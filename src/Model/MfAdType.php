<?php

declare (strict_types=1);
namespace Mwenju\Common\Model;

use Mwenju\Common\Model\Model;
/**
 * @property int $id 
 * @property string $type_name 
 * @property int $is_hide 
 * @property int $sort 
 * @property string $tag 
 */
class MfAdType extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected ?string $table = 'mf_ad_type';
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
    protected array $casts = ['id' => 'integer', 'is_hide' => 'integer', 'sort' => 'integer'];
}