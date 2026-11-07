<?php

namespace Tests\Models;

use EvanSchleret\LaravelUuidable\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasUuid;

    protected $table = 'posts';

    protected $guarded = [];
}
