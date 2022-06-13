<?php

declare (strict_types=1);
namespace Mwenju\Common\Model;

use Mwenju\Common\Model\Model;
/**
 * @property int $id 
 * @property int $shop_id 
 * @property int $from_shop_id 
 * @property int $product_id 
 * @property int $num 
 * @property string $shop_price 
 * @property string $total_price 
 * @property string $image_path 
 * @property string $product_name 
 * @property string $product_unit 
 * @property string $param_list 
 * @property string $create_time 
 * @property string $last_update_time 
 * @property int $selected 
 */
class MfShopCart extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected ?string $table = 'mf_shop_cart';
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
    protected array $casts = ['id' => 'integer', 'shop_id' => 'integer', 'from_shop_id' => 'integer', 'product_id' => 'integer', 'num' => 'integer', 'selected' => 'integer'];
}