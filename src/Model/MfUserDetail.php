<?php

declare (strict_types=1);
namespace Mwenju\Common\Model;

use Mwenju\Common\Model\Model;
/**
 * @property int $id 
 * @property int $user_id 
 * @property string $real_name 
 * @property string $sign 
 * @property int $reg_time 
 * @property string $reg_ip 
 * @property int $sex 
 * @property string $addr 
 * @property string $hobby 
 * @property string $school 
 * @property string $birthday 
 */
class MfUserDetail extends Model
{
    public bool $timestamps = false;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected ?string $table = 'mf_user_detail';
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
    protected array $casts = ['id' => 'integer', 'user_id' => 'integer', 'reg_time' => 'integer', 'sex' => 'integer'];
}