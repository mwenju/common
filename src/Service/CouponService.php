<?php


namespace Mwenju\Common\Service;


use _PHPStan_76800bfb5\Nette\Neon\Exception;
use Mwenju\Common\Model\MfShop;
use Mwenju\Common\Model\MfShopCart;
use Mwenju\Common\Model\MfShopOrder;
use Mwenju\Common\Model\MfShopOrderProduct;
use Mwenju\Common\Model\MfUser;
use Mwenju\Common\Model\TbCouponList;
use Mwenju\Common\Model\TbCouponLog;
use Mwenju\Common\Model\TbCouponRange;
use Mwenju\Common\Model\TbCouponTemplate;
use Mwenju\Common\Model\TbProduct;
use Mwenju\Common\Model\TbProductType;
use Mwenju\Common\Model\TbRechargeOrder;
use Mwenju\Common\Pojo\User;
use Mwenju\Common\Utils\Logger;
use Mwenju\Common\Utils\Sms;
use Mwenju\Common\Utils\Time;
use Mwenju\Common\Utils\UtilsTool;
use Hyperf\AsyncQueue\Annotation\AsyncQueueMessage;
use Hyperf\Database\Model\Builder;
use Hyperf\DbConnection\Db;

class CouponService
{
    public $bind_success = false;

    public function bind($shop_id = 0,$coupon_id = 0)
    {
        $c_info = TbCouponTemplate::where("id",$coupon_id)->first();
        if(!$c_info) throw new Exception("红包不存在");
        $c_count = TbCouponList::where('coupon_template_id',$coupon_id)->where("shop_id",'>',0)->count();
        if($c_count >= $c_info['create_num']) throw new Exception($c_info['coupon_name']."红包已领完");
        $c_count = TbCouponList::where('coupon_template_id',$coupon_id)->where("shop_id",0)->count();
        $now = date("Y-m-d H:i:s");
        if($c_count > 0)
        {
            Db::table("tb_coupon_list")->where("coupon_template_id",$coupon_id)
                ->where("shop_id",0)
                ->limit(1)
                ->update(['shop_id'=>$shop_id,'bind_time'=>$now]);
            $this->bind_success = true;
        }
        else
        {
            $batch_num = $c_info['batch_num']+1;
            $insertData = ['coupon_template_id'=>$coupon_id,'create_time'=>$now,'batch_num'=>$batch_num,'shop_id'=>$shop_id,'bind_time'=>$now];
            Db::table("tb_coupon_list")->insert($insertData);
        }
        Db::table("tb_coupon_template")->where("id",$coupon_id)->increment('bind_num',1);
        return $c_info;
    }

    /**
     * 验证手机号
     * @param string $mobile
     */
    private function checkMobile($mobile = '')
    {
        $u_info = MfUser::where('mobile',$mobile)->first();
        if(!$u_info) {
            throw new Exception($mobile."用户不存在");
        }
        $s_info = MfShop::where("user_id",$u_info->id)->first();
        if(!$s_info) {
            throw new Exception("未开通店铺");
        }
        if($s_info['status'] != 1) {
            throw new Exception($u_info->mobile."当前店铺还未审核通过");
        }
    }

    /**
     * 根据发放规则绑定
     * @param $shop_id
     * @param $tpl
     * @param string $mobile
     * @return bool
     * @throws Exception
     */
    private function bindByTpl($shop_id,$tpl,$mobile='')
    {
        if(empty($mobile))
        {
            $res = Db::select("select b.mobile from mf_shop a left join mf_user b on b.id=a.user_id where a.id=?",[$shop_id]);
            $mobile = $res[0]->mobile;
        }
        Db::beginTransaction();
        try {
            $package_arr = json_decode($tpl->package_json,true);
            $coupon_price = 0;
            foreach ($package_arr as $coupon_id=>$num)
            {
                for($i=0;$i<$num;$i ++)
                {
                    $c_info = $this->bind($shop_id,$coupon_id);
                    $coupon_price += $c_info['coupon_money'];
                }
            }
            Db::commit();
            $err_code = 0;
            $err_msg = '';
            Db::table("tb_coupon_log")->insert([
                'act_name'=>$tpl->coupon_name,
                'mobile'=>$mobile,
                'shop_id'=>$shop_id,
                'coupon_template_id'=>$tpl->id,
                'package_json'=>$tpl->package_json,
                'create_time'=>date('Y-m-d H:i:s'),
                'admin_id'=>0,
                'err_code'=>$err_code,
                "err_msg"=>$err_msg,
                "coupon_price"=>$coupon_price
            ]);
            //短信通知
            Sms::send($mobile,'COUPON_SEND_SUCCESS',['price'=>$coupon_price]);
            return true;
        }
        catch(\Exception $e)
        {
            Db::rollback();
            $err_code = $e->getCode();
            $err_msg = $e->getMessage();
            Db::table("tb_coupon_log")->insert([
                'act_name'=>$tpl['coupon_name'],
                'mobile'=>$mobile,
                'shop_id'=>$shop_id,
                'coupon_template_id'=>$tpl['id'],
                'package_json'=>$tpl['package_json'],
                'create_time'=>date('Y-m-d H:i:s'),
                'admin_id'=>0,
                'err_code'=>$err_code,
                "err_msg"=>$err_msg,
                "coupon_price"=>$coupon_price
            ]);
            UtilsTool::exception($e->getMessage());
        }
        return false;
    }

