<?php


namespace Mwenju\Common\Service\Dao;


use Mwenju\Common\Model\TbBuyOrder;
use Hyperf\Database\Model\Builder;

class BuyOrderDao extends Base
{
    public function getList($param,$page,$limit)
    {
        $audit_status       = $param['audit_status']??"";
        $supplier_id        = $param['supplier_id']??0;
        $top_depot_id       = $param['top_depot_id']??0;
        $order_status       = $param['order_status']??"";
        $keyword            = $param['keyword']??"";
        $order_status_in    = $param['order_status_in']??[];
        $map = [];
        if (strlen($audit_status) > 0){
            $map[] = ['audit_status','=',$audit_status];
        }
        if ($supplier_id > 0){
            $map[] = ['supplier_id','=',$supplier_id];
        }
        if (strlen($order_status) > 0){
            $map[] = ['order_status','=',$order_status];
        }
        if ($top_depot_id > 0){
            $map[] = ['top_depot_id','=',$top_depot_id];
        }
        $query = TbBuyOrder::where($map)->when($keyword,function ($query,$keyword){
            return $query->where(function ($q) use ($keyword){
                return $q->where("supplier_name",'like',"%{$keyword}%")
                    ->orWhere("buy_order_number",'like',"%{$keyword}%")
                    ->orWhere("create_by",'like',"%{$keyword}%");
            });
        })
            ->when($order_status_in,function ($query,$order_status_in){
                return $query->whereIn("order_status",$order_status_in);
            });
        $query->orderBy("id",'desc');
        return $this->pagination($query,$page,$limit);
    }

    public function getInfo($id)
    {
        return TbBuyOrder::find($id??0);
    }
}