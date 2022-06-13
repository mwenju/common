<?php

declare (strict_types=1);
namespace Mwenju\Common\Model;

use Mwenju\Common\Model\Model;
/**
 * @property int $id 
 * @property string $order_code 
 * @property int $shop_id 
 * @property int $order_type 
 * @property int $order_id 
 * @property int $admin_id 
 * @property int $audit_status 
 * @property string $create_time 
 * @property string $audit_time 
 * @property string $remark 
 */
class TbWorkOrder extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected ?string $table = 'tb_work_order';
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
    protected array $casts = ['id' => 'integer', 'shop_id' => 'integer', 'order_type' => 'integer', 'order_id' => 'integer', 'admin_id' => 'integer', 'audit_status' => 'integer'];
}