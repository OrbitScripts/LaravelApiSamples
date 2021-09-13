<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Class AvtocodModel
 * @package App\Models
 *
 * @property int $id
 * @property string $avtocod_model
 * @property string $avtocod_model_id
 * @property int $avtocod_to_default_mark_id
 * @property int $default_id
 *
 * @method static Builder|AvtocodModel whereAvtocodModelId(string $markId)
 */
class AvtocodModel extends Model
{
    protected $table = 'avtocod_to_default_models';
    protected $fillable = ['avtocod_model', 'avtocod_model_id', 'avtocod_to_default_mark_id', 'default_id'];
    public $timestamps = false;

    public function default() {
        return $this->hasOne('App\Models\DefaultTS')->first();
    }
}
