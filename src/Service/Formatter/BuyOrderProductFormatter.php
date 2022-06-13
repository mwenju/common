<?php


namespace Mwenju\Common\Service\Formatter;


use Mwenju\Common\Model\TbBuyOrderProduct;
use Mwenju\Common\Model\TbProduct;
use Mwenju\Common\Model\TbSupplier;

class BuyOrderProductFormatter
{
    public function base(TbBuyOrderProduct $model)
    {
        return [
            'id'=>$model->id,
            'product_id'=>$model->product_id,
            'buy_order_id'=>$model->buy_order_id,
            'product_name'=>$model->product_name,
            'supplier_name'=>TbSupplier::findFromCache($model->supplier_id)->supplier_name??'',
            'buy_num'=>$model->buy_num,
            'receive_num'=>$model->receive_num,
            'buy_price'=>$model->buy_price,
            'total_buy_price'=>$model->total_buy_price,
            'bar_code'=>$model->bar_code,
            'art_no'=>$model->art_no,
            'product_unit'=>$model->product_unit,
            'create_time'=>$model->create_time,
            'list_img_path'=>img_url(TbProduct::findFromCache($model->product_id)->list_img_path),
            'can_receive_num'=>$model->buy_num - $model->receive_num,
            'receive_status'=>trans("buy_order.receive_status_".($model->receive_num > 0 ? 1:0)),
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