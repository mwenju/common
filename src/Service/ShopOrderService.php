<?php


namespace Mwenju\Common\Service;

use Mwenju\Common\Model\CcProductStockLog;
use Mwenju\Common\Model\MfDepotProduct;
use Mwenju\Common\Model\MfShop;
use Mwenju\Common\Model\MfShopAddress;
use Mwenju\Common\Model\MfShopOrder;
use Mwenju\Common\Model\MfShopOrderLog;
use Mwenju\Common\Model\MfShopOrderProduct;
use Mwenju\Common\Model\TbProduct;
use Mwenju\Common\Model\TbProductStock;
use Mwenju\Common\Model\TbProductStockLog;
use Mwenju\Common\Model\TbSupplier;
use Mwenju\Common\Model\YunShopCredit;
use Mwenju\Common\Service\Dao\ShopOrderDao;
use Mwenju\Common\Service\Formatter\ShopOrderFormatter;
use Mwenju\Common\Utils\Sms;
use Mwenju\Common\Utils\UtilsTool;
use Hyperf\Database\Exception\QueryException;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;
use Hyperf\RpcServer\Annotation\RpcService;
use Swoole\Exception;

/**
 * 销售单服务
 * Class ShopOrderService
 * @package App\Common\Service
 * @RpcService(name="ShopOrderService",protocol="jsonrpc",server="jsonrpc")
 */
#[RpcService("ShopOrderService","jsonrpc","jsonrpc")]
class ShopOrderService extends BaseService
{

    #[Inject]
    private ShopOrderDao $shopOrderDao;

    #[Inject]
    private ShopOrderFormatter $shopOrderFormatter;

    public function getList($param = [])
    {
        list($page,$limit)  = $this->pageFmt($param);
        [$total,$list] = $this->shopOrderDao->getList($param,$page,$limit);
        return ['total'=>$total,'rows'=>$this->shopOrderFormatter->formatList($list)];
    }

    public function getProductList($param = [])
    {
        $id             = $param['id']??0;
        $supplier_id    = $param['supplier_id']??"";
        $shop_id        = $param['shop_id']??0;
        $top_depot_id   = $param['top_depot_id']??"";
        $bar_code       = $param['bar_code']??"";
        $is_return      = $param['is_return']??"";
        $map[]          = ['a.shop_id','=',$shop_id];
        if (strlen($id) > 0){
            $map[] = ['a.order_id','=',$id];
        }
        if (strlen($is_return) > 0){
            $map[] = ['a.is_return','=',$is_return];
        }
        if (strlen($supplier_id) > 0 && $id == 0){
            $map[] = ['a.supplier_id','=',$supplier_id];
        }
        if (strlen($top_depot_id) > 0){
            $map[] = ['a.top_depot_id','=',$top_depot_id];
        }

        if (!empty($bar_code)){
            $map[] = ['a.bar_code','=',$bar_code];
        }
        $total_buy_price = 0;
        $total_buy_num = 0;
        $data = Db::table("mf_shop_order_product as a")->selectRaw("a.*,0 total_price")
            ->leftJoin("tb_product as b","b.id","=","a.product_id")
            ->where($map);
        $total = $data->count();
        $list = $data->orderBy("id","desc")->get()->each(function ($item,$index) use (&$total_buy_price,&$total_buy_num){
            $item->list_img_path = img_url($item->list_img_path);
            $item->total_price  = round($item->num * $item->price,2);
            $total_buy_price    += ($item->num * $item->price);
            $total_buy_num      +=$item->num;

        });
        $footer[] = ['total_price'=>round($total_buy_price,2),'num'=>$total_buy_num];
        return ['total'=>$total,'rows'=>$list,'footer'=>$footer];

    }

    public function getInfo($param = [])
    {
        $item = MfShopOrder::selectRaw("*,'' audit_status_str")->find($param['id']??0);
        $item->audit_status_str = trans("audit.status_".$item->audit_status);
        return $item;
    }

