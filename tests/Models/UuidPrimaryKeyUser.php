<?php

namespace Tests\Models;

use EvanSchleret\LaravelUuidable\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Model;

class UuidPrimaryKeyUser extends Model
{
    use HasUuidPrimaryKey;

    protected $table = 'users';

    protected $guarded = [];
}
