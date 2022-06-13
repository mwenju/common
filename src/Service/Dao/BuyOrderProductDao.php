<?php


namespace Mwenju\Common\Service\Dao;


use Mwenju\Common\Model\TbBuyOrderProduct;
use Mwenju\Common\Model\TbProduct;
use Mwenju\Common\Model\TbSupplier;
use Mwenju\Common\Utils\Logger;
use Hyperf\Database\Model\Builder;
use Hyperf\DbConnection\Db;

class BuyOrderProductDao extends Base
{
    public function getList($param = [])
    {
        $id             = $param['id']??0;
        $supplier_id    = $param['supplier_id']??"";
        $top_depot_id   = $param['top_depot_id']??"";
        $bar_code       = $param['bar_code']??"";
        $keyword        = $param['keyword']??"";
        $receive_status = $param['receive_status']??"";
        $map = [];
        if (strlen($id) > 0){
            $map[] = ['buy_order_id','=',$id];
        }
        if (strlen($supplier_id) > 0 && $id == 0){
            $map[] = ['supplier_id','=',$supplier_id];
        }
        if (strlen($top_depot_id) > 0){
            $map[] = ['top_depot_id','=',$top_depot_id];
        }
        if (!empty($bar_code)){
            $map[] = ['bar_code','=',$bar_code];
        }
        if(strlen($receive_status) > 0)
        {
            if ($receive_status == 0){
                $map[] = ['receive_num','<',Db::raw("buy_num")];
            }else{
                $map[] = ['receive_num','=',Db::raw("buy_num")];
            }
        }
        $total_buy_price = 0;
        $total_buy_num = 0;

        $query = TbBuyOrderProduct::where($map);
        if (!empty($keyword))
        {
            $query->where(function(Builder $q) use ($keyword){
                return $q->where("product_name",'like',"%{$keyword}%")
                    ->orWhere("art_no",'like',"%{$keyword}%")
                    ->orWhere("bar_code",'like',"%{$keyword}%");
            });
        }
        $query->orderBy("id",'asc');
        $list   = $query->get();
        $total  = $query->count();
        foreach ($list as $k=>$item)
        {
            $total_buy_price += $item->total_buy_price;
            $total_buy_num   += $item->buy_num;
        }

        $footer[] = ['total_buy_price'=>round($total_buy_price,2),'buy_num'=>$total_buy_num];
        return [$total,$list,$footer];
    }

    public function getInfo($param = [])
    {
        $product_id     = $param['product_id']??0;
        $buy_order_id   = $param['buy_order_id']??0;
        $map[]          = ['buy_order_id','=',$buy_order_id];
        $map[]          = ['product_id','=',$product_id];
        $info           = TbBuyOrderProduct::where($map)->first();
        if (!$info) return [];
        $info->supplier_id = $info->buyOrder->supplier_id;
        return $info;
    }
}