<?php

declare (strict_types=1);
namespace Mwenju\Common\Model;

use Mwenju\Common\Model\Model;
/**
 * @property int $id 
 * @property string $order_code 
 * @property int $top_depot_id 
 * @property int $supplier_id 
 * @property int $stock_take_order_id 
 * @property int $do_admin_id 
 * @property string $do_admin_name 
 * @property int $status 
 * @property string $do_time 
 * @property int $sku_num 
 * @property string $profit_total_price 
 * @property int $create_admin_id 
 * @property int $is_diff_order
 * @property int $profit_total_num
 * @property string $create_admin_name
 * @property string $create_time 
 */
class TbProfitloss extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected ?string $table = 'tb_profitloss';
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
    protected array $casts = ['id' => 'integer', 'top_depot_id' => 'integer', 'supplier_id' => 'integer', 'stock_take_order_id' => 'integer', 'do_admin_id' => 'integer', 'status' => 'integer', 'sku_num' => 'integer', 'create_admin_id' => 'integer'];
}