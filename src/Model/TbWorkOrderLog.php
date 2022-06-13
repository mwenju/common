<?php

declare (strict_types=1);
namespace Mwenju\Common\Model;

use Mwenju\Common\Model\Model;
/**
 * @property int $id 
 * @property int $work_order_id 
 * @property int $admin_id 
 * @property int $audit_status 
 * @property string $remark 
 * @property string $create_time 
 */
class TbWorkOrderLog extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected ?string $table = 'tb_work_order_log';
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
    protected array $casts = ['id' => 'integer', 'work_order_id' => 'integer', 'admin_id' => 'integer', 'audit_status' => 'integer'];
}