<?php

declare (strict_types=1);
namespace Mwenju\Common\Model;

use Mwenju\Common\Model\Model;
/**
 * @property int $id 
 * @property int $admin_id 
 * @property int $user_id 
 * @property int $top_depot_id 
 * @property int $supplier_id 
 * @property string $order_code 
 * @property string $total_price 
 * @property int $total_num 
 * @property int $type_total_num 
 * @property int $status 
 * @property string $audit_time 
 * @property int $audit_admin_id 
 * @property string $create_time 
 * @property int $is_out 
 */
class TbStockInOrder extends Model
{
    public bool $timestamps = false;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected ?string $table = 'tb_stock_in_order';
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
    protected array $casts = ['id' => 'integer', 'admin_id' => 'integer', 'user_id' => 'integer', 'top_depot_id' => 'integer', 'supplier_id' => 'integer', 'total_num' => 'integer', 'type_total_num' => 'integer', 'status' => 'integer', 'audit_admin_id' => 'integer', 'is_out' => 'integer'];
}