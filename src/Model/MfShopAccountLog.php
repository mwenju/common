<?php

declare (strict_types=1);
namespace Mwenju\Common\Model;

use Mwenju\Common\Model\Model;
/**
 * @property int $id 
 * @property int $shop_id 
 * @property string $why_info 
 * @property string $account_field 
 * @property int $do_user_id 
 * @property string $add_num 
 * @property int $in_or_out 
 * @property int $add_type 
 * @property string $create_time 
 */
class MfShopAccountLog extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected ?string $table = 'mf_shop_account_log';
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
    protected array $casts = ['id' => 'integer', 'shop_id' => 'integer', 'do_user_id' => 'integer', 'in_or_out' => 'integer', 'add_type' => 'integer'];
}