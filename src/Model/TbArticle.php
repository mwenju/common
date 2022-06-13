<?php

declare (strict_types=1);
namespace Mwenju\Common\Model;

use Mwenju\Common\Model\Model;
/**
 * @property int $id 
 * @property string $title 
 * @property string $content 
 * @property int $type_id 
 * @property string $create_time 
 * @property string $update_time 
 * @property int $admin_id 
 * @property string $list_img 
 */
class TbArticle extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected ?string $table = 'tb_article';
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
    protected array $casts = ['id' => 'integer', 'type_id' => 'integer', 'admin_id' => 'integer'];
}