    /**
     * 手机号发放红包
     * @param string $mobile
     * @param int $coupon_id
     * @param bool $Async
     * @throws Exception
     */
    public function bindByMobile($mobile="",$coupon_id = 0,$Async = false)
    {

    }

    public function orderUse($order_id=0,$coupon_id = 0)
    {
        Db::table("tb_coupon_list")->where("id",$coupon_id)
            ->update(['order_id'=>$order_id,'use_time'=>date("Y-m-d H:i:s")]);
    }

    /**
     * 根据购物车计算优惠金额
     * @param ShopCartService $cart
     * @param int $coupon_id
     * @param User $user
     * @return \Hyperf\Utils\HigherOrderCollectionProxy|\Hyperf\Utils\HigherOrderTapProxy|int|mixed|string|void
     * @throws \Hyperf\Di\Exception\Exception
     */
    public function discountPrice($cart = null,$coupon_id = 0,$user = null)
    {
        $c_list_info = TbCouponList::where('id',$coupon_id)
            ->where("shop_id",$user->getShopId())
            ->first();

        if(!$c_list_info) {
            UtilsTool::exception("红包不存在");
        }

        if($c_list_info->order_id > 0) UtilsTool::exception("红包已使用");

        $c_info = TbCouponTemplate::find($c_list_info->coupon_template_id);

        $is_ok = strpos($c_info->device_type,$user->getDeviceType()) !== false;

        if(!$is_ok) {
            UtilsTool::exception("当前设备不能用此优惠券");
        }
        if(time() > strtotime($c_info->end_time)) UtilsTool::exception("红包已过期");

        $product_total_price = 0;
        if($c_info->range == 'all')
        {
            $product_total_price = $cart->totalPrice($user->getShopId());
        }
        else
        {
            $product_ids = [];
            if($c_info->range == 'product')
            {
                $product_ids = TbCouponRange::where("coupon_template_id",$c_info['id'])->pluck("range_id")->toArray();
            }
            else
            {
                $typeids = [];

                $range_ids = TbCouponRange::where("coupon_template_id",$c_info->id)->pluck("range_id")->toArray();

                if($c_info->range == 'type')
                {
                    $typeids = TbProductType::getChild($range_ids[0],$typeids);
                }
                $product_ids = TbProduct::when($c_info,function ($query,$range) use ($range_ids,$typeids){
                    if ($range == 'brand'){
                        return $query->whereIn("brand_id",$range_ids);
                    }
                    if ($range == 'supplier'){
                        return $query->whereIn("supplier_id",$range_ids);
                    }
                    if ($range == 'type'){
                        return $query->whereIn("product_type_id",$typeids);
                    }
                })->pluck("id")->toArray();
            }
            $cart_product_ids = [];
            foreach ($cart->items(true,$user) as $pid=>$c)
            {
                $cart_product_ids[] = $pid;
            }
            if(count(array_intersect($product_ids,$cart_product_ids)) == 0){
                Logger::init()->error("coupon_error:".json([$product_ids,$cart_product_ids]));
                UtilsTool::exception("购物车商品不能参与使用此优惠券");
            }

            foreach ($cart->items(true,$user) as $pid=>$c)
            {
                if(in_array($c->product_id,$product_ids))
                {
                    $product_total_price += $c->total_price;
                }
            }
        }
        if($product_total_price >= $c_info->min_price)
        {
            return $product_total_price>=$c_info->coupon_money?$c_info->coupon_money:$product_total_price;
        }
        Logger::init()->error("coupon_error:".json([$c_info->min_price,$product_total_price]));
        UtilsTool::exception("商品金额至少满".$c_info->min_price.'元才能使用哦');
        return 0;
    }

