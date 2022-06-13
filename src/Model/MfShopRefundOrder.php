<?php

declare (strict_types=1);
namespace Mwenju\Common\Model;

use Mwenju\Common\Model\Model;
/**
 * @property int $id 
 * @property string $order_code 
 * @property int $shop_id 
 * @property string $refund_money 
 * @property string $remarks 
 * @property int $add_admin_id 
 * @property string $add_admin 
 * @property int $audit_admin_id 
 * @property string $audit_admin 
 * @property int $is_delete 
 * @property int $status 
 * @property string $create_time 
 * @property string $audit_time 
 */
class MfShopRefundOrder extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected ?string $table = 'mf_shop_refund_order';
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
    protected array $casts = ['id' => 'integer', 'shop_id' => 'integer', 'add_admin_id' => 'integer', 'audit_admin_id' => 'integer', 'is_delete' => 'integer', 'status' => 'integer'];
}