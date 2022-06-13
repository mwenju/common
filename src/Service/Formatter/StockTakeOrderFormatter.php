<?php

namespace Mwenju\Common\Service\Formatter;

use Mwenju\Common\Model\TbProfitloss;
use Mwenju\Common\Model\TbStockTakeOrder;

class StockTakeOrderFormatter
{
    public function base(TbStockTakeOrder $model):array
    {
        return [
            'id'=>$model->id,
            'order_code'=>$model->order_code,
            'top_depot_id'=>$model->top_depot_id,
            'depot_name'=>trans("lang.top_depot_".$model->top_depot_id),
            'supplier_id'=>$model->supplier_id,
            'user_id'=>$model->user_id,
            'status'=>$model->status,
            'status_str'=>trans("lang.stock_take_status_".$model->status),
            'total_price'=>$model->total_price,
            'total_num'=>$model->total_num,
            'type_total_num'=>$model->type_total_num,
            'real_total_num'=>$model->real_total_num,
            'real_total_price'=>$model->real_total_price,
            'audit_time'=>$model->audit_time,
            'create_time'=>$model->create_time,
            'execute_time'=>$model->execute_time,
        ];
    }

    public function formatList($models): array
    {
        $results = [];
        foreach ($models as $model) {
            $results[] = $this->base($model);
        }
        return $results;
    }
}