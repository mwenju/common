<?php

declare (strict_types=1);
namespace Mwenju\Common\Model;

use Mwenju\Common\Model\Model;
/**
 * @property int $id 
 * @property string $title 
 * @property string $content 
 * @property int $shop_id 
 * @property string $source 
 * @property string $img_urls 
 * @property string $create_time 
 * @property int $is_delete 
 * @property string $delete_time 
 * @property int $read_total 
 * @property int $praise_total 
 * @property string $end_time 
 * @property int $sort 
 */
class MfUserNews extends Model
{
    public bool $timestamps = false;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected ?string $table = 'mf_user_news';
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
    protected array $casts = ['id' => 'integer', 'shop_id' => 'integer', 'is_delete' => 'integer', 'read_total' => 'integer', 'praise_total' => 'integer', 'sort' => 'integer'];
}