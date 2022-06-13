<?php

declare (strict_types=1);
namespace Mwenju\Common\Model;

use Mwenju\Common\Model\Model;
/**
 * @property int $id 
 * @property int $profitloss_id 
 * @property int $product_id 
 * @property int $supplier_id 
 * @property string $product_name 
 * @property string $bar_code 
 * @property string $art_no 
 * @property int $stock_num 
 * @property int $take_num 
 * @property int $profit_num 
 * @property string $bid_price 
 * @property string $profit_total_price 
 * @property int $top_depot_id 
 * @property string $profit_reason 
 * @property int $depot_id 
 */
class TbProfitlossProduct extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected ?string $table = 'tb_profitloss_product';
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
    protected array $casts = ['id' => 'integer', 'profitloss_id' => 'integer', 'product_id' => 'integer', 'supplier_id' => 'integer', 'stock_num' => 'integer', 'take_num' => 'integer', 'profit_num' => 'integer', 'top_depot_id' => 'integer', 'depot_id' => 'integer'];
}