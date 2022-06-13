<?php


namespace Mwenju\Common\Service;


use Mwenju\Common\Utils\UtilsTool;
use Hyperf\DbConnection\Db;

class UserOrderProductService
{
   public static function getList($user_id = 0,$shop_id = 0,$keyword = '',$state = 0,$page = 1,$rows = 10)
    {
        $map = [];
        if($user_id > 0)
        {
            $map[] = ['b.user_id','=',$user_id];
        }
        if($shop_id > 0)
        {
            $map[] = ['b.shop_id','=',$shop_id];
        }
        if($state > 0)
        {
            $map[] = ['b.state','=',$state];
        }
        if (!empty($keyword))
        {
            $map[] = ["b.order_code|b.card_number|b.mobile",'like',"%$keyword%"];
        }
        return Db::table("mf_user_order_product as a")->selectRaw('a.*,b.order_code,c.list_img_path,b.state,b.card_number,b.mobile,b.create_time')
            ->leftJoin('mf_user_order as b','b.id','=','a.order_id')
            ->leftJoin('tb_product as c','c.id','=','a.product_id')
            ->where($map)
            ->orderBy("b.state","asc")
            ->limit($rows)
            ->offset($page)
            ->get()
            ->each(function ($item,$index){
                $item->list_img_path = UtilsTool::img_url($item->list_img_path);
                $item->state_str = trans("lang.order_state_".$item->state);
            });
    }

    public static function getTotal($shop_id = 0)
    {
        $sql = 'SELECT count(id) total,state from mf_user_order WHERE shop_id=? GROUP BY state';
        $res = Db::select($sql,[$shop_id]);
        $total = 0;
        $received_total = 0;
        $not_received_total = 0;
        foreach ($res as $v)
        {
            if ($v->state == 1){
                $not_received_total += $v->total;
            }
            if ($v->state == 3){
                $received_total += $v->total;
            }
            $total += $v->total;
        }

        return [$total,$not_received_total,$received_total];
    }
}