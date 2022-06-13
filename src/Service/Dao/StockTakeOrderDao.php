<?php

namespace Mwenju\Common\Service\Dao;

use Mwenju\Common\Model\TbProfitloss;
use Mwenju\Common\Model\TbProfitlossProduct;
use Mwenju\Common\Model\TbStockTakeOrder;
use Hyperf\Database\Model\Builder;

class StockTakeOrderDao extends Base
{
    public function find(array $param,?int $page,?int $limit): array
    {
        $param                  = array_map("trim",$param);
        $top_depot_id           = $param['top_depot_id']??0;
        $supplier_id            = $param['supplier_id']??0;
        $status                 = $param['status']??"";
        $keyword                = $param['keyword']??"";
        $map = [];
        if ($top_depot_id > 0){
            $map[] = ['top_depot_id','=',$top_depot_id];
        }
        if ($supplier_id > 0){
            $map[] = ['supplier_id','=',$supplier_id];
        }

        if (strlen($status) > 0){
            $map[] = ['status','=',$status];
        }

        $query = TbStockTakeOrder::where($map);
        if (!empty($keyword)){
            $query->where(function(Builder $q) use ($keyword){
                return $q->where("order_code",'like',"%$keyword%");
            });
        };
        $query->orderBy("id",'desc');

        return $this->pagination($query,$page,$limit);
    }
}