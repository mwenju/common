<?php


namespace Mwenju\Common\Service;


use Mwenju\Common\Model\ActSeckillOrder;
use Mwenju\Common\Model\ActSeckillProduct;
use Mwenju\Common\Model\TbProduct;
use Mwenju\Common\Model\TbProductStock;
use Mwenju\Common\Pojo\User;
use Mwenju\Common\Utils\Logger;
use Mwenju\Common\Utils\UtilsTool;
use Hyperf\DbConnection\Db;

class ActService
{
    public $token = '';
    public static function updateActProduct($product_id = 0,$top_depot_id = 1)
    {
        $res = [];
        $redis = redis();
        if(is_array($product_id))
        {
            $ids = $product_id;
        }else
        {
            if($product_id > 0)
            {
                $ids[] = $product_id;
            }else
            {
                $ids =  ActSeckillProduct::where("end_time",'>=',date("Y-m-d H:i:s"))->distinct()->pluck("product_id")->toArray();
            }
        }
        if(!$ids) return [];

        foreach ($ids as $id) {
            $res[] = $id;
            $act_list = self::getProductAct($id);

            $price = TbProduct::where("id",$id)->value("wholesale_price");

            if (!$act_list->isEmpty()) {
                $row = $act_list[0];

                $salable_num = TbProductStock::where('top_depot_id',$top_depot_id)->where('product_id',$id)->value('salable_num');

                if (strtotime($row->start_time) > time()) // 未开始
                {
                    $redis->setex("CURRENT_ACT_INFO_" . $id, time() - strtotime($row->start_time), json_encode($row));
                    $redis->setex("CURRENT_ACT_ING_" . $id, time() - strtotime($row->start_time), 0);
                    $redis->setex("CURRENT_PRICE_".$id,time()-strtotime($row->start_time),$price);
                    $redis->setex("CURRENT_STOCK_{$top_depot_id}_".$id,time()-strtotime($row->start_time),$salable_num);

                } else {
                    $price = $row->act_price;
                    $act_salable_num = $row->act_stock - $row->sold_stock;
                    $salable_num = $act_salable_num<$salable_num?$act_salable_num:$salable_num;
                    $redis->setex("CURRENT_PRICE_".$id,strtotime($row->end_time)-time(),$price);
                    $redis->setex("CURRENT_ACT_ING_" . $id, strtotime($row->end_time) - time(), $row->act_seckill_id);
                    $row->salable_num = $salable_num;
                    $redis->setex("CURRENT_ACT_INFO_" . $id, strtotime($row->end_time) - time(), json_encode($row));
                    $redis->setex("CURRENT_STOCK_{$top_depot_id}_".$id,strtotime($row->end_time)-time(),$salable_num);
                }

            }
            else{
//                $redis->set("CURRENT_PRICE_".$id,$price);
            }
        }
        return $res;
    }

    public static function getProductAct($product_id = 0)
    {
        return ActSeckillProduct::where("end_time",'>=',date("Y-m-d H:i:s"))->where("product_id",$product_id)
            ->orderBy("start_time","asc")->limit(1)
            ->get()->each(function ($item,$index){
                $item->tag_img = UtilsTool::img_url($item->tag_img);
            });
    }


    public static function currentPrice($price , $product_id = 0)
    {
        $redis = redis();
        $price = $redis->get("CURRENT_PRICE_".$product_id);
        if(strlen($price) > 0)
        {
            return (float)$price;
        }

        self::updateActProduct($product_id);
        return (float)$price;
    }

    public static function currentStock($salable_num,$product_id = 0,$top_depot_id = 1)
    {
        $redis = redis();
        $salable_num_cache = $redis->get("CURRENT_STOCK_{$top_depot_id}_".$product_id);
        if(strlen($salable_num_cache) > 0)
        {
            return (int)$salable_num_cache;
        }
//        $salable_num = TbProductStock::where('top_depot_id',$top_depot_id)->where('product_id',$product_id)->value('salable_num');
        return (int)$salable_num;
    }

    public static function setPrice($product_id = 0,$price = 0)
    {
        $key = "CURRENT_PRICE_{$product_id}";
        $redis = redis();
        $expire_time = $redis->ttl($key);
        if($expire_time > 0)
        {
            $redis->setex($key,$expire_time,$price);
        }
    }

    public static function setStock($product_id = 0,$stock = 0,$top_depot_id = 1)
    {
        $key = "CURRENT_STOCK_{$top_depot_id}_{$product_id}";
        $redis = redis();
        $expire_time = $redis->ttl($key);
        if($expire_time > 0)
        {
            $redis->setex($key,$expire_time,$stock);
        }
    }

    public static function productDetail(&$detail,$user)
    {
        $detail['wholesale_price']  = ProductService::getAreaPrice($detail['wholesale_price'],$detail['product_id'],$user);
        $detail['salable_num']      = self::currentStock($detail['salable_num'],$detail['product_id'],$detail['top_depot_id']);
        $detail['act_list']         = self::currentActList($detail['product_id'],$user);
    }

