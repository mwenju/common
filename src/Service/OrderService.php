<?php


namespace Mwenju\Common\Service;


use Mwenju\Common\Event\AfterOrderPay;
use Mwenju\Common\Event\AfterOrderSubmit;
use Mwenju\Common\Event\OrderCancel;
use Mwenju\Common\Model\MfDepotProduct;
use Mwenju\Common\Model\MfShop;
use Mwenju\Common\Model\MfShopAccount;
use Mwenju\Common\Model\MfShopAddress;
use Mwenju\Common\Model\MfShopOrder;
use Mwenju\Common\Model\MfShopOrderLog;
use Mwenju\Common\Model\MfShopOrderProduct;
use Mwenju\Common\Model\TbStockTakeOrder;
use Mwenju\Common\Model\TbWorkOrder;
use Mwenju\Common\Model\YunShopCredit;
use Mwenju\Common\Pojo\User;
use Mwenju\Common\Rpc\ShopOrderServiceInterface;
use Mwenju\Common\Utils\Logger;
use Mwenju\Common\Utils\Sms;
use Mwenju\Common\Utils\UtilsTool;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Di\Exception\Exception;
use Hyperf\Event\EventDispatcher;
use Psr\EventDispatcher\EventDispatcherInterface;

class OrderService
{

    #[Inject]
    private EventDispatcherInterface $eventDispatcher;

    #[Inject]
    private User $user;

    #[Inject]
    private ShopAccountService $shopAccountService;

    #[Inject]
    private ShopCartService $cartService;

    #[Inject]
    private CouponService $couponService;

    public function setUser($user):OrderService
    {
        $this->user = $user;
        return $this;
    }

