<?php

declare (strict_types=1);
namespace Mwenju\Common\Model;

use Mwenju\Common\Model\Model;
/**
 * @property int $id 
 * @property int $act_seckill_id 
 * @property string $act_name 
 * @property string $start_time 
 * @property string $end_time 
 * @property int $product_id 
 * @property string $act_price 
 * @property int $act_stock 
 * @property int $sold_stock 
 * @property int $limit_day_num 
 * @property int $limit_total_num 
 * @property string $create_time 
 * @property string $tag_img 
 */
class ActSeckillProduct extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected ?string $table = 'act_seckill_product';
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
    protected array $casts = ['id' => 'integer', 'act_seckill_id' => 'integer', 'product_id' => 'integer', 'act_stock' => 'integer', 'sold_stock' => 'integer', 'limit_day_num' => 'integer', 'limit_total_num' => 'integer'];
}