    /**
     * 根据购物车获取可用红包
     * @param ShopCartService $cart
     * @param $coupon_list
     * @param bool $unset
     * @param User $user
     * @return array
     */
    public function check(?ShopCartService $cart,&$coupon_list,$unset = false,$user = null)
    {
        if(count($coupon_list) == 0) return [];

        foreach ($coupon_list as $v)
        {
            $coupon_template_id[] = $v->coupon_template_id;
        }
        $coupon_template_rows = TbCouponTemplate::whereIn("id",$coupon_template_id)->get();

        $cart_total_price  = $cart->totalPrice($user->getShopId());

        foreach ($coupon_template_rows as $c_info)
        {
            $is_ok = strpos($c_info->device_type,$user->getDeviceType()) !== false;

            if(!$is_ok)
            {
                $temp[$c_info->id] = 0;
                continue;
            }
            if(strtotime($c_info->end_time) < time())
            {
                $temp[$c_info->id] = 0;
                continue;
            }
            $product_total_price = 0;
            $product_ids = [];
            if($c_info->range == 'all')
            {
                $product_total_price = $cart_total_price;
            }
            else
            {
                $product_ids = $this->getProductIdsByRange($c_info);

                foreach ($cart->items(true,$user) as $pid=>$c)
                {
                    if(in_array($c->product_id,$product_ids))
                    {
                        $product_total_price += $c->total_price;
                    }
                }
            }

            if($product_total_price >= $c_info->min_price)
            {
                $temp[$c_info->id] = 1;
            }
            else
            {
                $temp[$c_info->id] = 0;
            }
            if($c_info->range != 'all') {
                foreach ($cart->items(true,$user) as $pid=>$c)
                {
                    $cart_product_ids[] = $pid;
                }
                if (count(array_intersect($product_ids, $cart_product_ids)) == 0) {
                    $temp[$c_info->id] = 0;
                }
            }
        }
        $ok_list = [];
        foreach ($coupon_list as $k=>$v)
        {
            if($unset && $temp[$v->coupon_template_id]==1) {
                $coupon_list[$k]->usable = $temp[$v->coupon_template_id];
                $ok_list[] = $coupon_list[$k];
                continue;
            }
            $coupon_list[$k]->usable = $temp[$v->coupon_template_id];
        }
        if($unset) return $ok_list;
        return $coupon_list;
    }

