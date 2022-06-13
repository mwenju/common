<?php

declare (strict_types=1);
namespace Mwenju\Common\Model;

use Mwenju\Common\Model\Model;
use Hyperf\ModelCache\Cacheable;
use Hyperf\ModelCache\CacheableInterface;

/**
 * @property int $id 
 * @property string $cname 
 * @property string $pinyin 
 * @property string $logo 
 * @property int $add_user_id 
 * @property int $add_time 
 * @property string $desc 
 * @property int $product_per 
 * @property int $is_show 
 */
class MfBrand extends Model implements CacheableInterface
{
    use Cacheable;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected ?string $table = 'mf_brand';
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
    protected array $casts = ['id' => 'integer', 'add_user_id' => 'integer', 'add_time' => 'integer', 'product_per' => 'integer', 'is_show' => 'integer'];
}