<?php


namespace Mwenju\Common\Service\Formatter;


use Mwenju\Common\Model\MfShop;

class ShopOrderLogisticsFormatter
{
    public function base($model)
    {
        return [
            'id'=>$model->id,
            'total_num'=>$model->total_num,
            'shop_name'=>$model->shop_name,
            'logistics_comp_cname'=>$model->logistics_comp_cname,
            'logistics_send_addr'=>$model->logistics_send_addr,
            'logistics_code'=>$model->logistics_code??'',
            'create_time'=>$model->create_time,
            'delivery_time'=>$model->delivery_time,
            'addr'=>$model->addr,
            'consignee'=>$model->consignee,
            'mobile'=>$model->mobile,
            'logistics_money'=>$model->logistics_money,
            'is_peihuo'=>$model->is_peihuo??0,
            'status'=>$model->is_peihuo>0?"待发货":"发货完成",
            'pay_type'=>$model->pay_type==1?"已付":"到付",
            'subsidy_state'=>$model->subsidy_money >0?'已补':'未补',
            'addr_type_str'=>$model->addr_type==1?'县城':'乡镇',
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