<?php

declare (strict_types=1);
namespace Mwenju\Common\Model;

use Mwenju\Common\Model\Model;
/**
 * @property int $id 
 * @property int $shop_id 
 * @property int $add_time 
 * @property int $search_sw_id 
 */
class MfSearchSwHistory extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected ?string $table = 'mf_search_sw_history';
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
    protected array $casts = ['id' => 'integer', 'shop_id' => 'integer', 'add_time' => 'integer', 'search_sw_id' => 'integer'];
}