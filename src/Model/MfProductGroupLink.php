<?php

declare (strict_types=1);
namespace Mwenju\Common\Model;

use Mwenju\Common\Model\Model;
/**
 * @property int $id 
 * @property string $link_name 
 * @property int $product_id 
 * @property int $product_group_id 
 * @property int $sort 
 * @property int $ppl_id 
 */
class MfProductGroupLink extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected ?string $table = 'mf_product_group_link';
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
    protected array $casts = ['id' => 'integer', 'product_id' => 'integer', 'product_group_id' => 'integer', 'sort' => 'integer', 'ppl_id' => 'integer'];
}