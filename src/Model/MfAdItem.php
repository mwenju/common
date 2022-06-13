<?php

declare (strict_types=1);
namespace Mwenju\Common\Model;

use Mwenju\Common\Model\Model;
/**
 * @property int $id 
 * @property int $ad_type_id 
 * @property string $items_name 
 * @property string $image_path 
 * @property string $link_url 
 * @property string $bg_color 
 * @property int $is_hide 
 * @property int $is_delete 
 * @property string $start_time 
 * @property string $end_time 
 * @property int $sort 
 * @property string $create_time 
 */
class MfAdItem extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected ?string $table = 'mf_ad_items';
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
    protected array $casts = ['id' => 'integer', 'ad_type_id' => 'integer', 'is_hide' => 'integer', 'is_delete' => 'integer', 'sort' => 'integer'];
}