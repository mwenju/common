<?php

declare (strict_types=1);
namespace Mwenju\Common\Model;

use Mwenju\Common\Model\Model;
/**
 * @property int $id 
 * @property int $shop_id 
 * @property int $user_id 
 * @property int $after_integral 
 * @property int $add_type 
 * @property int $add_num 
 * @property string $why_info 
 * @property string $create_time 
 * @property string $ip 
 */
class MfUserIntegralLog extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected ?string $table = 'mf_user_integral_log';
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
    protected array $casts = ['id' => 'integer', 'shop_id' => 'integer', 'user_id' => 'integer', 'after_integral' => 'integer', 'add_type' => 'integer', 'add_num' => 'integer'];
}