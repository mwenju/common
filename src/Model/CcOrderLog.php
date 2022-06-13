<?php

declare (strict_types=1);
namespace Mwenju\Common\Model;

use Mwenju\Common\Model\Model;
/**
 * @property int $id 
 * @property int $order_id 
 * @property int $admin_id 
 * @property int $status 
 * @property string $remark 
 * @property string $create_time 
 */
class CcOrderLog extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected ?string $table = 'cc_order_log';
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
    protected array $casts = ['id' => 'integer', 'order_id' => 'integer', 'admin_id' => 'integer', 'status' => 'integer'];
}