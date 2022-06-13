<?php

declare (strict_types=1);
namespace Mwenju\Common\Model;

use Mwenju\Common\Model\Model;
/**
 * @property int $id 
 * @property int $shop_id 
 * @property string $descr 
 * @property int $state 
 * @property string $create_time 
 * @property int $recharge_id 
 */
class TbGiftLog extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected ?string $table = 'tb_gift_log';
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
    protected array $casts = ['id' => 'integer', 'shop_id' => 'integer', 'state' => 'integer', 'recharge_id' => 'integer'];
}