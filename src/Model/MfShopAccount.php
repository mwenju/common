<?php

declare (strict_types=1);
namespace Mwenju\Common\Model;

use Mwenju\Common\Model\Model;
/**
 * @property int $id 
 * @property int $shop_id 
 * @property int $user_id 
 * @property int $experience 
 * @property string $enable_money 
 * @property string $freeze_money 
 * @property string $all_money 
 * @property int $enable_integrate 
 * @property int $all_integrate 
 * @property int $last_do_user_id 
 * @property int $last_do_time 
 * @property string $user_recharge_money 
 * @property int $user_recharge_count 
 * @property string $freeze_recharge_money 
 * @property string $enable_recharge_money 
 */
class MfShopAccount extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected ?string $table = 'mf_shop_account';
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
    protected array $casts = ['id' => 'integer', 'shop_id' => 'integer', 'user_id' => 'integer', 'experience' => 'integer', 'enable_integrate' => 'integer', 'all_integrate' => 'integer', 'last_do_user_id' => 'integer', 'last_do_time' => 'integer', 'user_recharge_count' => 'integer'];
}