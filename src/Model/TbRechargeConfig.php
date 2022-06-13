<?php

declare (strict_types=1);
namespace Mwenju\Common\Model;

use Mwenju\Common\Model\Model;
/**
 * @property int $id 
 * @property int $scene 
 * @property string $title 
 * @property string $money 
 * @property string $pix 
 * @property int $coupon_template_id 
 * @property string $beizhu 
 * @property int $sort 
 * @property int $is_show 
 */
class TbRechargeConfig extends Model
{
    public bool $timestamps = false;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected ?string $table = 'tb_recharge_config';
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
    protected array $casts = ['id' => 'integer', 'scene' => 'integer', 'coupon_template_id' => 'integer', 'sort' => 'integer', 'is_show' => 'integer'];
}