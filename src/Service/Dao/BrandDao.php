<?php


namespace Mwenju\Common\Service\Dao;


use Mwenju\Common\Model\MfBrand;

class BrandDao extends Base
{
    public function first($id): ?MfBrand
    {
        return MfBrand::findFromCache($id);
    }
    public function create($param = []):MfBrand
    {
        $model = new MfBrand();
        $model->cname           = $param['cname']??"";
        $model->pinyin          = $param['pinyin']??'';
        $model->logo            = $param['logo']??'';
        $model->product_per     = intval($param['product_per'])??0;
        $model->is_show         = intval($param['is_show'])??1;
        $model->save();
        return $model;
    }

    public function update($id,$param):MfBrand
    {
        $model = $this->first(intval($id));

        if (isset($param['cname']))
        {
            $model->cname = $param['cname']??"";
        }
        if (isset($param['pinyin']))
        {
            $model->pinyin = $param['pinyin']??"";
        }
        if (isset($param['logo']))
        {
            $model->logo = $param['logo']??"";
        }
        if (isset($param['product_per']))
        {
            $model->product_per = intval($param['product_per'])??"";
        }
        if (isset($param['is_show']))
        {
            $model->is_show = intval($param['is_show'])??0;
        }
        $model->save();
        return $model;
    }
}