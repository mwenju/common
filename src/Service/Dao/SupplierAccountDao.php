<?php


namespace Mwenju\Common\Service\Dao;


use Mwenju\Common\Model\TbSupplierAccount;
use Hyperf\Database\Model\Model;

class SupplierAccountDao extends Base
{
    public function create($param = []):TbSupplierAccount
    {
        $model = new TbSupplierAccount();
        $model->user_id = $param['user_id']??0;
        $model->supplier_id = $param['supplier_id']??0;
        $model->bank_id = $param['bank_id']??"";
        $model->bank_area = $param['bank_area']??"";
        $model->bank_area_id = $param['bank_area_id']??"";
        $model->bank_account = $param['bank_account']??"";
        $model->bank_card_number = $param['bank_card_number']??"";
        $model->rebate = $param['rebate']??0;
        $model->min_price = $param['min_price']??0;
        $model->all_out_money = 0;
        $model->get_money = 0;
        $model->save();
        return $model;
    }

    public function update($supplier_id,$param = []):TbSupplierAccount
    {
        $model = TbSupplierAccount::where("supplier_id",$supplier_id)->first();
        if (!$model){
            $model = new TbSupplierAccount();
        }
        $model->user_id = $param['user_id']??0;
        $model->supplier_id = $supplier_id;
        $model->bank_id = $param['bank_id']??"";
        $model->bank_area = $param['bank_area']??"";
        $model->bank_area_id = $param['bank_area_id']??"";
        $model->bank_account = $param['bank_account']??"";
        $model->bank_card_number = $param['bank_card_number']??"";
        $model->rebate = $param['rebate']??0;
        $model->min_price = $param['min_price']??0;
        $model->save();
        return $model;
    }
}