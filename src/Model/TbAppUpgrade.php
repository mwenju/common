<?php

declare (strict_types=1);
namespace Mwenju\Common\Model;

use Mwenju\Common\Model\Model;
/**
 * @property int $id 
 * @property int $app_type 
 * @property string $device_type 
 * @property string $new_version 
 * @property string $min_version 
 * @property string $apk_url 
 * @property string $wgt_url 
 * @property string $update_title 
 * @property string $update_description 
 * @property int $is_update 
 * @property int $force_update 
 * @property string $create_time 
 * @property int $enable 
 */
class TbAppUpgrade extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected ?string $table = 'tb_app_upgrade';
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
    protected array $casts = ['id' => 'integer', 'app_type' => 'integer', 'is_update' => 'integer', 'force_update' => 'integer', 'enable' => 'integer'];
}