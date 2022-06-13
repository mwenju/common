<?php


namespace Mwenju\Common\Service;


use Mwenju\Common\Model\MfUserCouponList;
use Mwenju\Common\Model\MfUserOrder;
use Mwenju\Common\Utils\Time;
use Mwenju\Common\Utils\UtilsTool;
use Hyperf\DbConnection\Db;

class UserOrderService
{
    public function checkout($shop_id = 0,$card_number = '',$product_total_price = 0){
        if(empty($card_number))
            UtilsTool::exception("卡号不能为空！");
        if(empty($product_total_price) || $product_total_price == 0)
            UtilsTool::exception("请输入商品金额");

        ShopStoreApplyService::checkState($shop_id); //检测微店审核状态

        $UserCardService = new UserCardService();

        $card = $UserCardService->getInfoByNumber($card_number);
        //检测会员卡状态
        $UserCardService->checkState($card);
        //是否本地会员
        if($card->shop_id != $shop_id)
            UtilsTool::exception("您非本店会员哦！");

        $card_price = 0;

        $UserCouponListService = new UserCouponListService();

        list($coupon_list_id,$discount) = $UserCouponListService->getList($card->user_id,$card->shop_id,$product_total_price);

        $pay_price = ($product_total_price - $discount - $card->balance) <= 0 ? 0 : ($product_total_price - $discount - $card->balance);

        if($discount > 0)
        {
            if($product_total_price < $discount) {
                $discount = $discount - $product_total_price;
            }
        }

        if($card->balance > 0 && ($product_total_price - $discount) > 0){
            if(($product_total_price - $discount) <= $card->balance){
                $card_price = ($product_total_price - $discount);
            }else{
                $card_price = $card->balance;
            }
        }

        return [
            'card_number'=>$card->card_number,
            'cart_number'=>$card->card_number,
            'mobile'=>$card->mobile,
            'real_name'=>$card->real_name,
            'card_balance'=>$card->balance,
            'product_total_price'=>$product_total_price,
            'pay_price'=>$pay_price,
            'balance'=>$card_price,
            'discount'=>$discount,
            'coupon_list_id'=>$coupon_list_id,
            'user_id'=>$card->user_id,
            'shop_id'=>$card->shop_id,
            'add_integral_num'=>intval($pay_price)
        ];
    }

    public function submit(int $shop_id,$card_number,$product_total_price){
        $checkParam = $this->checkOut($shop_id,$card_number,$product_total_price);
        $UserCardService = new UserCardService();

        Db::beginTransaction();
        try {
            // 余额支付更新
            $UserCardService->orderBalancePay($card_number,$checkParam['user_id'],$checkParam['balance']);

            $order_id = Db::table("mf_user_order")->insertGetId([
                'shop_id'=>$shop_id,
                'order_code'=>UtilsTool::create_order_number($shop_id),
                'user_id'=>$checkParam['user_id'],
                'card_number'=>$checkParam['card_number'],
                'mobile'=>$checkParam['mobile'],
                'product_total_price' =>$checkParam['product_total_price'],
                'pay_price'=>$checkParam['pay_price'],
                'paid_price'=>$checkParam['pay_price'],
                'discount'=>$checkParam['discount'],
                'balance'=>$checkParam['balance'],
                'state'=>1,
                'create_time'=>date("Y-m-d H:i:s")
            ]);
            if($checkParam['coupon_list_id'] > 0){
                $updateNum =  Db::table("mf_user_coupon_list")->where("order_id",0)
                    ->where("id",$checkParam['coupon_list_id'])
                    ->where("is_delete",0)
                    ->update(['order_id'=>$order_id,'use_time'=>date("Y-m-d H:i:s")]);
                if($updateNum == 0){
                    UtilsTool::exception("优惠券使用失败");
                }
            }
            list($add_integral_num,$after_integral_num) = $UserCardService->integralUpdateByCardNumber($card_number,$checkParam['pay_price']);// 下单赠送积分
            Db::commit();
            return ['order_id'=>$order_id,'add_integral_num'=>$add_integral_num,'after_integral_num'=>$after_integral_num];
        }catch (\Exception $e){
            Db::rollback();
            UtilsTool::exception($e->getMessage());
        }
    }

