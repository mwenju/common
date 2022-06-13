<?php

declare (strict_types=1);
namespace Mwenju\Common\Model;

use Mwenju\Common\Model\Model;
/**
 * @property int $id 
 * @property int $shop_id 
 * @property int $user_id 
 * @property string $after_balance 
 * @property int $add_type 
 * @property string $add_num 
 * @property string $why_info 
 * @property string $create_time 
 * @property string $ip 
 */
class MfUserBalanceLog extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected ?string $table = 'mf_user_balance_log';
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
    protected array $casts = ['id' => 'integer', 'shop_id' => 'integer', 'user_id' => 'integer', 'add_type' => 'integer'];
}