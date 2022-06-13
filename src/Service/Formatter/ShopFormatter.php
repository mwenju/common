<?php


namespace Mwenju\Common\Service\Formatter;


use Mwenju\Common\Model\MfShop;
use Mwenju\Common\Service\ShopAddressService;

class ShopFormatter
{
    public function base(MfShop $model)
    {
        return [
            'id'=>$model->id,
            'user_id'=>$model->user_id,
            'cname'=>$model->cname,
            'mobile'=>$model?->user?->mobile,
            'addr'=>$model->addr,
            'link_man'=>$model->link_man,
            'link_mobile'=>$model->link_mobile,
            'face_img'=>img_url($model->face_img),
            'shop_tags'=>$model->shop_tags,
            'tag_name'=>trans("lang.shop_tag_".$model->shop_tags),
            'status'=>$model->status,
            'logistics_tel'=>$model->logistics_tel,
            'logistics_send_addr'=>$model->logistics_send_addr,
            'level_id'=>$model->level_id,
            'level_name'=>$model->level_id>1?"代理商":"普通商户",
            'business_license_url'=>img_url($model->business_license_url),
            'create_time'=>$model->create_time,
            'audit_remark'=>$model->audit_remark,
            'audit_time'=>$model->audit_time,
            'province_code'=>$model->province_code,
            'city_code'=>$model->city_code,
            'area_code'=>$model->area_code,
            'province_name'=>ShopAddressService::getNameByCode($model->province_code),
            'city_name'=>ShopAddressService::getNameByCode($model->city_code),
            'area_name'=>ShopAddressService::getNameByCode($model->area_code),
            'enable_money'=>$model->account?->enable_money,
            'freeze_money'=>$model->account?->freeze_money,
            'all_money'=>$model->account?->all_money,
            'enable_integrate'=>$model->account?->enable_integrate,
            'all_integrate'=>$model->account?->all_integrate,
            'user_recharge_money'=>$model->account?->user_recharge_money,
            'user_recharge_count'=>$model->account?->user_recharge_count,
            'freeze_recharge_money'=>$model->account?->freeze_recharge_money,
            'enable_recharge_money'=>$model->account?->enable_recharge_money,
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