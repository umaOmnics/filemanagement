<?php

namespace Omnics\FileManagement\Models;

use Illuminate\Database\Eloquent\Model;

class FileEntity extends Model
{
    protected $table = 'files_entities';

    protected $fillable = [
        'files_id',
        'entity_type',
        'entity_id',
        'created_at',
        'updated_at'
    ];
}
