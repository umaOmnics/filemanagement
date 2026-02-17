<?php

namespace Omnics\FileManagement\Models;

use Illuminate\Database\Eloquent\Model;

class Tags extends Model
{
    protected $table = 'tags';
    protected $hidden = ['module'];
}
