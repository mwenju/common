<?php


namespace Mwenju\Common\Service;


use Mwenju\Common\Utils\UtilsTool;
use Hyperf\DbConnection\Db;

class AdItemsService
{
    public static function getListByType($type_id = 0)
    {
        return Db::select("select * from mf_ad_items where ad_type_id = ? and start_time<=now() and end_time>=now() and is_hide=0 order by sort asc",[$type_id]);
    }

    public static function getHomeAdList()
    {
        $res = Db::table("mf_ad_items as a")->selectRaw("a.ad_type_id,a.items_name,a.link_url,a.image_path")
            ->leftJoin('mf_ad_type as b','b.id','=','a.ad_type_id')
            ->where("a.is_hide",0)
            ->where("b.is_hide",0)
            ->where("a.start_time",'<',date("Y-m-d H:i:s"))
            ->where("a.end_time",'>=',date("Y-m-d H:i:s"))
            ->where('b.tag','home')
            ->orderBy('b.sort')
            ->orderBy('a.sort')
            ->get()->each(function ($item,$index){
                $item->image_path = UtilsTool::img_url($item->image_path);
            });
        if(!$res) return [];
        $list = [];
        foreach ($res as $v)
        {
            $list[$v->ad_type_id][] = $v;
        }
        $ls = [];
        $i = 0;
        foreach ($list as $k=>$v)
        {
            $ls[$i] = $list[$k];
            $i ++;
        }
        return $ls;
    }
}