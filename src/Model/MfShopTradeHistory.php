<?php

declare (strict_types=1);
namespace Mwenju\Common\Model;

use Mwenju\Common\Model\Model;
/**
 * @property int $id 
 * @property int $shop_id 
 * @property string $why_info 
 * @property int $do_user_id 
 * @property int $do_time 
 * @property string $num 
 * @property int $in_or_out 
 * @property int $trade_type 
 * @property string $create_time 
 */
class MfShopTradeHistory extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected ?string $table = 'mf_shop_trade_history';
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
    protected array $casts = ['id' => 'integer', 'shop_id' => 'integer', 'do_user_id' => 'integer', 'do_time' => 'integer', 'in_or_out' => 'integer', 'trade_type' => 'integer'];
}