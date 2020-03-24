<?php

namespace Tests\Models;

use App\User as UserModel;
use Illuminate\Contracts\Auth\MustVerifyEmail;

class User extends UserModel implements MustVerifyEmail
{
    // ...
}