    /**
     * 根据订单规则发放
     */
    public function bindByOrder($order_id = 0,$order_code = '')
    {
        $order_info = Db::table('mf_shop_order as a')
            ->leftJoin('mf_user as b','b.id','=','a.user_id')
            ->selectRaw("a.*,b.mobile user_mobile")
            ->when($order_id,function ($query,$order_id){
                return $query->where("a.id",$order_id);
            })
            ->when($order_code,function ($query,$order_code){
                return $query->where("a.order_code",$order_code);
            })
            ->first();
        if (!$order_info){
            throw new Exception("订单不存在");
        }

        if($order_info->coupon_bind_id > 0){
            return TbCouponTemplate::where("id",$order_info->coupon_bind_id)->first();
        }elseif($order_info->coupon_bind_id < 0)
        {
            return '';
        }
        $coupon_template_id = -1;
        $coupon_template_rows = TbCouponTemplate::where("start_time",'<=',date("Y-m-d H:i:s"))
            ->where("end_time",'>',date("Y-m-d H:i:s"))
            ->where('tpl_mode',1)
            ->get();
        $temp = [];
        foreach ($coupon_template_rows as $c_info)
        {
            $is_ok = strpos($c_info->device_type,$order_info->device_type) !== false;
            if(!$is_ok)
            {
                continue;
            }
            $product_total_price = 0;
            if($c_info->range == 'all')
            {
                $product_total_price = $order_info->product_total_price;
            }
            else
            {
                $product_ids = $this->getProductIdsByRange($c_info);

                foreach (MfShopOrderProduct::where("order_id",$order_id)->get() as $pid=>$c)
                {
                    if(in_array($c->product_id,$product_ids))
                    {
                        $product_total_price += $c->price * $c->num;
                    }
                }
            }

            if($product_total_price >= $c_info->min_price)
            {
                $temp[] = ['package'=>$c_info->package_json,'coupon_name'=>$c_info->coupon_name,'coupon_template_id'=>$c_info->id,'min_price'=>$c_info->min_price];
            }
        }
        if(count($temp) > 0)
        {
            $max_temp = UtilsTool::array_sort($temp,'min_price','desc'); // 金额最大排序
            $i = 0;
            foreach ($max_temp as $t)
            {
                if($i > 0) break;
                $logData = [
                    'act_name' => $t['coupon_name'],
                    'mobile' => $order_info->user_mobile,
                    'shop_id' => $order_info->shop_id,
                    'coupon_template_id' => $t['coupon_template_id'],
                    'package_json' => $t['package'],
                    'create_time' => date("Y-m-d H:i:s")
                ];
                Db::beginTransaction();
                try
                {
                    foreach (json_decode($t['package']) as $coupon_id => $num) {
                        for ($i = 0; $i < $num; $i++) {
                            $this->bind($order_info->shop_id, $coupon_id);
                        }
                    }

                    Db::commit();
                    $logData['err_msg'] = "发放成功";
                    Db::table("tb_coupon_log")->insert($logData);
                    $coupon_template_id = $t['coupon_template_id'];
                }catch (\Exception $e)
                {
                    Db::rollback();
                    Logger::init()->error($e->getMessage());
                    $logData['err_code'] = $e->getCode();
                    $logData['err_msg'] = $e->getMessage();
                    Db::table("tb_coupon_log")->insert($logData);
                }
                $i ++;
            }

        }
        Db::table("mf_shop_order")->where("id",$order_id)->update(['coupon_bind_id'=>$coupon_template_id]);
        if($coupon_template_id > 0)
        {
            return TbCouponTemplate::where("id",$coupon_template_id)->first();
        }
        return '';
    }

    /**
     * 注册送红包
     */
    public function bindByReg($shop_id = 0,$mobile = '',$device_type = '')
    {
        $coupon_template_rows = TbCouponTemplate::where("start_time",'<=',date("Y-m-d H:i:s"))
            ->where("end_time",'>',date("Y-m-d H:i:s"))
            ->where('tpl_mode',4)
            ->get();
        if($coupon_template_rows->isEmpty()) return;
        foreach ($coupon_template_rows as $row)
        {
            $is_ok = strpos($row->device_type,$device_type) !== false;
            if($is_ok)
            {
                $this->bindByTpl($shop_id,$row,$mobile);
            }
        }
    }

    /**
     * 领取
     * @param int $shop_id
     * @param int $id
     * @throws \Exception
     */
    public function get($shop_id = 0,$id = 0)
    {
        $row = TbCouponTemplate::find($id);
        if(!$row) UtilsTool::exception("ID有误");
        if($row['tpl_mode'] != 3) UtilsTool::exception("优惠券有误");
        if(strtotime($row['start_time']) > time()) UtilsTool::exception("活动还未开始");
        if(strtotime($row['end_time']) < time()) UtilsTool::exception("活动已经结束");
        $rule = json_decode($row['rule_json'],true);

        if($rule['day_num'] > 0)
        {
            list($start,$end) = Time::today();
            $taday_num = TbCouponLog::where("coupon_template_id",$id)
                ->where("shop_id",$shop_id)
                ->where("err_code",0)
                ->whereBetween("create_time",[$start,$end])
                ->count();
            if($taday_num >= $rule['day_num']) UtilsTool::exception("今天已领，明天再来吧");
        }
        if($rule['total_num'] > 0)
        {
            $total_num = TbCouponLog::where("coupon_template_id",$id)
                ->where("shop_id",$shop_id)
                ->where("err_code",0)
                ->count();
            if($total_num >= $rule['total_num']) UtilsTool::exception("领取次数已满，下次再来吧");
        }
        $this->bindByTpl($shop_id,$row);
    }

