<?php

namespace Tests\Models;

use EvanSchleret\LaravelUuidable\Concerns\HasRoutableUuid;
use Illuminate\Database\Eloquent\Model;

class RoutablePost extends Model
{
    use HasRoutableUuid;

    protected $table = 'posts';

    protected $guarded = [];
}
