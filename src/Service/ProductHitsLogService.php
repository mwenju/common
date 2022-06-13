<?php


namespace Mwenju\Common\Service;


use Hyperf\DbConnection\Db;

class ProductHitsLogService
{
    public static function update($shop_id = 0,$device_code = '',$product_id = 0)
    {
        if($shop_id > 0)
        {
            $row = Db::select("select * from tb_product_hits_log where product_id=? and (shop_id=? or device_code=?) limit 1",
                [$product_id,$shop_id,$device_code]);
        }
        else
        {
            $row = Db::select("select * from tb_product_hits_log where product_id=? and device_code=? limit 1",
                [$product_id,$device_code]);
        }
        if($row)
        {
            $update['last_update_time'] = date("Y-m-d H:i:s");
            if($shop_id > 0 && $row[0]->shop_id == 0){
                $update['shop_id'] = $shop_id;
            }
            $update['hit_num'] = Db::raw("hit_num + 1");
            Db::table("tb_product_hits_log")->where("id",$row[0]->id)->update($update);
        }
        else
        {
            Db::table("tb_product_hits_log")->insert([
                'shop_id'=>$shop_id,
                'product_id'=>$product_id,
                'hit_num'=>1,
                'device_code'=>$device_code,
                'create_time'=>date("Y-m-d H:i:s"),
                'last_update_time'=>date("Y-m-d H:i:s")
            ]);
        }
    }
}