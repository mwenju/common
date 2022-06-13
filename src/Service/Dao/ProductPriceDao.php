<?php


namespace Mwenju\Common\Service\Dao;


use Mwenju\Common\Model\TbProductPrice;
use Mwenju\Common\Utils\UtilsTool;

class ProductPriceDao
{
    public function updateOrInsert(array $param,int $id):void
    {
        //区域价格
        $province_code  = $param['province_code']??'';
        $city_code      = $param['city_code']??'';
        $area_code      = $param['area_code']??'';
        $area_price     = $param['area_price']??0;

        TbProductPrice::where("product_id",$id)->get()->each(function ($item,$index) use ($id){
            $key = "PRICE_".$id."_".$item->province_code."_".$item->city_code."_".$item->area_code;
            UtilsTool::redis()->del($key);
        });
        TbProductPrice::where('product_id',$id)->delete();

        if(!empty($province_code))
        {
            $area_insert = [];
            for ($i=0;$i<count($province_code);$i ++)
            {
                $area_insert[] = [
                    'province_code'=>$province_code[$i],
                    'city_code'=>$city_code[$i] ? $city_code[$i] : 0,
                    'area_code'=>$area_code[$i] ? $area_code[$i] : 0,
                    'price'=>$area_price[$i] ? $area_price[$i] : 0,
                    'product_id'=>$id
                ];
            }

            TbProductPrice::insert($area_insert);
            foreach ($area_insert as $codes){
                $priceKey = "PRICE_".$id."_".$codes['province_code']."_".$codes['city_code']."_".$codes['area_code'];
                UtilsTool::redis()->set($priceKey,$codes['price']);
            }

        }
    }
}