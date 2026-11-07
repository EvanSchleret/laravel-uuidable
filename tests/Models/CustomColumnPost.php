<?php

namespace Tests\Models;

class CustomColumnPost extends Post
{
    protected function uuidColumn(): string
    {
        return 'public_id';
    }
}
