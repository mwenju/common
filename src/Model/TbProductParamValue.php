<?php

declare (strict_types=1);
namespace Mwenju\Common\Model;

use Mwenju\Common\Model\Model;
/**
 * @property int $id 
 * @property int $product_id 
 * @property int $product_param_id 
 * @property string $param_name 
 * @property string $param_val 
 * @property int $product_type_id 
 * @property string $bid_price 
 * @property string $market_price 
 * @property string $wholesal_price 
 * @property int $num 
 * @property string $bar_code 
 * @property string $art_no 
 * @property int $add_time 
 * @property int $add_user_id 
 */
class TbProductParamValue extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected ?string $table = 'tb_product_param_value';
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
    protected array $casts = ['id' => 'integer', 'product_id' => 'integer', 'product_param_id' => 'integer', 'product_type_id' => 'integer', 'num' => 'integer', 'add_time' => 'integer', 'add_user_id' => 'integer'];
}