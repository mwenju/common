<?php

declare (strict_types=1);
namespace Mwenju\Common\Model;

use Mwenju\Common\Model\Model;
/**
 * @property int $id 
 * @property int $coupon_template_id 
 * @property string $coupon_code 
 * @property string $bind_time 
 * @property int $shop_id 
 * @property int $user_id 
 * @property string $create_time 
 * @property int $batch_num 
 * @property string $use_time 
 * @property int $order_id 
 * @property int $is_delete 
 * @property string $delete_time 
 * @property int $delete_admin_id 
 */
class MfUserCouponList extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected ?string $table = 'mf_user_coupon_list';
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
    protected array $casts = ['id' => 'integer', 'coupon_template_id' => 'integer', 'shop_id' => 'integer', 'user_id' => 'integer', 'batch_num' => 'integer', 'order_id' => 'integer', 'is_delete' => 'integer', 'delete_admin_id' => 'integer'];
}