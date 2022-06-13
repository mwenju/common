<?php

declare (strict_types=1);
namespace Mwenju\Common\Model;

use Mwenju\Common\Model\Model;
/**
 * @property int $id 
 * @property int $tpl_mode 
 * @property string $coupon_name 
 * @property string $coupon_desc 
 * @property string $coupon_money 
 * @property string $min_price 
 * @property string $start_time 
 * @property string $end_time 
 * @property string $create_time 
 * @property string $device_type 
 * @property int $supplier_id 
 * @property string $package_json 
 * @property string $rule_json 
 * @property string $range 
 * @property int $is_delete 
 * @property int $admin_id 
 * @property int $create_num 
 * @property int $batch_num 
 * @property int $bind_num 
 */
class TbCouponTemplate extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected ?string $table = 'tb_coupon_template';
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
    protected array $casts = ['id' => 'integer', 'tpl_mode' => 'integer', 'supplier_id' => 'integer', 'is_delete' => 'integer', 'admin_id' => 'integer', 'create_num' => 'integer', 'batch_num' => 'integer', 'bind_num' => 'integer'];
}