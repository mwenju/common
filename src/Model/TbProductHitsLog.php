<?php

declare (strict_types=1);
namespace Mwenju\Common\Model;

use Mwenju\Common\Model\Model;
/**
 * @property int $id 
 * @property string $device_code 
 * @property int $shop_id 
 * @property int $product_id 
 * @property int $hit_num 
 * @property string $create_time 
 * @property string $last_update_time 
 */
class TbProductHitsLog extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected ?string $table = 'tb_product_hits_log';
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
    protected array $casts = ['id' => 'integer', 'shop_id' => 'integer', 'product_id' => 'integer', 'hit_num' => 'integer'];
}