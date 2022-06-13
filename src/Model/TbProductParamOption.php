<?php

declare (strict_types=1);
namespace Mwenju\Common\Model;

use Mwenju\Common\Model\Model;
/**
 * @property int $id 
 * @property int $product_param_id 
 * @property string $param_val 
 */
class TbProductParamOption extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected ?string $table = 'tb_product_param_options';
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
    protected array $casts = ['id' => 'integer', 'product_param_id' => 'integer'];
}