<?php

namespace Omnics\FileManagement\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use SoftDeletes, HasFactory;
    protected $table = 'tasks';

    /**
     * Get the Tags of the Task.
     */
    public function tags()
    {
        return $this->belongsToMany(Tags::class, 'tasks_tags', 'tasks_id', 'tags_id');
    }
}
