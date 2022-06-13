<?php

declare (strict_types=1);
namespace Mwenju\Common\Model;

use Mwenju\Common\Model\Model;
/**
 * @property int $id 
 * @property int $shop_id 
 * @property int $is_online 
 * @property string $three_name 
 * @property string $three_card 
 * @property int $money_num 
 * @property int $status 
 * @property string $system_name 
 * @property string $system_card 
 * @property string $remark 
 * @property int $add_time 
 * @property int $add_user_id 
 */
class MfShopRechargeHistory extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected ?string $table = 'mf_shop_recharge_history';
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
    protected array $casts = ['id' => 'integer', 'shop_id' => 'integer', 'is_online' => 'integer', 'money_num' => 'integer', 'status' => 'integer', 'add_time' => 'integer', 'add_user_id' => 'integer'];
}