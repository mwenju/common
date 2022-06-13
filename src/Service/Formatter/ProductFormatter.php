<?php


namespace Mwenju\Common\Service\Formatter;


use Mwenju\Common\Model\MfBrand;
use Mwenju\Common\Model\TbProduct;
use Mwenju\Common\Model\TbProductType;
use Mwenju\Common\Model\TbSupplier;

class ProductFormatter
{
    public function base(TbProduct $model)
    {
        return [
            'id'                =>$model->id,
            'product_id'        =>$model->id,
            'product_name'      =>$model->product_name,
            'list_img_path'     =>img_url($model->list_img_path),
            'supplier_name'     =>TbSupplier::findFromCache($model->supplier_id)->supplier_name,
            'type_name'         =>TbProductType::findFromCache($model->product_type_id)->cname,
            'brand_name'        =>MfBrand::findFromCache($model->brand_id)->cname,
            'wholesale_price'   =>$model->wholesale_price,
            'original_price'    =>$model->original_price,
            'bid_price'         =>$model->bid_price,
            'last_bid_price'    =>$model->last_bid_price,
            'bar_code'          =>$model->bar_code,
            'art_no'            =>$model->art_no,
            'product_unit'      =>$model->product_unit,
            'is_on_sale'        =>$model->is_on_sale,
            'create_time'       =>$model->create_time,
            'on_sale_time'      =>$model->on_sale_time,
            'stock_num'         =>$model->stock_num,
            'lock_num'          =>$model->lock_num,
            'warn_num'          =>$model->warn_num,
            'depot_name'        =>$model->depot_name,
            'depot_id'          =>$model->depot_id??0,
            'real_sale_num'     =>$model->real_sale_num,
            'virtual_sale_num'  =>$model->virtual_sale_num,
            'market_price'      =>$model->market_price,
            'jianyi_price'      =>$model->jianyi_price,
            'integrate_num'     =>$model->integrate_num,
            'cc_integrate_num'  =>$model->cc_integrate_num,
            'is_integrate'      =>$model->is_integrate,
            'is_show'           =>$model->is_show,
            'package'           =>$this->packageFmt($model->product_param_values_json,7),
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

    private function packageFmt($param,$id)
    {
        foreach ($param as $item){
            if ($id == $item['id'])
                return $item['value']??"";
        }
        return "";
    }
}