    /**
     * 充值赠送
     * @param int $order_id
     * @param string $order_code
     * @return bool
     * @throws Exception
     * @throws \Hyperf\Di\Exception\Exception
     */
    public function bindByRecharge($order_id = 0,$order_code = '')
    {
        if($order_id > 0)
        {
            $order = TbRechargeOrder::where("id",$order_id)->where("is_delete",0)->first();
        }elseif (!empty($order_code))
        {
            $order = TbRechargeOrder::where("order_code",$order_code)->where("is_delete",0)->first();
        }
        else {
            UtilsTool::exception("订单ID或订单号不能全部为空");
        }
        if(!$order)
        {
            UtilsTool::exception("订单不存在");
        }
        if($order->coupon_bind_id > 0){
            return TbCouponTemplate::where("id",$order->coupon_bind_id)->first();
        }elseif ($order->coupon_bind_id < 0)
        {
            return '';
        }
        $recharge_money = $order->money;
        $shop_id = $order->shop_id;
        $coupon_template_id = -1;
        $clist = TbCouponTemplate::where("tpl_mode",5)->where("min_price",$recharge_money)
            ->where("is_delete",0)
            ->where("end_time",">",date("Y-m-d H:i:s"))
            ->where("start_time","<",date("Y-m-d H:i:s"))
            ->get();
        $cinfo = '';
        if($clist)
        {
            foreach ($clist as $item)
            {
                if($this->bindByTpl($shop_id,$item))
                {
                    $this->bind_success = true;
                    $coupon_template_id = $item->id;
                    $cinfo = $item;
                }
            }
        }
        else{
            Logger::init()->error("没有有效的充值活动:");
        }
        $order->coupon_bind_id = $coupon_template_id;
        $order->save();
        return $cinfo;
    }

    public function smsNotice()
    {
        $redis = redis();
        $n = $redis->get("COUPON_IS_SEND");
        if($n) return $n;

        $sql = 'SELECT a.shop_id,b.end_time,d.mobile,b.coupon_money
                from tb_coupon_list a 
                LEFT JOIN tb_coupon_template b on b.id=a.coupon_template_id
                LEFT JOIN mf_shop c on c.id=a.shop_id
                LEFT JOIN mf_user d on d.id=c.user_id
                where shop_id>0 and order_id=0 and a.is_delete=0
                and NOW()<DATE_SUB(b.end_time,INTERVAL 1 DAY)
                and NOW()>DATE_SUB(b.end_time,INTERVAL 2 DAY)';

        $res = Db::query($sql);
        $i = 0;
        foreach ($res as $v){
            SmsService::send($v['mobile'],"COUPON_END_TOMORROW",['price'=>$v['coupon_money']]);
            $i ++;
        }
        $sql = 'SELECT a.shop_id,b.end_time,d.mobile,b.coupon_money
                from tb_coupon_list a 
                LEFT JOIN tb_coupon_template b on b.id=a.coupon_template_id
                LEFT JOIN mf_shop c on c.id=a.shop_id
                LEFT JOIN mf_user d on d.id=c.user_id
                where shop_id>0 and order_id=0 and a.is_delete=0
                AND NOW()<b.end_time
                and NOW()>DATE_SUB(b.end_time,INTERVAL 1 DAY)';

        $res = Db::query($sql);
        foreach ($res as $v){
            SmsService::send($v['mobile'],"COUPON_END_TODAY",['price'=>$v['coupon_money']]);
            $i ++;
        }
        list($startTime,$endTime) = Time::today();
        $redis->setex("COUPON_IS_SEND",$endTime-time(),$i);
        return $i;
    }

    /**
     * @param $couponInfo
     * @return array
     */
    private function getProductIdsByRange($couponInfo):array
    {
        if($couponInfo->range == 'product')
        {
            $product_ids = TbCouponRange::where("coupon_template_id",$couponInfo->id)->pluck("range_id")->toArray();
        }
        else
        {
            $range_ids = TbCouponRange::where("coupon_template_id",$couponInfo->id)->pluck("range_id");
            $product_ids = Db::table("tb_product")
                ->when($couponInfo->range,function ($query,$range) use ($range_ids){
                    if ($range == 'brand'){
                        return $query->whereIn('brand_id',$range_ids);
                    }elseif($range == 'supplier'){
                        return $query->whereIn('supplier_id',$range_ids);
                    }elseif($range == 'type'){
                        $typeids = [];
                        $typeids = ProductService::getChild($range_ids[0],$typeids);
                        return $query->whereIn('product_type_id',$typeids);
                    }
                })->pluck("id")->toArray();
        }
        return $product_ids;
    }
}