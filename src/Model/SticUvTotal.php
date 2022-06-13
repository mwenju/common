<?php

declare (strict_types=1);
namespace Mwenju\Common\Model;

use Mwenju\Common\Model\Model;
/**
 * @property int $id 
 * @property string $tag 
 * @property int $total_num 
 * @property string $update_time 
 * @property string $create_time 
 */
class SticUvTotal extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected ?string $table = 'stic_uv_total';
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
    protected array $casts = ['id' => 'integer', 'total_num' => 'integer'];
}