    public static function productList(&$list,$user)
    {
        $top_depot_id = 1;
        foreach ($list as $k=>$item)
        {
            $list[$k]->act_list = [];
        }
        foreach ($list as $k=>$detail)
        {
            if(isset($detail->wholesale_price))
            {
                $curPrice = ProductService::getAreaPrice($detail->wholesale_price,$detail->product_id,$user);
                $list[$k]->wholesale_price = $curPrice;

            }
            $list[$k]->salable_num    = self::currentStock($detail->salable_num,$detail->product_id,$top_depot_id);
            $list[$k]->act_list       = self::currentActList($detail->product_id,$user);
        }
    }

    public static function checkLimit($shop_id = 0,$product_id = 0,$add_num = 1,$top_depot_id = 1)
    {
        $redis = redis();
        $act_seckill_id = $redis->get("CURRENT_ACT_ING_".$product_id);
        if($act_seckill_id > 0)
        {
            $actNum = ActSeckillOrder::where("shop_id",$shop_id)->where("act_seckill_id",$act_seckill_id)->where("is_delete",0)->sum("buy_num");
            $actInfo = json_decode($redis->get("CURRENT_ACT_INFO_".$product_id),true);

            $todayActNum = ActSeckillOrder::where("shop_id",$shop_id)
                ->whereTime("create_time",'>=',date("Y-m-d 00:00:00"))
                ->where("act_seckill_id",$act_seckill_id)
                ->where("is_delete",0)
                ->sum("buy_num");

            if($actInfo['limit_day_num'] > 0 && ($add_num+$todayActNum) > $actInfo['limit_day_num']){
                Logger::init()->error("活动每天数量限制:".$actInfo['limit_day_num']."|".($todayActNum+$add_num));
                UtilsTool::exception("活动每天数量限制:".$actInfo['limit_day_num']);
            }

            if($actInfo['limit_total_num'] > 0 && ($actNum+$add_num) > $actInfo['limit_total_num']){
                Logger::init()->error("活动每人限制:".$actInfo['limit_total_num']."|".($actNum+$add_num));
                UtilsTool::exception("活动每人限制：".$actInfo['limit_total_num']);
            }
        };
    }

    /**
     * 当前最近的活动
     * @param int $product_id
     * @param User $user;
     * @return array
     */
    public static function currentActList(int $product_id,User $user)
    {
        $list = [];
        $redis = redis();
        $act_row = $redis->get("CURRENT_ACT_INFO_".$product_id);
        $salable_num_cache = $redis->get("CURRENT_STOCK_{$user->getDepotId()}_".$product_id);
        if($act_row)
        {
            $data = json_decode($act_row,true);
            $list[] = [
                'act_name'=>$data['act_name'],
                'act_price'=>ProductService::getAreaPrice($data['act_price'],$product_id,$user),
                'act_stock'=>$salable_num_cache,
                'start_time'=>date("Y-m-d",strtotime($data['start_time'])),
                'end_time'=>date("Y-m-d",strtotime($data['end_time'])),
                'tag_img'=>$data['tag_img'],
            ];
        }
        return $list;
    }

    public static function currentAct($product_id = 0,$shop_id = 0)
    {
        $redis = redis();
        $act_seckill_id = $redis->get("CURRENT_ACT_ING_".$product_id);
        if($act_seckill_id > 0){
            $actNum = ActSeckillOrder::where("shop_id",$shop_id)->where("act_seckill_id",$act_seckill_id)->where("is_delete",0)->sum("buy_num");
            $actInfo = json_decode($redis->get("CURRENT_ACT_INFO_".$product_id),true);

            $todayActNum = ActSeckillOrder::where("shop_id",$shop_id)
                ->whereTime("create_time",'>=',date("Y-m-d 00:00:00"))
                ->where("act_seckill_id",$act_seckill_id)
                ->where("is_delete",0)
                ->sum("buy_num");

            if($actInfo['limit_total_num'] > 0 && ($actNum) >= $actInfo['limit_total_num']){
                $actInfo['salable_num'] = 0;
            }else{
                $actInfo['salable_num'] = $actInfo['limit_total_num'] - $actNum;
            }

            if($actInfo['limit_day_num'] > 0 && ($todayActNum) >= $actInfo['limit_day_num']){
                $actInfo['salable_num'] = 0;
            }else{
                $actInfo['salable_num'] = $actInfo['limit_day_num'] - $todayActNum;
            }
            return $actInfo;
        }
        return [];
    }

    public static function setActList($product_id = 0)
    {
        $redis = redis();
        $redis->del("CURRENT_PRICE_".$product_id);
        $redis->del("CURRENT_ACT_ING_".$product_id);
        $redis->del("CURRENT_ACT_INFO_".$product_id);
        self::updateActProduct($product_id);
    }

    /**
     * 作废活动订单，活动库存还原
     * @param int $order_id
     */
    public static function cancelActOrder($order_id = 0)
    {
        $actGoods = ActSeckillOrder::where("order_id",$order_id)->where("is_delete",0)->get();
        if($actGoods->isNotEmpty())
        {
            foreach ($actGoods as $goods)
            {
                Db::table("act_seckill_product")->where("act_seckill_id",$goods->act_seckill_id)
                    ->where("product_id",$goods->product_id)->decrement("sold_stock",$goods->buy_num);
                self::updateActProduct($goods->product_id);
            }
        }
        Db::table("act_seckill_order")->where("order_id",$order_id)->update(['is_delete'=>1]);
    }
}