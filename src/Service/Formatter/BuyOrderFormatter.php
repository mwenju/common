<?php


namespace Mwenju\Common\Service\Formatter;


use Mwenju\Common\Model\TbBuyOrder;

class BuyOrderFormatter
{
    public function base(TbBuyOrder $model): array
    {
        return [
            'id'=>$model->id,
            'buy_order_number'=>$model->buy_order_number,
            'supplier_id'=>$model->supplier_id,
            'supplier_name'=>$model->supplier_name,
            'create_time'=>$model->create_time,
            'order_status'=>$model->order_status,
            'top_depot_id'=>$model->top_depot_id,
            'total_price'=>$model->total_price,
            'total_num'=>$model->total_num,
            'audit_status'=>$model->audit_status,
            'create_by'=>$model->create_by,
            'type_total_num'=>$model->type_total_num,
            'credit_total_price'=>$model->credit_total_price,
            'remark'=>$model->remark??"",
            'audit_status_str'=>trans("audit.status_".$model->audit_status),
            'order_status_str'=>trans("buy_order.status_".$model->order_status),
            'depot_name'=>trans("lang.top_depot_".$model->top_depot_id),
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