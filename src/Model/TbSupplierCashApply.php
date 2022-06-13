<?php

declare (strict_types=1);
namespace Mwenju\Common\Model;

use Mwenju\Common\Model\Model;
/**
 * @property int $id 
 * @property int $cash_user_id 
 * @property int $add_time 
 * @property string $apply_price 
 * @property int $status 
 * @property int $check_time 
 * @property int $cash_time 
 * @property string $bank_id 
 * @property string $bank_name 
 * @property string $bank_area_id 
 * @property string $bank_area_name 
 * @property string $bank_account 
 * @property string $bank_card_number 
 * @property int $supplier_id 
 * @property string $supplier_name 
 * @property string $rebate_price 
 * @property string $cash_price 
 * @property string $fact_price 
 * @property string $create_time 
 * @property int $audit_admin_id 
 * @property string $audit_time 
 * @property int $pay_admin_id 
 * @property string $pay_time 
 */
class TbSupplierCashApply extends Model
{
    public bool $timestamps = false;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected ?string $table = 'tb_supplier_cash_apply';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected array $fillable = [];
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected array $casts = ['id' => 'integer', 'cash_user_id' => 'integer', 'add_time' => 'integer', 'status' => 'integer', 'check_time' => 'integer', 'cash_time' => 'integer', 'supplier_id' => 'integer', 'audit_admin_id' => 'integer', 'pay_admin_id' => 'integer'];
}