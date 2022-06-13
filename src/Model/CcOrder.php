<?php

declare (strict_types=1);
namespace Mwenju\Common\Model;

use Mwenju\Common\Model\Model;
/**
 * @property int $id 
 * @property string $order_code 
 * @property string $mobile 
 * @property string $product_total_price 
 * @property string $freight_price 
 * @property string $discount_price 
 * @property string $pay_price 
 * @property string $paid_price 
 * @property int $product_total_num 
 * @property int $product_total_type_num 
 * @property string $consignee 
 * @property string $province_name 
 * @property string $city_name 
 * @property string $area_name 
 * @property string $address 
 * @property int $status 
 * @property int $admin_id 
 * @property string $pay_time 
 * @property string $create_time 
 * @property int $is_delete 
 * @property int $top_depot_id 
 */
class CcOrder extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected ?string $table = 'cc_order';
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
    protected array $casts = ['id' => 'integer', 'product_total_num' => 'integer', 'product_total_type_num' => 'integer', 'status' => 'integer', 'admin_id' => 'integer', 'is_delete' => 'integer', 'top_depot_id' => 'integer'];
}