    public function audit($param = [])
    {
        $id                     = $param['id']??0;
        $audit_status           = $param['audit_status']??0;
        $audit_by               = $param['audit_by']??'';
        $session_user_id        = $param['session_user_id']??0;
        $session_admin_id       = $param['session_admin_id']??0;
        $session_admin_name     = $param['session_admin_name']??'';
        $audit_remark           = $param['audit_remark']??'';
        $order                  = MfShopOrder::find($id);
        if ($order->audit_status > 0)
            return arrayError("已审核不能重复操作");

        if ($order->status > 0)
            return arrayError("订单已处理，不能操作");

        Db::beginTransaction();
        try {
            $res = di(AuditLogService::class)->add(['model'=>'mf_shop_order','model_id'=>$id,'audit_status'=>$audit_status,'audit_user_id'=>$session_user_id,'audit_remark'=>$audit_remark]);
            if ($res['err_code'] > 0) throw new Exception($res['msg']);
            $audit_status = $res['data']['audit_status'];
            if ($audit_status == 1)
            {
                Db::table("mf_shop_order_log")->insert([
                    'order_id' => $id,
                    'shop_id' => $order->shop_id,
                    'user_id' => $session_user_id,
                    'admin_id' => $session_admin_id,
                    'status' => 01,
                    'remark' => '审核通过',
                    'create_time' => date("Y-m-d H:i:s")
                ]);
                $order->status      = $order->is_return == 1 ? 3:1;
                $order->paid_price  = $order->pay_price;
                $order->pay_time    = date("Y-m-d H:i:s");
                if ($order->is_return == 1)
                    $order->delivery_time = date("Y-m-d H:i:s");
                $order->save();
            }
            else if ($audit_status == 2)
            {
                Db::table("mf_shop_order_log")->insert([
                    'order_id' => $id,
                    'shop_id' => $order->shop_id,
                    'user_id' => $session_user_id,
                    'admin_id' => $session_admin_id,
                    'status' => 02,
                    'remark' => '审核未通过',
                    'create_time' => date("Y-m-d H:i:s")
                ]);
                if ($order->is_return == 0){
                    di(OrderService::class)->cancel(['order_id'=>$id,'admin_id'=>$session_admin_id,'user_id'=>$session_user_id]);
                }
            }

            Db::commit();
        }
        catch (\Exception $e)
        {
            Db::rollBack();
            return arrayError($e->getMessage());
        }
        return arraySuccess("审核成功");
    }

    private function checkProduct($param = [])
    {
        $param          = array_map("intval",$param);
        $shop_id        = $param['shop_id']??0;
        $supplier_id    = $param['supplier_id']??0;
        $product_id     = $param['product_id']??0;
        $num            = $param['num']??0;
        $top_depot_id   = $param['top_depot_id']??0;
        $is_return  = $param['is_return']??0;

        if ($shop_id == 0 || $supplier_id == 0)
            return arrayError("商家和供应商必须选择");

        $product = TbProduct::find($product_id);
        if (!$product)
            return arrayError("商品信息有误");

        $supplier = TbSupplier::find($product->supplier_id);
        if (!$supplier)
            return arrayError("没有供应商信息");

        $stock = TbProductStock::where("top_depot_id",$top_depot_id)->where("product_id",$product_id)->first();
        if (!$stock && !$is_return)
            return arrayError("该商品还没有入库哦");

        return arraySuccess("成功",[$product,$supplier,$stock]);
    }

    public function addProduct($param = [])
    {
        $is_return  = $param['is_return']??0;

        $check_res = $this->checkProduct($param);

        if ($check_res['err_code'] > 0) return $check_res;

        list($product,$supplier,$stock) = $check_res['data'];

        $shop_id        = $param['shop_id']??0;
        $supplier_id    = $param['supplier_id']??0;
        $product_id     = $param['product_id']??0;
        $num            = $param['num']??0;
        $top_depot_id   = $param['top_depot_id']??0;
        $price          = $param['price']??$product->wholesale_price;

        $order_product = MfShopOrderProduct::where("shop_id",$shop_id)
            ->where("order_id",0)
            ->where("product_id",$product_id)
            ->where("supplier_id",$supplier_id)
            ->where("is_return",$is_return)
            ->first();

        if ($order_product)
        {
            $order_product->num += $num;
            if(!$is_return) {
                if ($order_product->num > $stock->salable_num)
                    return arrayError("库存不足");
            }
            $order_product->save();
        }
        else
        {
            if(!$is_return) {
                if ($num > $stock->salable_num)
                    return arrayError("库存不足");
            }
            MfShopOrderProduct::insert([
                'order_id'=>0,
                'product_id'=>$product_id,
                'supplier_id'=>$supplier_id,
                'shop_id'=>$shop_id,
                'idea_title'=>$product->idea_title,
                'product_name'=>$product->product_name,
                'bar_code'=>$product->bar_code,
                'art_no'=>$product->art_no,
                'product_unit'=>$product->product_unit,
                'param_list'=>json_encode($product->product_param_values_json),
                'price'=>$price,
                'bid_price'=>$product->bid_price,
                'num'=>$num,
                'list_img_path'=>$product->list_img_path,
                'top_depot_id'=>$top_depot_id,
                'is_return'=>$is_return,
            ]);
        }
        return arraySuccess("添加成功");
    }

