<?php

declare (strict_types=1);
namespace Mwenju\Common\Model;

use Mwenju\Common\Model\Model;
use Hyperf\Database\Model\Collection;
use Hyperf\ModelCache\Cacheable;
use Hyperf\ModelCache\CacheableInterface;
/**
 * @property int $id 
 * @property int $parent_id 
 * @property string $cname 
 * @property string $icon_value 
 * @property int $level 
 * @property int $is_show 
 * @property string $product_param_ids 
 * @property string $marker_price_per 
 * @property string $sale_price_per 
 * @property string $path 
 * @property string $img_url 
 */
class TbProductType extends Model implements CacheableInterface
{
    use Cacheable;
    public static function getChild($id = 0, $chids = [])
    {
        $id = !is_array($id) ? [$id] : $id;
        $ids = self::whereIn('parent_id', $id)->pluck('id');
        if (count($ids) > 0) {
            $chids[] = $ids;
            return self::getChild($ids, $chids);
        } else {
            $chids = $id;
        }
        return $chids;
    }
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected ?string $table = 'tb_product_type';
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
    protected array $casts = ['id' => 'integer', 'parent_id' => 'integer', 'level' => 'integer', 'is_show' => 'integer'];
}