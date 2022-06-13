<?php

declare (strict_types=1);
namespace Mwenju\Common\Model;

use Mwenju\Common\Model\Model;
/**
 * @property int $id 
 * @property string $order_code 
 * @property string $product_total_price 
 * @property string $balance 
 * @property string $discount 
 * @property string $pay_price 
 * @property string $paid_price 
 * @property int $paid_integrate 
 * @property string $create_time 
 * @property string $card_number 
 * @property string $mobile 
 * @property int $user_id 
 * @property int $shop_id 
 * @property int $state 
 * @property string $cancel_time 
 * @property string $delivery_time 
 */
class MfUserOrder extends Model
{
    public bool $timestamps = false;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected ?string $table = 'mf_user_order';
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
    protected array $casts = ['id' => 'integer', 'paid_integrate' => 'integer', 'user_id' => 'integer', 'shop_id' => 'integer', 'state' => 'integer'];
}