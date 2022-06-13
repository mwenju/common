<?php

declare (strict_types=1);
namespace Mwenju\Common\Model;

use Mwenju\Common\Model\Model;
/**
 * @property int $id 
 * @property int $order_id 
 * @property string $coupon_money 
 * @property string $coupon_name 
 * @property int $coupon_list_id 
 * @property int $coupon_template_id 
 */
class MfShopOrderCoupon extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected ?string $table = 'mf_shop_order_coupon';
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
    protected array $casts = ['id' => 'integer', 'order_id' => 'integer', 'coupon_list_id' => 'integer', 'coupon_template_id' => 'integer'];
}