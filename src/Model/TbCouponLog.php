<?php

declare (strict_types=1);
namespace Mwenju\Common\Model;

use Mwenju\Common\Model\Model;
/**
 * @property int $id 
 * @property string $act_name 
 * @property string $mobile 
 * @property int $shop_id 
 * @property string $create_time 
 * @property int $admin_id 
 * @property int $coupon_template_id 
 * @property string $package_json 
 * @property int $err_code 
 * @property string $err_msg 
 * @property string $coupon_price 
 */
class TbCouponLog extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected ?string $table = 'tb_coupon_log';
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
    protected array $casts = ['id' => 'integer', 'shop_id' => 'integer', 'admin_id' => 'integer', 'coupon_template_id' => 'integer', 'err_code' => 'integer'];
}