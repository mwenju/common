<?php

declare (strict_types=1);
namespace Mwenju\Common\Model;

use Mwenju\Common\Model\Model;
/**
 * @property int $id 
 * @property int $order_id 
 * @property int $product_id 
 * @property string $cc_product_unit 
 * @property string $product_name 
 * @property string $bar_code 
 * @property string $art_no 
 * @property string $price 
 * @property string $bid_price 
 * @property int $num 
 * @property int $top_depot_id 
 */
class CcOrderProduct extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected ?string $table = 'cc_order_product';
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
    protected array $casts = ['id' => 'integer', 'order_id' => 'integer', 'product_id' => 'integer', 'num' => 'integer', 'top_depot_id' => 'integer'];
}