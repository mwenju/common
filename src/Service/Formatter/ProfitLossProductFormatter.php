<?php

namespace Mwenju\Common\Service\Formatter;

use Mwenju\Common\Model\TbProfitlossProduct;

class ProfitLossProductFormatter
{
    public function base(TbProfitlossProduct $model):array
    {
        return [
            'id'=>$model->id,
            'product_id'=>$model->product_id,
            'supplier_id'=>$model->supplier_id,
            'product_name'=>$model->product_name,
            'bar_code'=>$model->bar_code,
            'art_no'=>$model->art_no,
            'stock_num'=>$model->stock_num,
            'take_num'=>$model->take_num,
            'profit_num'=>$model->profit_num,
            'bid_price'=>$model->bid_price,
            'profit_total_price'=>$model->profit_total_price,
            'top_depot_id'=>$model->top_depot_id,
            'profit_reason'=>$model->profit_reason,
            'depot_id'=>$model->depot_id,
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