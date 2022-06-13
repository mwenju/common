<?php

declare (strict_types=1);
namespace Mwenju\Common\Model;

use Mwenju\Common\Model\Model;
/**
 * @property int $id 
 * @property int $product_id 
 * @property string $bid_price 
 * @property string $market_price 
 * @property string $wholesale_price 
 * @property string $create_time 
 * @property int $admin_id 
 */
class TbProductPriceLog extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected ?string $table = 'tb_product_price_log';
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
    protected array $casts = ['id' => 'integer', 'product_id' => 'integer', 'admin_id' => 'integer'];
}