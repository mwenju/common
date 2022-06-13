<?php

declare (strict_types=1);
namespace Mwenju\Common\Model;

use Mwenju\Common\Model\Model;
/**
 * @property int $id 
 * @property string $coupon_name 
 * @property string $coupon_desc 
 * @property string $coupon_money 
 * @property string $min_price 
 * @property string $start_time 
 * @property string $end_time 
 * @property string $create_time 
 * @property int $supplier_id 
 * @property int $is_delete 
 * @property int $admin_id 
 * @property int $create_num 
 * @property int $batch_num 
 * @property int $bind_num 
 */
class MfUserCouponTemplate extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected ?string $table = 'mf_user_coupon_template';
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
    protected array $casts = ['id' => 'integer', 'supplier_id' => 'integer', 'is_delete' => 'integer', 'admin_id' => 'integer', 'create_num' => 'integer', 'batch_num' => 'integer', 'bind_num' => 'integer'];
}