<?php

declare (strict_types=1);
namespace Mwenju\Common\Model;

use Mwenju\Common\Model\Model;
/**
 * @property int $id 
 * @property int $scene 
 * @property string $card_number 
 * @property int $user_id 
 * @property int $shop_id 
 * @property string $mobile 
 * @property string $order_code 
 * @property string $money 
 * @property string $pix 
 * @property int $pay_type 
 * @property int $pay_state 
 * @property int $recharge_config_id 
 * @property int $coupon_template_id 
 * @property string $title 
 * @property string $transaction_id 
 * @property string $bank_type 
 * @property string $fee_type 
 * @property string $create_time 
 * @property string $pay_time 
 * @property int $is_delete 
 * @property string $device_type 
 * @property string $device_code 
 * @property string $ip 
 * @property int $coupon_bind_id 
 */
class TbRechargeOrder extends Model
{
    public bool $timestamps = false;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected ?string $table = 'tb_recharge_order';
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
    protected array $casts = ['id' => 'integer', 'scene' => 'integer', 'user_id' => 'integer', 'shop_id' => 'integer', 'pay_type' => 'integer', 'pay_state' => 'integer', 'recharge_config_id' => 'integer', 'coupon_template_id' => 'integer', 'is_delete' => 'integer', 'coupon_bind_id' => 'integer'];
}