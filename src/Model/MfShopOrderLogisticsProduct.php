<?php

declare (strict_types=1);
namespace Mwenju\Common\Model;

use Mwenju\Common\Model\Model;
/**
 * @property int $id 
 * @property int $order_id 
 * @property int $ppl_id 
 * @property int $logistics_id 
 * @property int $order_product_id 
 * @property int $send_num 
 * @property int $send_time 
 * @property int $send_user_id 
 * @property int $get_num 
 * @property int $get_time 
 * @property int $get_user_id 
 * @property int $status 
 * @property string $get_beizhu 
 * @property int $rsync_state 
 * @property string $create_time 
 */
class MfShopOrderLogisticsProduct extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected ?string $table = 'mf_shop_order_logistics_product';
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
    protected array $casts = ['id' => 'integer', 'order_id' => 'integer', 'ppl_id' => 'integer', 'logistics_id' => 'integer', 'order_product_id' => 'integer', 'send_num' => 'integer', 'send_time' => 'integer', 'send_user_id' => 'integer', 'get_num' => 'integer', 'get_time' => 'integer', 'get_user_id' => 'integer', 'status' => 'integer', 'rsync_state' => 'integer'];
}