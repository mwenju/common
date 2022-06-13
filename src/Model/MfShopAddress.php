<?php

declare (strict_types=1);
namespace Mwenju\Common\Model;

use Mwenju\Common\Model\Model;
/**
 * @property int $id 
 * @property int $user_id 
 * @property int $shop_id 
 * @property string $link_name 
 * @property int $link_mobile 
 * @property string $area 
 * @property string $addr_detail 
 * @property string $create_time 
 * @property int $is_default 
 * @property string $province_code 
 * @property string $city_code 
 * @property string $area_code 
 * @property int $addr_type 
 */
class MfShopAddress extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected ?string $table = 'mf_shop_address';
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
    protected array $casts = ['id' => 'integer', 'user_id' => 'integer', 'shop_id' => 'integer', 'link_mobile' => 'integer', 'is_default' => 'integer', 'addr_type' => 'integer'];
}