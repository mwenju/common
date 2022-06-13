<?php

declare (strict_types=1);
namespace Mwenju\Common\Model;

use Mwenju\Common\Model\Model;
/**
 * @property int $id 
 * @property int $user_id 
 * @property string $mobile 
 * @property string $login_ip 
 * @property string $token 
 * @property string $uri 
 * @property string $device_type 
 * @property string $device_code 
 * @property string $user_agent 
 * @property string $create_time 
 * @property string $client_id 
 * @property string $param 
 */
class MfUserLoginLog extends Model
{
    public bool $timestamps = false;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected ?string $table = 'mf_user_login_log';
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
    protected array $casts = ['id' => 'integer', 'user_id' => 'integer'];
}