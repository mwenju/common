<?php


namespace Mwenju\Common\Service\Dao;


use Mwenju\Common\Model\TbSupplier;

class SupplierDao extends Base
{
    public function first(int $id): ?TbSupplier
    {
        return TbSupplier::findFromCache($id);
    }
    public function create($param = []):TbSupplier
    {
        $model = new TbSupplier();
        $model->user_id         = $param['user_id']??0;
        $model->supplier_name   = $param['supplier_name']??'';
        $model->is_new          = $param['is_new']??0;
        $model->link_man        = $param['link_man']??'';
        $model->link_mobile     = $param['link_mobile']??"";
        $model->pay_type        = $param['pay_type']??0;
        $model->bond_rate       = $param['bond_rate']??0;
        $model->service_fee_rate= $param['service_fee_rate']??0;
        $model->status          = 1;
        $model->create_time     = date("Y-m-d H:i:s");
        $model->save();
        return $model;
    }

    public function update($id,$param):TbSupplier
    {
        $model = $this->first(intval($id));
        if (isset($param['user_id']))
        {
            $model->user_id = intval($param['user_id'])??0;
        }
        if (isset($param['supplier_name']))
        {
            $model->supplier_name = $param['supplier_name']??"";
        }
        if (isset($param['link_man']))
        {
            $model->link_man = $param['link_man']??"";
        }
        if (isset($param['link_mobile']))
        {
            $model->link_mobile = $param['link_mobile']??"";
        }
        if (isset($param['bond_rate']))
        {
            $model->bond_rate = $param['bond_rate']??0;
        }
        if (isset($param['pay_type']))
        {
            $model->pay_type = $param['pay_type']??0;
        }
        if (isset($param['bond_rate']))
        {
            $model->bond_rate = $param['bond_rate']??0;
        }
        if (isset($param['service_fee_rate']))
        {
            $model->service_fee_rate = $param['service_fee_rate']??0;
        }
        $model->save();
        return $model;
    }

}