<?php

declare (strict_types=1);
namespace Mwenju\Common\Model;

use Mwenju\Common\Model\Model;
/**
 * @property int $id 
 * @property string $cname 
 * @property int $parent_id 
 * @property int $add_time 
 * @property string $admin_menus_ids 
 * @property string $menus_ids 
 * @property int $depot_id 
 */
class MfAdminRole extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected ?string $table = 'mf_admin_role';
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
    protected array $casts = ['id' => 'integer', 'parent_id' => 'integer', 'add_time' => 'integer', 'depot_id' => 'integer'];
}