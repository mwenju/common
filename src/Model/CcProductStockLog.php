<?php

declare (strict_types=1);
namespace Mwenju\Common\Model;

use Mwenju\Common\Model\Model;
/**
 * @property int $id 
 * @property int $product_id 
 * @property int $supplier_id 
 * @property int $add_num 
 * @property string $bid_price 
 * @property int $top_depot_id 
 * @property int $do_type 
 * @property int $do_type_id 
 * @property string $remark 
 * @property string $create_time 
 * @property int $admin_id 
 * @property int $user_id 
 * @property int $is_ok 
 */
class CcProductStockLog extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected ?string $table = 'cc_product_stock_log';
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
    protected array $casts = ['id' => 'integer', 'product_id' => 'integer', 'supplier_id' => 'integer', 'add_num' => 'integer', 'top_depot_id' => 'integer', 'do_type' => 'integer', 'do_type_id' => 'integer', 'admin_id' => 'integer', 'user_id' => 'integer', 'is_ok' => 'integer'];
}