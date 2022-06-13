<?php

declare (strict_types=1);
namespace Mwenju\Common\Model;

use Mwenju\Common\Model\Model;
/**
 * @property int $id 
 * @property int $product_id 
 * @property int $top_depot_id 
 * @property int $stock_num 
 * @property int $salable_num 
 * @property int $lock_num 
 * @property string $bid_price 
 * @property int $cc_packing_rate 
 * @property string $cc_product_unit 
 * @property int $bb_out_total_num 
 * @property int $bb_out_remainder_num 
 */
class CcProductStock extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected ?string $table = 'cc_product_stock';
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
    protected array $casts = ['id' => 'integer', 'product_id' => 'integer', 'top_depot_id' => 'integer', 'stock_num' => 'integer', 'salable_num' => 'integer', 'lock_num' => 'integer', 'cc_packing_rate' => 'integer', 'bb_out_total_num' => 'integer', 'bb_out_remainder_num' => 'integer'];
}