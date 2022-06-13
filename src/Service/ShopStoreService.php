<?php


namespace Mwenju\Common\Service;


use Mwenju\Common\Utils\UtilsTool;
use Hyperf\DbConnection\Db;

class ShopStoreService
{
    public static function getInfo($shop_id = 0)
    {
        $shop_info = Db::table("mf_shop_store as a")->selectRaw("b.cname shop_name,c.face_img,a.face_img store_header_img,a.desction,b.addr,b.link_mobile")
            ->leftJoin('mf_shop as b','b.id','=','a.shop_id')
            ->leftJoin('mf_user as c','b.user_id','=','c.id')
            ->where('a.shop_id',$shop_id)
            ->where('a.audit_state',1)
            ->first();
        if($shop_info){
            $shop_info->face_img = UtilsTool::img_url($shop_info->face_img,'listh');
            $shop_info->store_header_img = UtilsTool::img_url($shop_info->store_header_img,'detail',false);
        }

        return  $shop_info;
    }
}