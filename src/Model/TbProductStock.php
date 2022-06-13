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
 * @property int $warn_num 
 * @property string $bid_price 
 * @property string $last_bid_price 
 */
class TbProductStock extends Model
{
    public bool $timestamps = false;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected ?string $table = 'tb_product_stock';
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
    protected array $casts = ['id' => 'integer', 'product_id' => 'integer', 'top_depot_id' => 'integer', 'stock_num' => 'integer', 'salable_num' => 'integer', 'lock_num' => 'integer', 'warn_num' => 'integer'];
}