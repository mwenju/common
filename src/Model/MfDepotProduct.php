<?php

declare (strict_types=1);
namespace Mwenju\Common\Model;

use Mwenju\Common\Model\Model;
/**
 * @property int $id 
 * @property int $depot_id 
 * @property int $product_id 
 * @property int $store_num 
 * @property string $now_bid_price 
 * @property int $lock_num 
 * @property int $top_depot_id 
 * @property int $is_delete 
 */
class MfDepotProduct extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected ?string $table = 'mf_depot_product';
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
    protected array $casts = ['id' => 'integer', 'depot_id' => 'integer', 'product_id' => 'integer', 'store_num' => 'integer', 'lock_num' => 'integer', 'top_depot_id' => 'integer', 'is_delete' => 'integer'];
}