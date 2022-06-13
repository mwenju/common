<?php

declare (strict_types=1);
namespace Mwenju\Common\Model;

use Mwenju\Common\Model\Model;
/**
 * @property int $id 
 * @property int $user_id 
 * @property string $real_name 
 * @property string $person_code 
 * @property string $now_addr 
 * @property string $person_addr 
 * @property string $emergency_contact 
 * @property string $emergency_mobile 
 * @property string $other_mobiles 
 * @property int $education 
 * @property string $resume 
 * @property int $status 
 * @property int $role_ids 
 * @property string $theme 
 * @property int $in_time 
 * @property int $out_time 
 * @property int $top_depot_id 
 * @property string $create_time 
 * @property int $depot_id 
 */
class MfAdmin extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected ?string $table = 'mf_admin';
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
    protected array $casts = ['id' => 'integer', 'user_id' => 'integer', 'education' => 'integer', 'status' => 'integer', 'role_ids' => 'integer', 'in_time' => 'integer', 'out_time' => 'integer', 'top_depot_id' => 'integer', 'depot_id' => 'integer'];
}