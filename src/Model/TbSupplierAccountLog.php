<?php

declare (strict_types=1);
namespace Mwenju\Common\Model;

use Mwenju\Common\Model\Model;
/**
 * @property int $id 
 * @property int $supplier_id 
 * @property string $enable_money 
 * @property string $freeze_money 
 * @property string $all_money 
 * @property string $all_out_money 
 * @property string $add_money 
 * @property int $add_type 
 * @property string $why_info 
 * @property string $create_time 
 * @property int $user_id 
 * @property int $admin_id 
 */
class TbSupplierAccountLog extends Model
{
    public bool $timestamps = false;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected ?string $table = 'tb_supplier_account_log';
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
    protected array $casts = ['id' => 'integer', 'supplier_id' => 'integer', 'add_type' => 'integer', 'user_id' => 'integer', 'admin_id' => 'integer'];
}