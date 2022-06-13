<?php


namespace Mwenju\Common\Service\Formatter;


use Mwenju\Common\Model\MfShopOrder;
use Mwenju\Common\Service\ShopAddressService;

class ShopOrderFormatter
{
    public function base(MfShopOrder $model)
    {
        return [
            'id'=>$model->id,
            'shop_name'=>$model->shop_name,
            'supplier_name'=>$model->supplier_name,
            'order_code'=>$model->order_code,
            'status'=>$model->status,
            'product_total_price'=>$model->product_total_price,
            'product_total_num'=>$model->product_total_num,
            'product_total_type_num'=>$model->product_total_type_num,
            'discount_price'=>$model->discount_price,
            'pay_price'=>$model->pay_price,
            'total_price'=>$model->total_price,
            'paid_price'=>$model->paid_price,
            'freight_price'=>$model->freight_price,
            'freight_price_str'=>$model->total_price>399?'补贴':'自付',
            'paid_balance_price'=>$model->paid_balance_price,
            'paid_integrate'=>$model->paid_integrate,
            'pay_type'=>$model->pay_type,
            'create_time'=>$model->create_time,
            'pay_time'=>$model->pay_time,
            'consignee'=>$model->consignee,
            'province_name'=>ShopAddressService::getNameByCode($model->province_code),
            'city_name'=>ShopAddressService::getNameByCode($model->city_code),
            'area_name'=>ShopAddressService::getNameByCode($model->area_code),
            'mobile'=>$model->mobile,
            'address'=>$model->address,
            'top_depot_id'=>$model->top_depot_id,
            'delivery_status'=>$model->delivery_status,
            'delivery_time'=>$model->delivery_time,
            'receive_time'=>$model->receive_time,
            'remark'=>$model->remark,
            'audit_status'=>$model->audit_status,
            'audit_status_str' => trans("audit.status_".$model->audit_status),
            'depot_name' => trans("lang.top_depot_".$model->top_depot_id),
            'order_status_str' => trans("lang.order_status_".$model->status),
            'delivery_status_str' => trans("lang.delivery_status_".$model->delivery_status)
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