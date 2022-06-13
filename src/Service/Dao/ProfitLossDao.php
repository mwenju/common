<?php

namespace Mwenju\Common\Service\Dao;

use Mwenju\Common\Model\TbProfitloss;
use Mwenju\Common\Model\TbProfitlossProduct;
use Hyperf\Database\Model\Builder;

class ProfitLossDao extends Base
{
    public function find(array $param,?int $page,?int $limit): array
    {
        $param                  = array_map("trim",$param);
        $is_diff_order          = $param['is_diff_order']??"";
        $top_depot_id           = $param['top_depot_id']??0;
        $supplier_id            = $param['supplier_id']??0;
        $stock_take_order_id    = $param['stock_take_order_id']??0;
        $status                 = $param['status']??"";
        $keyword                = $param['keyword']??"";
        $map = [];
        if ($top_depot_id > 0){
            $map[] = ['top_depot_id','=',$top_depot_id];
        }
        if ($supplier_id > 0){
            $map[] = ['supplier_id','=',$supplier_id];
        }
        if ($stock_take_order_id > 0){
            $map[] = ['stock_take_order_id','=',$stock_take_order_id];
        }
        if (strlen($status) > 0){
            $map[] = ['status','=',$status];
        }
        if (strlen($is_diff_order) > 0){
            $map[] = ['is_diff_order','=',$is_diff_order];
        }
        $query = TbProfitloss::where($map);
        if (!empty($keyword)){
            $query->where(function(Builder $q) use ($keyword){
                return $q->where("order_code",'like',"%$keyword%")
                    ->orWhere("do_admin_name",'like',"%$keyword%");
            });
        };
        $query->orderBy("id",'desc');

        return $this->pagination($query,$page,$limit);
    }
}