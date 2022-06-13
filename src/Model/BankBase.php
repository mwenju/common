<?php

declare (strict_types=1);
namespace Mwenju\Common\Model;

use Mwenju\Common\Model\Model;
/**
 * @property int $id 
 * @property int $pay_channel 
 * @property string $bank_name 
 * @property string $bank_code 
 * @property string $create_time 
 * @property int $is_delete 
 * @property string $ext1 
 */
class BankBase extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected ?string $table = 'bank_base';
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
    protected array $casts = ['id' => 'integer', 'pay_channel' => 'integer', 'is_delete' => 'integer'];
}