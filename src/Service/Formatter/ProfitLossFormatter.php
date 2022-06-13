<?php

namespace Mwenju\Common\Service\Formatter;

use Mwenju\Common\Model\TbProfitloss;

class ProfitLossFormatter
{
    public function base(TbProfitloss $model):array
    {
        return [
            'id'=>$model->id,
            'order_code'=>$model->order_code,
            'top_depot_id'=>$model->top_depot_id,
            'depot_name'=>trans("lang.top_depot_".$model->top_depot_id),
            'supplier_id'=>$model->supplier_id,
            'do_admin_name'=>$model->do_admin_name,
            'status'=>$model->status,
            'status_str'=>trans("lang.do_status_".$model->status),
            'do_time'=>$model->do_time,
            'sku_num'=>$model->sku_num,
            'profit_total_price'=>$model->profit_total_price,
            'profit_total_num'=>$model->profit_total_num,
            'create_admin_id'=>$model->create_admin_id,
            'create_admin_name'=>$model->create_admin_name,
            'create_time'=>$model->create_time,
            'is_diff_order'=>$model->is_diff_order,
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