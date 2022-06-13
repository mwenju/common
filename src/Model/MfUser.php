<?php

declare (strict_types=1);
namespace Mwenju\Common\Model;

use Mwenju\Common\Model\Model;
/**
 * @property int $id 
 * @property string $mobile 
 * @property string $nick_name 
 * @property string $face_img 
 * @property string $pwd 
 * @property int $last_time 
 * @property string $reg_ip 
 * @property string $last_ip 
 * @property string $wx_openid 
 * @property string $wx_unionid 
 * @property string $app_status 
 * @property string $token 
 * @property string $create_time 
 * @property string $device_type 
 * @property string $last_client_id 
 * @property int $is_delete 
 */
class MfUser extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected ?string $table = 'mf_user';
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
    protected array $casts = ['id' => 'integer', 'last_time' => 'integer', 'is_delete' => 'integer'];
}