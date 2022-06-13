<?php

declare (strict_types=1);
namespace Mwenju\Common\Model;

use Mwenju\Common\Model\Model;
/**
 * @property int $id 
 * @property string $create_time 
 * @property int $admin_id 
 * @property int $user_id 
 * @property string $real_name 
 * @property string $uri 
 * @property string $param 
 * @property string $user_agent 
 * @property string $ip 
 * @property string $remark 
 */
class MfAdminLog extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected ?string $table = 'mf_admin_log';
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
    protected array $casts = ['id' => 'integer', 'admin_id' => 'integer', 'user_id' => 'integer'];
}