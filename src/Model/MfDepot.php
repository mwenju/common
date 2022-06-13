<?php

declare (strict_types=1);
namespace Mwenju\Common\Model;

use Mwenju\Common\Model\Model;
use Hyperf\ModelCache\Cacheable;
use Hyperf\ModelCache\CacheableInterface;

/**
 * @property int $id 
 * @property string $cname 
 * @property string $code_val 
 * @property int $parent_id 
 * @property int $is_seat 
 * @property int $status 
 * @property int $top_depot_id 
 * @property string $cname_bak 
 * @property string $S1 
 * @property string $S2 
 * @property int $S3 
 * @property int $S4 
 */
class MfDepot extends Model implements CacheableInterface
{
    use Cacheable;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected ?string $table = 'mf_depot';
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
    protected array $casts = ['id' => 'integer', 'parent_id' => 'integer', 'is_seat' => 'integer', 'status' => 'integer', 'top_depot_id' => 'integer', 'S3' => 'integer', 'S4' => 'integer'];
}