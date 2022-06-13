<?php

declare (strict_types=1);
namespace Mwenju\Common\Model;

use Mwenju\Common\Model\Model;
/**
 * @property int $id 
 * @property int $supplier_id 
 * @property int $user_id 
 * @property string $enable_money 
 * @property string $freeze_money 
 * @property string $all_money 
 * @property int $last_do_user_id 
 * @property int $last_do_time 
 * @property string $bank_id 
 * @property string $bank_area 
 * @property string $bank_area_id 
 * @property string $bank_account 
 * @property string $bank_card_number 
 * @property string $rebate 
 * @property string $min_price 
 * @property string $get_money 
 * @property string $all_out_money 
 */
class TbSupplierAccount extends Model
{
    public bool $timestamps = false;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected ?string $table = 'tb_supplier_account';
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
    protected array $casts = ['id' => 'integer', 'supplier_id' => 'integer', 'user_id' => 'integer', 'last_do_user_id' => 'integer', 'last_do_time' => 'integer'];
}