    public function updateProduct($param = [])
    {
        $is_return  = $param['is_return']??0;
        $check_res = $this->checkProduct($param);

        if ($check_res['err_code'] > 0) return $check_res;

        list($product,$supplier,$stock) = $check_res['data'];

        $shop_id        = $param['shop_id']??0;
        $supplier_id    = $param['supplier_id']??0;
        $product_id     = $param['product_id']??0;
        $num            = $param['num']??0;
        $price          = $param['price']??"";
        $top_depot_id   = $param['top_depot_id']??0;

        $order_product = MfShopOrderProduct::where("shop_id",$shop_id)
            ->where("order_id",0)
            ->where("product_id",$product_id)
            ->where("supplier_id",$supplier_id)
            ->where("is_return",$is_return)
            ->first();

        if ($order_product)
        {
            if ($num <= 0)
            {
                MfShopOrderProduct::where("id",$order_product->id)->delete();
            }
            else
            {
                if (!empty($price))
                    $order_product->price = $price;
                $order_product->num = $num;
                if(!$is_return) {
                    if ($order_product->num > $stock->salable_num)
                        return arrayError("库存不足");
                }

                $order_product->save();
            }
        }
        else
        {
            return $this->addProduct($param);
        }
        return arraySuccess("更新成功");
    }

    public function clearProduct($param = [])
    {
        MfShopOrderProduct::where([
            ['shop_id','=',$param['shop_id']??0],
            ['order_id','=',0],
            ['is_return','=',$param['is_return']??0],
        ])->delete();
        return arraySuccess("清除成功");
    }

