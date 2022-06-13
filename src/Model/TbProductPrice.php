<?php

declare (strict_types=1);
namespace Mwenju\Common\Model;

use Mwenju\Common\Model\Model;
/**
 * @property int $id 
 * @property string $province_code 
 * @property string $city_code 
 * @property string $area_code 
 * @property string $price 
 * @property int $product_id 
 */
class TbProductPrice extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected ?string $table = 'tb_product_price';
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
    protected array $casts = ['id' => 'integer', 'product_id' => 'integer'];
}