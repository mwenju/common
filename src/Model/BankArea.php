<?php

declare (strict_types=1);
namespace Mwenju\Common\Model;

use Mwenju\Common\Model\Model;
/**
 * @property int $id 
 * @property int $pay_channel 
 * @property string $area_name 
 * @property int $parent_id 
 * @property string $area_code 
 * @property string $parent_code 
 * @property string $create_time 
 * @property int $is_delete 
 * @property string $ext1 
 */
class BankArea extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected ?string $table = 'bank_area';
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
    protected array $casts = ['id' => 'integer', 'pay_channel' => 'integer', 'parent_id' => 'integer', 'is_delete' => 'integer'];
}