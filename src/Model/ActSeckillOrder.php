<?php

declare (strict_types=1);
namespace Mwenju\Common\Model;

use Mwenju\Common\Model\Model;
/**
 * @property int $id 
 * @property int $order_id 
 * @property int $shop_id 
 * @property int $product_id 
 * @property int $act_seckill_id 
 * @property int $buy_num 
 * @property string $create_time 
 * @property int $is_delete 
 */
class ActSeckillOrder extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected ?string $table = 'act_seckill_order';
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
    protected array $casts = ['id' => 'integer', 'order_id' => 'integer', 'shop_id' => 'integer', 'product_id' => 'integer', 'act_seckill_id' => 'integer', 'buy_num' => 'integer', 'is_delete' => 'integer'];
}