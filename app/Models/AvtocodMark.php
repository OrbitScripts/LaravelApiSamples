<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Class AvtocodMark
 * @package App\Models
 *
 * @property int $id
 * @property string $avtocod_mark
 * @property string $avtocod_mark_id
 * @property string $default_mark
 *
 * @method static Builder|AvtocodMark whereAvtocodMarkId(string $markId)
 */
class AvtocodMark extends Model
{
    protected $table = 'avtocod_to_default_marks';
    protected $fillable = ['avtocod_mark', 'avtocod_mark_id', 'default_mark'];
    public $timestamps = false;
}
