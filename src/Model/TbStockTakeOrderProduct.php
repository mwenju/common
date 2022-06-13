<?php

declare (strict_types=1);
namespace Mwenju\Common\Model;

use Mwenju\Common\Model\Model;
/**
 * @property int $id 
 * @property int $product_id 
 * @property int $supplier_id 
 * @property int $depot_id 
 * @property string $product_name 
 * @property string $bar_code 
 * @property string $art_no 
 * @property int $num 
 * @property int $real_num 
 * @property string $bid_price 
 * @property string $bid_total_price 
 * @property string $real_bid_total_price 
 * @property int $admin_id 
 * @property int $user_id 
 * @property int $update_admin_id 
 * @property int $stock_take_order_id 
 * @property int $child_order_id 
 * @property string $create_time 
 * @property int $top_depot_id 
 */
class TbStockTakeOrderProduct extends Model
{
    public bool $timestamps = false;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected ?string $table = 'tb_stock_take_order_product';
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
    protected array $casts = ['id' => 'integer', 'product_id' => 'integer', 'supplier_id' => 'integer', 'depot_id' => 'integer', 'num' => 'integer', 'real_num' => 'integer', 'admin_id' => 'integer', 'user_id' => 'integer', 'update_admin_id' => 'integer', 'stock_take_order_id' => 'integer', 'child_order_id' => 'integer', 'top_depot_id' => 'integer'];
}