    /**
     * 分拣
     * @param int $order_product_id
     * @throws Exception
     */
    public function fenjian($order_product_id = 0,$admin_id = 0)
    {
        $order_product = MfShopOrderProduct::where('id', $order_product_id)->first();
        if (!$order_product) {
            throw new Exception('订单商品不存在，请检查');
        }

        $order_id = $order_product->order_id;
        $order = MfShopOrder::find($order_id);
        if (!$order) {
            throw new Exception('订单不存在');
        }
        if (UtilsTool::config_value("STOCK_TAKE_CHECK")) {
            if (TbStockTakeOrder::where("top_depot_id", $order->top_depot_id)
                    ->where("parent_id", 0)
                    ->whereIn("status", [0, 1])->count() > 0) {
                throw new Exception("正在盘存中，禁止操作");
            }
        }
        // 订单取消拦截
        if (!in_array($order->status, [1, 2])) {
            throw new Exception('当前订单状态不能操作分拣状态为：' . trans('lang.order_status_' . $order->status));
        }

        if ($order_product->send_num >= $order_product->num) {
            throw new Exception('已分拣过，不用重复操作');
        }
        // 判断当前库位库存
        $depot_product = MfDepotProduct::where('top_depot_id', $order->top_depot_id)
            ->where('product_id', $order_product->product_id)
            ->where("is_delete", 0)
            ->first();
        if (!$depot_product) {
            throw new Exception($order_product->product_name.':没有库位信息');
        }
        if ($depot_product->store_num < $order_product->num) {
            throw new Exception('当前库位库存不足');
        }

        Db::beginTransaction();
        try {
            Db::table("mf_shop_order_product")
                ->where("id", $order_product_id)
                ->increment("send_num", $order_product->num, ['check_status' => 1]);

            if ($order->status == 1) {
                Db::table("mf_shop_order")->where("id", $order_id)
                    ->update([
                        'status' => 2,
                        'delivery_status' => 1
                    ]);

                Db::table("mf_shop_order_log")->insert([
                    'order_id' => $order_id,
                    'shop_id' => $order->shop_id,
                    'user_id' => $order->user_id,
                    'admin_id' => $admin_id,
                    'status' => 2,
                    'create_time' => date("Y-m-d H:i:s"),
                    'remark' => '配送中'
                ]);
            }

            Db::update("update mf_depot_product set lock_num = lock_num+?,store_num = store_num-? 
                    where product_id=? and is_delete=0", [$order_product->num, $order_product->num, $order_product->product_id]);
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollBack();
            throw new Exception($e->getMessage());
        }
    }

    /**
     * 分拣提交
     * @param int $order_id
     * @return
     * @throws Exception
     */
    public function fenJianSubmit($order_id = 0,$admin_id = 0)
    {
        $order = MfShopOrder::find($order_id);
        if (UtilsTool::config_value("STOCK_TAKE_CHECK")) {
            if (TbStockTakeOrder::where("top_depot_id", $order->top_depot_id)
                    ->where("parent_id", 0)
                    ->whereIn("status", [0, 1])->count() > 0) {
                throw new Exception("正在盘存中，禁止操作");
            }
        }
        // 订单取消拦截
        if ($order->status != 2) throw new Exception('当前订单已发生变化，不能继续操作，状态为：' . trans('lang.order_status_' . $order->status));

        if ($order->delivery_status != 1) throw new Exception('当前订单状态有误，不能提交' . trans('lang.delivery_status_' . $order->status));

        $order_product = MfShopOrderProduct::where('order_id', $order_id)->get();

        foreach ($order_product as $p) {
            if ($p->send_num < $p->num) throw new Exception($p->product_name . '未分拣');
        }

        MfShopOrderProduct::where('order_id', $order->id)->update(['check_status' => 2]);

        MfShopOrder::where('id', $order_id)->update(['delivery_status' => 2]);

        MfShopOrderLog::insert([
            'order_id' => $order_id,
            'shop_id' => $order->shop_id,
            'user_id' => $order->user_id,
            'admin_id' => $admin_id,
            'status' => 22,
            'create_time' => date("Y-m-d H:i:s"),
            'remark' => '分拣已提交'
        ]);

    }

    /**
     * 复检 check_status  0待分拣,1已分拣,2待复检,3已复检,4已出库,5已撤销,6撤销,
     * @param int $order_product_id
     * @return
     * @throws Exception
     */
    public function fuJian($order_product_id = 0)
    {
        $order_product = MfShopOrderProduct::find($order_product_id);

        if (!$order_product) throw new Exception('订单商品不存在，请检查');

        $order_id = $order_product->order_id;

        $order = MfShopOrder::find($order_id);

        // 订单取消拦截
        if ($order->status != 2) throw new Exception('当前订单已发生变化，不能继续操作，状态为：' . trans('lang.order_status_' . $order->status));

        if (!in_array($order->delivery_status, [2, 3])) throw new Exception('操作失败，请检查商品状态:' . trans('lang.delivery_status_' . $order->delivery_status));

        if ($order_product->check_status != 2) throw new Exception('操作失败，请检查商品状态:' . trans('lang.check_status_' . $order_product->check_status));

        $order->delivery_status = 3; // 标记复检中
        $order->save();
        $order_product->check_status = 3;
        $order_product->save();
    }

    /**
     * 复检提交
     * @param number $order_id
     * 复检 check_status  0待分拣,1已分拣,2待复检,3已复检,4已出库,5已撤销,6撤销,
     */
    public function fuJianSubmit($order_id = 0,$admin_id = 0)
    {
        Db::beginTransaction();
        try {
            $order = MfShopOrder::find($order_id);

            // 订单取消拦截
            if ($order->status != 2) throw new Exception('当前订单已发生变化，不能继续操作，状态为：' . trans('lang.order_status_' . $order->status), 300);

            $order_product = MfShopOrderProduct::where('order_id', $order_id)->get();

            foreach ($order_product as $p) {
                if ($p->check_status != 3) throw new Exception($p->product_name . '未确认复检');
            }

            Db::table("mf_shop_order_product")->where('order_id', $order_id)->update(['check_status' => 4]);

            Db::table("mf_shop_order")->where('id', $order_id)->update(['delivery_status' => 4]);

            Db::table("mf_shop_order_log")->insert([
                'order_id' => $order_id,
                'shop_id' => $order->shop_id,
                'user_id' => $order->user_id,
                'admin_id' => $admin_id,
                'status' => 24,
                'create_time' => date("Y-m-d H:i:s"),
                'remark' => '复检完成'
            ]);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollBack();
        }
    }

    /**
     * 取消订单
     * @param int $order_id
     * @return
     * @throws Exception
     */
    public function cancel($param = [])
    {
        $order_id = $param['order_id']??0;
        $admin_id = $param['admin_id']??0;
        $user_id  = $param['user_id']??0;
        $session_admin_name = $param['session_admin_name']??"";

        if ($order_id == 0) UtilsTool::exception('参数有误');

        $order_id = intval($order_id);

        $order = MfShopOrder::find($order_id);

        if (!$order)
            UtilsTool::exception('订单不存在');

        if ($order->status > 2)
            UtilsTool::exception('当前订单状态，不能取消');

        Db::beginTransaction();
        try {

            Db::table("mf_shop_order")->where("id", $order_id)->update(['status' => 5]);

            // 释放运营库存
            Db::update("UPDATE tb_product_stock a,mf_shop_order_product b
                set a.lock_num = a.lock_num-b.num,a.salable_num = a.salable_num+b.num
                WHERE a.product_id=b.product_id and a.top_depot_id=b.top_depot_id and b.order_id=?", [$order_id]);
            // 释放仓库锁定库存
            Db::update("UPDATE mf_depot_product a,mf_shop_order_product b
                SET a.lock_num = a.lock_num-b.send_num,a.store_num = a.store_num+b.send_num
                WHERE a.product_id=b.product_id AND a.top_depot_id=b.top_depot_id and b.send_num > 0 and b.order_id=?", [$order_id]);

            Db::update("UPDATE mf_shop_order_product SET send_num = 0  WHERE  send_num > 0 and order_id=?", [$order_id]);

            //退还红包
            Db::table("tb_coupon_list")->where('order_id', $order_id)->update(['order_id' => 0, 'use_time' => null]);

            //退还余额
            if ($order->paid_balance_price > 0) {
                $this->shopAccountService->changeAccount($order->shop_id, $order->paid_balance_price, '取消订单退还金额', 6);
            }
            // 支付金额退还到余额,在线支付的退还余额账户
            if ($order->paid_price > 0) {
                if (in_array($order->pay_type,[1,2]))
                {
                    $this->shopAccountService->changeAccount($order->shop_id, $order->paid_price, '取消订单退还支付金额', 7);
                }
                // 账期余额返还
                if ($order->pay_type == 6)
                {
                    di(ShopCreditService::class)->change($order->supplier_id,$order->shop_id,$order->paid_price,2,"取消退还",$session_admin_name);
                }
            }
            // 退还积分
            if ($order->paid_integrate > 0)
            {
                Db::table("mf_shop_account")->where("shop_id",$order->shop_id)
                    ->increment("enable_integrate",$order->paid_integrate);

                Db::table("mf_shop_account_log")->insert([
                    'shop_id'=>$order->shop_id,
                    'why_info'=>'取消订单退还积分',
                    'add_type'=>7,
                    'in_or_out'=>1,
                    'account_field'=>'enable_integrate',
                    'add_num'=>$order->paid_integrate,
                    'create_time'=>date("Y-m-d H:i:s")
                ]);
            }
            Db::table("mf_shop_order_log")->insert([
                'order_id' => $order_id,
                'shop_id' => $order->shop_id,
                'user_id' => $user_id,
                'admin_id' => $admin_id,
                'status' => 5,
                'remark' => '取消订单',
                'create_time' => date("Y-m-d H:i:s")
            ]);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            UtilsTool:: exception($e->getMessage());
        }
    }

    /**
     * 自动取消订单
     */
    public function autoCancel()
    {
        $time = UtilsTool::config_value("ORDER_CANCEL_TIME");
        $time = $time ? $time : 30;
        $time = 60 * $time;
        $olist = MfShopOrder::where('create_time', '<=', date("Y-m-d H:i:s", time() - $time))
            ->where('status', '=', 0)
            ->where('pay_type', '<', 3)
            ->get()->each(function ($item, $index) {
                $this->cancel(['order_id'=>$item->id]);
            });
    }

    /**
     * 未支付短信提醒通知
     */
    public function noPayNotice()
    {
        $time = UtilsTool::config_value("ORDER_NO_PAY_NOTICE_TIME");
        $time = $time ? $time : 10; // 默认10分钟
        $time = 60 * $time;
        $sql = "SELECT * from (
                SELECT o.id order_id,s.id,o.`status`,o.mobile,o.create_time,IFNULL(s.send_date,o.create_time) send_date from mf_shop_order o 
                LEFT JOIN 
                (
                    SELECT * FROM mf_sms_log where id in(SELECT MAX(id) from mf_sms_log WHERE order_id>0 GROUP BY order_id)
                ) s on s.order_id=o.id WHERE o.`status`=0 and o.pay_type < 3
            ) oo where UNIX_TIMESTAMP(oo.send_date)<(UNIX_TIMESTAMP(NOW())-{$time});
            ";
        $res = Db::select($sql);
        if ($res) {
            foreach ($res as $v) {
                // 发送短信
                Sms::send($v->mobile, 'ORDER_PAY_NOTICE', [], ['order_id' => $v->order_id]);
            }
        }
    }

    public function notify($param = [])
    {
        if (isset($param['trade_state']) && $param['trade_state'] != 'SUCCESS') {
            Logger::init()->error($param['trade_state_desc']);
            return $param['trade_state_desc'];
        }
        try {
            Db::beginTransaction();
            $order = MfShopOrder::where('order_code', $param['out_trade_no'])->where("status", 0)->first();
            if (!$order) {
                Db::commit();
                return;
            }
            $order->status = 1;
            $order->pay_time = date("Y-m-d H:i:s");
            $order->paid_price = $order->total_price - $order->paid_balance_price - $order->discount_price;
            $order->pay_type = 1;
            if (!$order->save()) {
                UtilsTool::exception("已经支付，不能重复更新");
            };

            MfShopOrderLog::insert([
                'order_id' => $order->id,
                'shop_id' => $order->shop_id,
                'user_id' => $order->user_id,
                'status' => 1,
                'create_time' => date("Y-m-d H:i:s"),
                'remark' => '订单支付'
            ]);
            Db::commit();

            $this->eventDispatcher->dispatch(new AfterOrderPay($order->id));

            return '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
        } catch (\Exception $e) {
            Db::rollBack();
            Logger::init()->error("WX_PAY_ERR:" . $e->getMessage());
            return $e->getMessage();
        }
    }

    /**
     * @param ShopCartService $cart
     * @param int $coupon_id
     * @param User $user
     * @return array
     * @throws Exception
     */
    public function getPrice($cart = null, $coupon_id = 0,$user = null)
    {
        $total_price = $cart->totalPrice($user->getShopId());
        $freight_price = 0;
        $discount_price = 0;
        $balance_price = 0;
        $enable_money = MfShopAccount::where('shop_id', $user->getShopId())->value('enable_money');
        if ($coupon_id > 0) {
            $discount_price = $this->couponService->discountPrice($cart, $coupon_id,$user);
            $discount_price = $discount_price > $total_price ? $total_price : $discount_price;
        }
        if ($enable_money > 0) {
            if ($total_price + $freight_price - $discount_price > $enable_money) {
                $balance_price = $enable_money;
            } else {
                $balance_price = $total_price + $freight_price - $discount_price;
            }
        }
        $balance_price = round($balance_price, 2);
        $pay_total_price = $total_price + $freight_price - $discount_price - $balance_price;

        return [$total_price, $freight_price, $discount_price, $balance_price, round($pay_total_price, 2)];
    }

    /**
     * 提交订单
     * @param User $user
     * @param array $input
     * @return array
     * @throws Exception
     */
    public function submit(User $user,$input = [])
    {
        $this->user = $user;
        Db::beginTransaction();
        try {
            $shop_id        = $user->getShopId();
            $user_id        = $user->getUserId();
            $top_depot_id   = $user->getDepotId();
            $address_id     = isset($input['address_id']) ? intval($input['address_id']) : 0;
            $coupon_id      = isset($input['coupon_id']) ? intval($input['coupon_id']) : 0;
            $remark         = isset($input['remark']) ? trim($input['remark']) : '';
            $key            = 'order_clock_' . $shop_id;
            $random         = UtilsTool::get_rand(8);
            $ttl = 3;
            $redis          = redis();
            $rs             = $redis->set($key, $random, array('nx', 'ex' => $ttl));
            if (!$rs)
                UtilsTool::exception("操作过于频繁，请稍后");
            // 购物车
            $cart = $this->cartService->items(true,$user);
            if (empty($cart))
                UtilsTool::exception("购物车没有商品");

            $this->cartService->checkStock($user);

            // 收货地址
            if ($address_id == 0)
                UtilsTool::exception('请选择收货地址');
            $adr = MfShopAddress::where('shop_id', $shop_id)->find($address_id);
            if (!$adr)
                UtilsTool::exception('您选择的收货地址有误');
            if (empty($adr->area_code))
                UtilsTool::exception('您的收货地址不完善，请完善后再提交');

            // 红包
            list($total_price, $freight_price, $discount_price, $balance_price, $pay_total_price) = $this->getPrice($this->cartService, $coupon_id,$user);

            Logger::init()->info("orderSubmit:", [$total_price, $freight_price, $discount_price, $balance_price, $pay_total_price]);

            $product_total_num      = $this->cartService->totalNum(true,$shop_id);
            $product_total_type_num = $this->cartService->totalType($shop_id);

            $pay_type = $pay_total_price == 0 ? 3 : 0;
            $status = $pay_total_price == 0 ? 1 : 0; // 订单状态:0.等待支付,1.支付结束等待发货.2.发货结束.3.订单作废,4.订单取消，5.签收
            $now = date("Y-m-d H:i:s");
            $shop_name = MfShop::where("id", $shop_id)->value("cname");
            $selectNum = count($this->cartService->getSelectProductIds($user));

            // 库存更新/判断
            $sql = 'UPDATE mf_shop_cart a,tb_product_stock b,tb_product c set 
                        b.lock_num = b.lock_num+a.num,
                        b.salable_num = b.salable_num-a.num
                        WHERE a.selected=1 
                        AND a.product_id=b.product_id 
                        AND b.product_id=c.id
                        AND c.is_on_sale>0
                        AND b.top_depot_id=:top_depot_id
                        AND b.salable_num>=a.num
                        AND a.shop_id=:shop_id';
            $update_num = Db::update($sql, ['top_depot_id'=>$top_depot_id,'shop_id' => $shop_id]);
            // 异常商品检查
            if ($update_num < $selectNum) {
                $sql = 'SELECT a.product_id,b.product_name,c.is_on_sale,a.salable_num,b.num from tb_product_stock a 
                        LEFT JOIN mf_shop_cart b on a.product_id=b.product_id
                        LEFT JOIN tb_product c on c.id=b.product_id
                        WHERE b.selected=1
                        AND (a.salable_num<b.num OR c.is_on_sale=0)
                        AND b.shop_id=:shop_id
                        ORDER BY c.is_on_sale asc LIMIT 1';
                $ExceptionProduct = Db::select($sql, ['shop_id' => $shop_id]);
                if ($ExceptionProduct[0]->is_on_sale == 0) {
                    UtilsTool::exception($ExceptionProduct[0]->product_name . "已下架", 303);
                } else {
                    UtilsTool::exception($ExceptionProduct[0]->product_name . "库存不足", 303);
                }
            }

            $order = [
                'shop_id' => $shop_id,
                'user_id' => $user_id,
                'shop_name' => $shop_name,
                'top_depot_id' => $top_depot_id,
                'order_code' => UtilsTool::create_order_number($shop_id),
                'product_total_price' => $total_price,
                'product_total_num' => $product_total_num,
                'product_total_type_num' => $product_total_type_num,
                'freight_price' => $freight_price,
                'total_price' => $total_price + $freight_price,
                'all_money' => $pay_total_price * 100,
                'real_money' => $pay_total_price * 100,
                'discount_price' => $discount_price,
                'paid_price' => 0,
                'pay_price' => $pay_total_price, //待支付金额
                'paid_balance_price' => $balance_price,
                'pay_type' => $pay_type,
                'pay_time' => $status == 1 ? $now : null,
                'status' => $status,
                'consignee' => $adr['link_name'],
                'mobile' => $adr['link_mobile'],
                'address' => $adr['addr_detail'],
                'province_code' => $adr['province_code'],
                'city_code' => $adr['city_code'],
                'area_code' => $adr['area_code'],
                'addr_type' => $adr['addr_type'],
                'create_time' => $now,
                'address_id' => $address_id,
                'remark' => $remark,
                'audit_status' => 1, // 商户下单自动审核通过
                'device_type' => $user->getDeviceType()
            ];
            $order_id = Db::table("mf_shop_order")->insertGetId($order);
            $order['id'] = $order_id;
            $act_product = [];
            foreach ($cart as $c) {
                //促销检查
                $act_key = "CURRENT_ACT_ING_" . $c->product_id;
                $is_act = $redis->get($act_key);
                if ($is_act && $is_act > 0) {
                    $act_product[] = [
                        'order_id' => $order_id,
                        'shop_id' => $shop_id,
                        'product_id' => $c->product_id,
                        'act_seckill_id' => $is_act,
                        'buy_num' => $c->num,
                        'create_time' => $now
                    ];
                    Db::table("act_seckill_product")->where("act_seckill_id", $is_act)
                        ->where("product_id", $c->product_id)
                        ->increment("sold_stock", $c->num);
                    $act_stock_key = "CURRENT_STOCK_" . $top_depot_id . "_" . $c->product_id;
                    if ($redis->get($act_stock_key) < $c->num) {
                        UtilsTool::exception($c->product_name . "活动库存不足", 304);
                    }
                    ActService::setActList($c->product_id);
                }
            }
            // 促销活动记录
            if (count($act_product) > 0) {
                Db::table("act_seckill_order")->insert($act_product);
            }
            // 记录订单商品
            $sql = 'INSERT INTO mf_shop_order_product (order_id,from_shop_id,product_id,product_name,price,num,top_depot_id,
                    supplier_id,bar_code,art_no,product_unit,bid_price,list_img_path,param_list)
                    SELECT ' . $order_id . ' order_id,a.from_shop_id,a.product_id,a.product_name,a.shop_price,a.num,'.$top_depot_id.' top_depot_id,
                    b.supplier_id,b.bar_code,b.art_no,a.product_unit,b.bid_price,b.list_img_path,a.param_list 
                    from mf_shop_cart a left join tb_product b on b.id=a.product_id WHERE a.shop_id=:shop_id AND a.selected=1';
            Db::insert($sql, ['shop_id' => $shop_id]);
            //资金日志
            if ($balance_price > 0) {
                Db::table("mf_shop_account")->where("shop_id", $shop_id)->decrement('enable_money', $balance_price);
                Db::table("mf_shop_trade_history")->insert([
                    'shop_id' => $shop_id,
                    'why_info' => '下单扣减',
                    'num' => $balance_price,
                    'in_or_out' => -1,
                    'trade_type' => 1,
                    'create_time' => $now
                ]);
            }
            Db::table("mf_shop_order_log")->insert([
                'order_id' => $order_id,
                'shop_id' => $shop_id,
                'user_id' => $user_id,
                'status' => 0,
                'remark' => '提交订单',
                'create_time' => $now
            ]);
            if ($status == 1) {
                Db::table("mf_shop_order_log")->insert([
                    'order_id' => $order_id,
                    'shop_id' => $shop_id,
                    'user_id' => $user_id,
                    'status' => 1,
                    'remark' => '订单已确认',
                    'create_time' => $now
                ]);
            }
            Db::table("mf_shop_cart")->where("shop_id", $shop_id)
                ->where("selected", 1)->delete();
            if ($coupon_id > 0) {
                Db::table("tb_coupon_list")->where("id", $coupon_id)
                    ->update(['order_id' => $order_id, 'use_time' => $now]);

                Db::table("mf_shop_order_coupon")->insert([
                    'order_id' => $order_id,
                    'coupon_money' => $discount_price,
                    'coupon_list_id' => $coupon_id
                ]);
            }
            Db::commit();

        } catch (\Exception $e) {
            Db::rollback();
            Logger::init()->error("订单创建失败：" . $e->getMessage(), $input);
            $msg = "订单创建失败，请联系管理员";
            $msg = $e->getMessage();
            UtilsTool::exception($msg);
        }
        if ($redis->get($key) == $random) {
            $redis->del($key);
        }
        $this->eventDispatcher->dispatch(new AfterOrderSubmit($order_id));
        return $order;

    }

    /**
     * 收货签收
     */
    public function receive($order_id = 0)
    {
        Db::beginTransaction();
        try {
            $order = MfShopOrder::find($order_id);
            if (!$order) UtilsTool::exception("订单不存在");
            if ($order->status != 3) UtilsTool::exception("还未发货不能签收哦");
            $order->status = 4;
            $order->delivery_status = 8;
            $order->receive_time = date("Y-m-d H:i:s");
            $order->save();
            MfShopOrderProduct::where("order_id", $order_id)->where("get_num", '<', 0)->update(
                ['get_num' => Db::raw("send_num")]
            );
            MfShopOrderLog::create([
                'order_id' => $order_id,
                'shop_id' => $order->shop_id,
                'user_id' => $order->user_id,
                'admin_id' => 0,
                'status' => 43,
                'remark' => '已签收'
            ]);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollBack();
            Logger::init()->error($e->getMessage());
            UtilsTool::exception($e->getMessage());
        }
    }

    /**
     * 再次购买,购物车已存在的商品不再添加
     * @param int $order_id
     */
    public function buyAgain($order_id = 0)
    {
        Db::insert("INSERT INTO mf_shop_cart (shop_id,product_id,num,shop_price,total_price,image_path,product_name,product_unit,param_list,create_time,last_update_time)
                SELECT c.shop_id,a.product_id,a.num,a.price,a.num*a.price,a.list_img_path,a.product_name,a.product_unit,b.product_param_values_json,NOW(),NOW()
                from mf_shop_order_product a left join tb_product b on a.product_id=b.id 
                LEFT JOIN mf_shop_order c on c.id=a.order_id
                LEFT JOIN mf_shop_cart d on d.product_id=a.product_id and d.shop_id=c.shop_id
                where d.product_id is null and a.order_id=?", [$order_id]);
    }

    /**
     * 扫码对货
     * @param int $order_product_id
     * @param int $get_num
     * @return bool
     * @throws Exception
     */
    public function getNum($order_product_id = 0,$get_num = 0)
    {
        $pinfo = MfShopOrderProduct::find($order_product_id);

        $oinfo = MfShopOrder::where("shop_id",$this->user->getUserId())->where("id",$pinfo->order_id)->first();

        if(!$oinfo) UtilsTool::exception("数据不存在");

        if($oinfo->status != 3) UtilsTool::exception("当订单状态不能操作");

        if($oinfo->delivery_status == 5)
        {
            $oinfo->delivery_status = 7;
            $oinfo->save();
        }
        if($get_num < 0 && abs($get_num) > $pinfo->send_num)
        {
            $get_num = -$pinfo->get_num;
        }

        if($pinfo['get_num'] >=0)
        {
            MfShopOrderProduct::where("id",$order_product_id)
                ->increment("get_num",$get_num);
        }
        else
        {
            MfShopOrderProduct::where("id",$order_product_id)
                ->update(['get_num'=>$get_num]);
        }
        return true;
    }

    public function receiveSubmit($order_id = 0)
    {
        $order = MfShopOrder::where("id",$order_id)->where("shop_id",$this->user->getShopId())->first();

        if(!$order) UtilsTool::exception("数据不存在");

        if($order['status'] != 3) UtilsTool::exception("当订单状态不能操作");

        $unDoNu = MfShopOrderProduct::where("get_num","<",0)->where("order_id",$order_id)->count();
        if($unDoNu > 0)
        {
            UtilsTool::exception("还有商品未核对完成，不能提交");
        }

        $order->status = 4;
        $order->delivery_status = 8;
        $order->receive_time = date("Y-m-d H:i:s");
        $order->save();
        MfShopOrderLog::insert([
            'order_id'=>$order_id,
            'shop_id'=>$order->shop_id,
            'user_id'=>$order->user_id,
            'admin_id'=>0,
            'status'=>43,
            'remark'=>'已签收'
        ]);

        if(MfShopOrderProduct::where("order_id",$order_id)
                ->where('send_num','<>',Db::raw("get_num"))
                ->count() > 0)
        {
            TbWorkOrder::insert([
                'order_id'=>$order_id,
                'order_code'=>$order->order_code,
                'order_type'=>1,
                'shop_id'=>$this->user->getShopId()
            ]);
        }

    }

    public function remarkSubmit($order_id = 0,$remark = '')
    {
        if(empty($remark)) return;
        $order = MfShopOrder::find($order_id);
        MfShopOrderLog::insert([
            'order_id'=>$order_id,
            'shop_id'=>$order->shop_id,
            'user_id'=>$order->user_id,
            'admin_id'=>$this->user->getAdminId(),
            'status'=>99, // 备注专用状态
            'remark'=>$remark
        ]);
    }
}