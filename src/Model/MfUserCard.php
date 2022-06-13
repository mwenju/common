<?php

declare (strict_types=1);
namespace Mwenju\Common\Model;

use Mwenju\Common\Model\Model;
/**
 * @property int $id 
 * @property string $card_number 
 * @property int $user_id 
 * @property int $shop_id 
 * @property string $mobile 
 * @property string $real_name 
 * @property string $shop_name 
 * @property string $create_time 
 * @property string $active_time 
 * @property string $balance 
 * @property string $balance_total 
 * @property int $integral 
 * @property int $integral_total 
 * @property int $batch_num 
 * @property int $state 
 * @property string $qr_url 
 * @property string $key_number 
 */
class MfUserCard extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected ?string $table = 'mf_user_card';
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
    protected array $casts = ['id' => 'integer', 'user_id' => 'integer', 'shop_id' => 'integer', 'integral' => 'integer', 'integral_total' => 'integer', 'batch_num' => 'integer', 'state' => 'integer'];
}