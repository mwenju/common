<?php

declare (strict_types=1);
namespace Mwenju\Common\Model;

use Mwenju\Common\Model\Model;
/**
 * @property int $id 
 * @property string $cname 
 * @property int $parent_id 
 * @property int $is_group 
 * @property int $is_submenus 
 * @property string $node_uri 
 * @property string $jsfunc 
 * @property string $other_param 
 * @property string $open_page_type 
 * @property string $style_class 
 * @property int $add_time 
 */
class MfAdminMenu extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected ?string $table = 'mf_admin_menus';
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
    protected array $casts = ['id' => 'integer', 'parent_id' => 'integer', 'is_group' => 'integer', 'is_submenus' => 'integer', 'add_time' => 'integer'];
}