<?php

declare (strict_types=1);
namespace Mwenju\Common\Model;

use Mwenju\Common\Model\Model;
/**
 * @property int $id 
 * @property int $shop_id 
 * @property int $user_id 
 * @property int $supplier_id
 * @property string $shop_name
 * @property string $supplier_name
 * @property string $order_code
 * @property int $all_money 
 * @property int $real_money 
 * @property int $jiesuan 
 * @property int $status 
 * @property int $last_time 
 * @property int $is_huizong 
 * @property string $product_total_price 
 * @property int $product_total_num 
 * @property int $product_total_type_num 
 * @property string $freight_price 
 * @property string $total_price 
 * @property string $discount_price 
 * @property string $pay_price 
 * @property string $paid_price 
 * @property string $paid_balance_price 
 * @property int $paid_integrate 
 * @property int $pay_type 
 * @property string $create_time 
 * @property string $pay_time 
 * @property string $consignee 
 * @property string $mobile 
 * @property string $province_code 
 * @property string $city_code 
 * @property string $area_code 
 * @property string $address 
 * @property int $addr_type 
 * @property int $top_depot_id 
 * @property int $delivery_status 
 * @property int $address_id 
 * @property string $delivery_time 
 * @property string $receive_time
 * @property string $remark
 * @property string $device_type 
 * @property int $coupon_bind_id 
 * @property int $is_return
 * @property int $audit_status
 */
class MfShopOrder extends Model
{
    public function products()
    {
        return $this->hasMany(MfShopOrderProduct::class,"order_id",'id');
    }
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected ?string $table = 'mf_shop_order';
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
    protected array $casts = ['id' => 'integer', 'shop_id' => 'integer', 'user_id' => 'integer', 'all_money' => 'integer', 'real_money' => 'integer', 'jiesuan' => 'integer', 'status' => 'integer', 'last_time' => 'integer', 'is_huizong' => 'integer', 'product_total_num' => 'integer', 'product_total_type_num' => 'integer', 'paid_integrate' => 'integer', 'pay_type' => 'integer', 'addr_type' => 'integer', 'top_depot_id' => 'integer', 'delivery_status' => 'integer', 'address_id' => 'integer', 'coupon_bind_id' => 'integer'];
}