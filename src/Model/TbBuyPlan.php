<?php

declare (strict_types=1);
namespace Mwenju\Common\Model;

use Mwenju\Common\Model\Model;
/**
 * @property int $id 
 * @property string $buy_order_number 
 * @property int $supplier_id 
 * @property int $product_id 
 * @property int $buy_num 
 * @property int $receive_num 
 * @property int $admin_id 
 * @property string $create_time 
 * @property int $order_status 
 * @property string $update_time 
 */
class TbBuyPlan extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected ?string $table = 'tb_buy_plan';
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
    protected array $casts = ['id' => 'integer', 'supplier_id' => 'integer', 'product_id' => 'integer', 'buy_num' => 'integer', 'admin_id' => 'integer', 'audit_status' => 'integer'];
}