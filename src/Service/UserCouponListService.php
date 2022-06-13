<?php


namespace Mwenju\Common\Service;


use Mwenju\Common\Model\MfUserCouponTemplate;
use Mwenju\Common\Utils\Logger;
use Hyperf\DbConnection\Db;

class UserCouponListService
{

    public function getList($user_id,$shop_id,$product_total_price = 0){

        $discount = 0;
        $id = 0;
        $res = Db::table('mf_user_coupon_list as a')->selectRaw("a.id,b.coupon_name,b.coupon_money,b.min_price")
            ->leftJoin('mf_user_coupon_template as b','b.id', '=','a.coupon_template_id')
            ->where('a.user_id',$user_id)
            ->where("a.shop_id",$shop_id)
            ->where('a.is_delete',0)
            ->where('a.order_id',0)
            ->where("b.start_time",'<=',date("Y-m-d H:i:s"))
            ->where("b.end_time",'>',date("Y-m-d H:i:s"))
            ->where("b.min_price",'<=',$product_total_price)
            ->orderBy("b.end_time","desc")
            ->get();
        if($res->isNotEmpty()){
            foreach ($res as $v){
                $priceList[$v->id] = $v->coupon_money;
                $list[abs($product_total_price - $v->coupon_money)] = $v->id;
            }
            ksort($list);
            foreach ($list as $k=>$v){
                $id = $v;
                $discount = $priceList[$v];
                break;
            }
        }
        return [$id,$discount];
    }

    public function bind($user_id = 0,$coupon_template_id = 0){
        $couponTemp = MfUserCouponTemplate::find($coupon_template_id);
        if(!$couponTemp) {
            Logger::init()->error("优惠券不存在:".$coupon_template_id);
        }
        if($couponTemp->create_num < $couponTemp->bind_num + 1){
            Logger::init()->error("优惠券已送完:".$coupon_template_id);
            return;
        }
        $now = date("Y-m-d H:i:s");
        Db::table("mf_user_coupon_list")->insert([
            'coupon_template_id'=>$coupon_template_id,
            'coupon_code'=>"",
            'bind_time'=>$now,
            'user_id'=>$user_id,
            'create_time'=>$now,
        ]);
        $upNum = Db::table("mf_user_coupon_templage")->where("id",$coupon_template_id)
            ->where("bind_num",$couponTemp->bind_num)
            ->increment("bind_num",1);
        if($upNum == 0){
            Logger::init()->error("优惠券已送完:".$coupon_template_id);
        }
    }
}