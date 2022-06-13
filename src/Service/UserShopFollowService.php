<?php


namespace Mwenju\Common\Service;


use Mwenju\Common\Model\MfUserShopFollow;
use Mwenju\Common\Utils\UtilsTool;
use Hyperf\DbConnection\Db;

class UserShopFollowService
{
    public function getUserFollowList($user_id = 0)
    {
        $map[] = ['a.user_id','=',$user_id];
        return Db::table("mf_user_shop_follow as a")->selectRaw("a.shop_id,a.user_id,b.cname shop_name,b.link_mobile mobile,
            b.addr,u.face_img,c.face_img store_header_img,b.desction")
            ->leftJoin('mf_shop as b','a.shop_id','=','b.id')
            ->leftJoin('mf_shop_store as c','c.shop_id','=','b.id')
            ->leftJoin('mf_user as u','u.id','=','b.user_id')
            ->where($map)->get()->each(function ($item,$index){
                $item->face_img = UtilsTool::img_url($item->face_img);
                $item->store_header_img = UtilsTool::img_url($item->store_header_img,'',false);
            });
    }

    public function add($user_id = 0,$shop_id = 0)
    {
        if($user_id == 0 || $shop_id == 0) return;
        if(!MfUserShopFollow::where("user_id",$user_id)->where("shop_id",$shop_id)->exists())
        {
            MfUserShopFollow::insert([
                'user_id'=>$user_id,
                'shop_id'=>$shop_id,
                'create_time'=>date("Y-m-d H:i:s"),
            ]);
        }
    }
}