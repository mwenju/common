<?php

namespace Mwenju\Common\Service\Dao;

use Mwenju\Common\Model\TbProfitlossProduct;

class ProfitLossProductDao
{

    public function find(array $param = []): \Hyperf\Database\Model\Collection|array
    {
        $profitloss_id          = $param['profitloss_id']??"";
        $top_depot_id           = $param['top_depot_id']??0;
        $supplier_id            = $param['supplier_id']??0;
        $map = [];
        if ($top_depot_id > 0){
            $map[] = ['top_depot_id','=',$top_depot_id];
        }
        if ($profitloss_id > 0){
            $map[] = ['profitloss_id','=',$profitloss_id];
        }
        if ($supplier_id > 0){
            $map[] = ['supplier_id','=',$supplier_id];
        }
        $model = TbProfitlossProduct::where($map)->get();
        return $model;
    }
}