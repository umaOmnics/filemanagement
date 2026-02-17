<?php

namespace Omnics\FileManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Files extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'folders_id',
        'title',
        'original_name',
        'visibility',
        'size',
        'mime',
        'path',
        'object_key',
        'is_entity',
        'created_at',
        'updated_at'
    ];
    protected $table = 'files';
}