    public function submit($param = [])
    {
        $is_return              = $param['is_return']??0;
        $shop_id                = $param['shop_id']??0;
        $user_id                = $param['user_id']??0;
        $supplier_id            = $param['supplier_id']??0;
        $admin_id               = $param['admin_id']??0;
        $session_admin_name     = $param['session_admin_name']??"";
        $address_id             = $param['address_id']??0;
        $remark                 = $param['remark']??"";
        $device_type            = $param['device_type']??"pc";
        $top_depot_id           = $param['top_depot_id']??0;
        $freight_price          = $param['freight_price']??0;
        $pay_type               = $param['pay_type']??0; // 支付方式，1-微信，2-支付宝，3-余额全支付，4-积分兑换，5-现金支付，6-账期支付
        $product_total_price    = 0;
        $product_total_num      = 0;
        $product_total_type_num = 0;
        $paid_price             = 0;
        $pay_time               = null;
        $shop                   = MfShop::find($shop_id);

        if ($pay_type <= 0)
            return arrayError("请选择支付方式");
        if (!$shop)
            return arrayError("批发商不存在");

        if ($shop->status == 0)
            return arrayError("门店未审核不能下单");

        if ($shop->status < 0)
            return arrayError("门店已冻结，不能操作");

        $supplier = TbSupplier::find($supplier_id);
        if (!$supplier)
            return arrayError("厂家不存在");

        if ($supplier->status == 0)
            return arrayError("当前厂家已冻结，不能提供服务哦");

        if ($supplier->audit_status == 0)
            return arrayError("当前厂家未审核，不能提供服务哦");

        if ($supplier->audit_status == 2)
            return arrayError("当前厂家未审核通过，不能提供服务哦");

        //检查授信关联
        $shop_credit_row = YunShopCredit::where("shop_id",$shop_id)->where("supplier_id",$supplier_id)->first();

//        if (!$shop_credit_row)
//            return arrayError("没有授信记录，不支持下单");

        if ($shop_credit_row && strtotime($shop_credit_row->end_time) < time())
            return arrayError("授信已到期，请联系厂家");

        $data = MfShopOrderProduct::where("order_id",0)
            ->where("shop_id",$shop_id)
            ->where("is_return",$is_return)
            ->where("supplier_id",$supplier_id);

        if ($data->count() == 0)
            return arrayError("请添加商品");

        $data->get()->each(function ($item,$index) use (&$product_total_price,&$product_total_num,&$product_total_type_num){
            $product_total_price    += $item->num * $item->price;
            $product_total_num      +=$item->num;
            $product_total_type_num ++;
        });

        if (!$is_return)
        {
            $address_row = MfShopAddress::find($address_id);
            if (!$address_row)
                return arrayError("地址有误");

            $consignee              = $address_row->link_name;
            $mobile                 = $address_row->link_mobile;
            $province_code          = $address_row->province_code;
            $city_code              = $address_row->city_code;
            $area_code              = $address_row->area_code;
            $address                = $address_row->addr_detail;

            // 库存判断
            $plist = Db::select("SELECT a.product_name,IFNULL(b.salable_num,0) salable_num,a.num from mf_shop_order_product a 
            LEFT JOIN tb_product_stock b on a.product_id=b.product_id and b.top_depot_id=?
            WHERE a.shop_id=? and a.order_id=0 and a.is_return=?",[$top_depot_id,$shop_id,$is_return]);
            foreach ($plist as $p)
            {
                if ($p->num > $p->salable_num)
                    return arrayError($p->product_name.'库存不足');
            }

            // 授信金额判断
            if ($shop_credit_row && $pay_type == 6)
            {
                if ($product_total_price <= $shop_credit_row->enable_money)
                {
                    $paid_price = $product_total_price;
                    $pay_time   = date("Y-m-d H:i:s");
                }
                else
                {
                    return arrayError("授信金额不足，请联系厂家");
                }
            }
        }
        else
        {
            $consignee              = "曹总";
            $mobile                 = "13312345676";
            $province_code          = "340000";
            $city_code              = "340100";
            $area_code              = "340111";
            $address                = "买文具仓库";
        }

        Db::beginTransaction();
        try {
            $order_code = UtilsTool::create_order_number($admin_id);
            if ($is_return) $order_code = "R".$order_code;
            $order_id = MfShopOrder::insertGetId([
                'shop_id'=>$shop_id,
                'user_id'=>$user_id,
                'is_return'=>$is_return,
                'jiesuan'=>0,
                'supplier_id'=>$supplier_id,
                'supplier_name'=>$supplier->supplier_name,
                'order_code'=>$order_code,
                'shop_name'=>$shop->cname,
                'all_money'=>$product_total_price*100,
                'real_money'=>$product_total_price*100,
                'product_total_price'=>$product_total_price,
                'product_total_num'=>$product_total_num,
                'product_total_type_num'=>$product_total_type_num,
                'total_price'=>$product_total_price,
                'discount_price'=>0,
                'pay_price'=>$product_total_price,
                'freight_price'=>$freight_price,
                'pay_time'=>$pay_time,
                'paid_price'=>$paid_price,
                'pay_type'=>$pay_type,
                'create_time'=>date("y-m-d H:i:s"),
                'consignee'=>$consignee,
                'mobile'=>$mobile,
                'province_code'=>$province_code,
                'city_code'=>$city_code,
                'area_code'=>$area_code,
                'address'=>$address,
                'address_id'=>$address_id,
                'remark'=>$remark,
                'device_type'=>$device_type,
                'top_depot_id'=>$top_depot_id,
            ]);

            MfShopOrderProduct::where("order_id",0)
                ->where("shop_id",$shop_id)
                ->where("supplier_id",$supplier_id)
                ->where("is_return",$is_return)
                ->update(['order_id'=>$order_id]);

            if (!$is_return)
            {
                // 更新库存
                $sql = 'UPDATE mf_shop_order_product a,tb_product_stock b,tb_product c set 
                        b.lock_num = b.lock_num+a.num,
                        b.salable_num = b.salable_num-a.num
                        WHERE a.order_id=? 
                        AND a.product_id=b.product_id 
                        AND b.product_id=c.id
                        AND c.is_on_sale>0
                        AND b.top_depot_id=a.top_depot_id
                        AND b.salable_num>=a.num
                        ';
                $update_num = Db::update($sql, [$order_id]);
                if ($update_num != $product_total_type_num)
                    throw new Exception("下单异常",300);

                // 更新可用账余额
                if ($paid_price > 0 && $pay_type == 6)
                {
                    $shop_credit_row->enable_money -= $paid_price;
                    $shop_credit_row->save();
                    Db::table("yun_shop_credit_log")->insert([
                        'shop_id'=>$shop_id,
                        'supplier_id'=>$supplier_id,
                        'add_num'=>-$paid_price,
                        'add_type'=>1,
                        'why_info'=>"下单扣减",
                        'create_time'=>date("Y-m-d H:i:s"),
                        'create_by'=>$session_admin_name
                    ]);
                }
            }

            Db::table("mf_shop_order_log")->insert([
                'order_id' => $order_id,
                'shop_id' => $shop_id,
                'user_id' => $user_id,
                'admin_id' => $admin_id,
                'status' => 0,
                'remark' => '提交订单',
                'create_time' => date("Y-m-d H:i:s")
            ]);

            $res = di(AuditLogService::class)->add([
                'model'=>'mf_shop_order',
                'model_id'=>$order_id,
                'audit_user_id'=>$user_id,
            ]);
            if ($res['err_code'] > 0) throw new Exception($res['msg']);
            Db::commit();
        }
        catch (QueryException $e)
        {
            Db::rollBack();
            return arrayError($e->getMessage());
        }
        return arraySuccess("提交成功");
    }

    public function cancel($param = [])
    {
        try {
            di(OrderService::class)->cancel($param);
        }catch (\Exception $e)
        {
            return arrayError($e->getMessage());
        }
       return arraySuccess("取消成功");
    }

    /**
     * 出库
     * @param int $order_id
     * do_type 变动类型，1-入库，2-出库，3-盘盈，4-盘亏，5-退货
     * @throws
     */
    public function sendOrder($order_id = 0,$admin_id = 0,$user_id = 0)
    {
        $order = MfShopOrder::find($order_id);
        if($order->status == 3)
            return arraySuccess("已出库");

        if($order->delivery_status != 4)
            return arrayError('当前订单未复检完成，无法出库');

        Db::beginTransaction();
        try {

            $up_num = Db::table("mf_shop_order")->where("id",$order_id)
                ->where("status",'<>',3)
                ->update([
                    'delivery_status'=>5,
                    'status'=>3,
                    'delivery_time'=>date("Y-m-d H:i:s")
                ]);
            if($up_num == 0)
                throw new \Exception("不能重复操作");

            MfShopOrderLog::insert([
                'order_id'=>$order_id,
                'shop_id'=>$order->shop_id,
                'user_id'=>$order->user_id,
                'admin_id'=>$admin_id,
                'status'=>35,
                'create_time'=>date("Y-m-d H:i:s"),
                'remark'=>'已出库'
            ]);

            // 扣减锁定库存
            foreach ($order->products as $p)
            {
                $ps = TbProductStock::query()->where('product_id',$p->product_id)->where('top_depot_id',$p->top_depot_id)->first();
                $ps->lock_num = $ps->lock_num - $p->send_num;
                $ps->stock_num =$ps->stock_num - $p->send_num;
                $ps->save();

                $bid_price = $ps->bid_price;
                // 更新固化成本价
                MfShopOrderProduct::where("id",$p->id)->update(['bid_price'=>$bid_price]);

                MfDepotProduct::where('product_id',$p->product_id)->where('top_depot_id',$p->top_depot_id)
                    ->where("is_delete",0)
                    ->decrement('lock_num',$p->send_num);

                //库存日志
                TbProductStockLog::insert([
                    'product_id'=>$p->product_id,
                    'add_num'=>-$p->send_num,
                    'bid_price'=>$bid_price,
                    'top_depot_id'=>$p->top_depot_id,
                    'do_type'=>2,
                    'do_type_id'=>$order_id,
                    'remark'=>'出库',
                    'admin_id'=>$admin_id,
                    'user_id'=>$user_id,
                    'supplier_id'=>$p->supplier_id,
                    'create_time'=>date("Y-m-d H:i:s")
                ]);
            }
            // 发货送积分
            $add_integrate = intval($order->paid_price+$order->paid_balance_price);
            if ($add_integrate > 0)
            {
                Db::table("mf_shop_account")->where("shop_id",$order->shop_id)
                    ->increment("enable_integrate",$add_integrate);
                Db::table("mf_shop_account_log")->insert([
                    'shop_id'=>$order->shop_id,
                    'why_info'=>'下单赠送',
                    'add_type'=>8,
                    'in_or_out'=>1,
                    'account_field'=>'enable_integrate',
                    'add_num'=>$add_integrate,
                    'create_time'=>date("Y-m-d H:i:s")
                ]);
            }
            Db::commit();
        }
        catch(\Exception $e)
        {
            Db::rollback();
            return arrayError($e->getMessage());
        }
        //发货自动入库商家礼品库
        di(ShopProductService::class)->receiveByOrderId($order_id);
        if ($order->paid_integrate > 0){
            Sms::send($order['mobile'],'MALL_DELIVERY_END');
        }else{
            Sms::send($order['mobile'],'DELIVERY_END');
        }
        return arraySuccess("已出库成功");
    }
    /**
     * 退货入库
     * @param array $param
     * @return array
     */
    public function receiveReturnOrder($param = [])
    {
        $id                  = $param['id']??0;
        $session_admin_id    = $param['session_admin_id']??0;
        $session_admin_name  = $param['session_admin_name']??"";
        $session_user_id     = $param['session_user_id']??0;
        $order = MfShopOrder::find($id);

        if (!$order)
            return arrayError("订单不存在");

        if ($order->is_return == 0)
            return arrayError("退货单才可以入库");

        if ($order->audit_status != 1)
            return arrayError("未审核不能入库签收");

        if ($order->status != 3)
            return arrayError("当前状态不能入库");

        Db::beginTransaction();
        try {
            $order->status = 4;
            $order->delivery_status = 8;
            $order->receive_time = date("Y-m-d H:i:s");
            $order->save();

            di(ShopCreditService::class)->change($order->supplier_id,$order->shop_id,$order->total_price,3,'退货返还');

            // 更新仓库库存
            $sql = "UPDATE tb_product_stock a,mf_shop_order_product b SET a.stock_num=a.stock_num + b.num,a.salable_num=a.salable_num+b.num
                    WHERE a.product_id=b.product_id and a.top_depot_id=b.top_depot_id and b.order_id=?";
            Db::update($sql,[$id]);
            // 更新库位库存
            $sql = "UPDATE mf_depot_product a,mf_shop_order_product b SET a.store_num=a.store_num + b.num
                    WHERE a.product_id=b.product_id and a.top_depot_id=b.top_depot_id AND b.order_id=?";
            Db::update($sql,[$id]);
            // 插入日志
            $sql = "INSERT INTO tb_product_stock_log (product_id,bid_price,add_num,do_type,do_type_id,remark,admin_id,user_id,supplier_id,top_depot_id,create_time)
                    SELECT product_id,bid_price,num,7 do_type,b.id do_type_id,'退货入库' remark,
                    {$session_admin_id} admin_id,{$session_user_id} user_id,a.supplier_id,a.top_depot_id,now()
                    from mf_shop_order_product a LEFT JOIN mf_shop_order b on a.order_id=b.id
                    WHERE a.order_id=?";
            Db::update($sql,[$id]);
            Db::commit();
        }catch (\Exception $e)
        {
            Db::rollBack();
            return arrayError($e->getMessage());
        }
        return arraySuccess("入库成功");
    }

    /**
     * 一键出库
     */
    public function oneKeySend($order_id = 0,$admin_id = 0)
    {
        $model = MfShopOrder::find($order_id);
        if ($model->status > 2)
            return arrayError("订单状态：".trans("lang.order_status_".$model->status));
        $products = $model->products;
        Db::beginTransaction();
        try {
            foreach ($products as $product){
                di(OrderService::class)->fenjian($product->id,$admin_id);
            }
            di(OrderService::class)->fenJianSubmit($order_id,$admin_id);
            foreach ($products as $product){
                di(OrderService::class)->fuJian($product->id);
            }
            di(OrderService::class)->fuJianSubmit($order_id,$admin_id);
            $res = $this->sendOrder($order_id,$admin_id);
            Db::commit();
            return $res;
        }catch (\Exception $e)
        {
            Db::rollBack();
            return arrayError($e->getMessage());
        }

    }

}