    public function cancel($shop_id,$order_id){
        $order = MfUserOrder::where("id",$order_id)->where("shop_id",$shop_id)->first();
        if(!$order)
            UtilsTool::exception("订单不存在");
        Db::beginTransaction();
        try {
            if(Db::table("mf_user_order")->where("id",$order_id)->where("state",1)
                    ->update(['state'=>2,'cancel_time'=>date("Y-m-d H:i:s")]) == 0){
                UtilsTool::exception("订单更新失败");
            }
            if($order->balance > 0)
            {
                $UserCardService = new UserCardService();
                $UserCardService->orderCancel($shop_id,$order->user_id,$order->balance);
            }
            if($order->discount > 0){
                MfUserCouponList::where("order_id",$order_id)
                    ->update(['order_id'=>0]);
            }
            Db::commit();
        }catch (\Exception $e){
            Db::rollback();
            UtilsTool::exception($e->getMessage());
        }
    }

    public function getList($input = [],$rows = 30){
        $list = Db::table("mf_user_order as a")->selectRaw("a.*,b.mobile,b.shop_name")
            ->leftJoin('mf_user_card as b','b.card_number','=','a.card_number')
            ->orderBy("id","desc")
            ->paginate($rows);
        return $list;
    }

    public function getListByShopId($shop_id = 0,$input = [],$page = 1,$limit = 30){
        $map[] = ['a.shop_id','=',$shop_id];
        $map[] = ["a.state",'=',1];
        $month = !empty($input['month'])?$input['month']:date("Y-m-01");
        list($start_time,$end_time) = Time::month($month,false);
        $map[] = ["a.create_time",">=",$start_time];
        $map[] = ["a.create_time","<=",$end_time];
        $card_number = !empty($input['card_number'])?$input['card_number']:'';
        if(!empty($card_number))
        {
            $map[] = ["a.card_number",'=',$card_number];
        }
        $list = Db::table("mf_user_order as a")->selectRaw("a.*,b.mobile,b.shop_name")
            ->leftJoin('mf_user_card as b','b.card_number','=','a.card_number')
            ->where($map)
            ->orderBy("id","desc")
            ->limit($limit)
            ->offset($page)
            ->get();

        return $list;
    }

    public function getListByUserId($user_id = 0,$input = [],$page = 1,$limit = 30){
        $map[] = ['a.user_id','=',$user_id];
        $map[] = ["a.state",'=',1];
        $month = !empty($input['month'])?$input['month']:date("Y-m-01");
        list($start_time,$end_time) = Time::month($month,false);
        $map[] = ["a.create_time",">=",$start_time];
        $map[] = ["a.create_time","<=",$end_time];
        $card_number = !empty($input['card_number'])?$input['card_number']:'';
        if(!empty($card_number))
        {
            $map[] = ["a.card_number",'=',$card_number];
        }
        $list = Db::table("mf_user_order as a")->selectRaw("a.*,b.mobile,b.shop_name")
            ->leftJoin('mf_user_card as b','b.card_number','=','a.card_number')
            ->where($map)
            ->orderBy("id desc")
            ->limit($limit)
            ->offset($page)
            ->get();

        return $list;
    }

    public function getTotalByShopId($shop_id,$input = [])
    {
        $map[] = ["state",'=',1];
        $map[] = ['shop_id','=',$shop_id];
        $month = !empty($input['month'])?$input['month']:date("Y-m-01");
        list($start_time,$end_time) = Time::month($month,false);
        $map[] = ["create_time",">=",$start_time];
        $map[] = ["create_time","<",$end_time];
        $card_number = !empty($input['card_number'])?$input['card_number']:'';
        if(!empty($card_number))
        {
            $map[] = ["card_number",'=',$card_number];
        }
        return[
            MfUserOrder::where($map)->count(),
            MfUserOrder::where($map)->sum("paid_price")
        ];
    }
}