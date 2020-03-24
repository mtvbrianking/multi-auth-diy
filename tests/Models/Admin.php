<?php

namespace Tests\Models;

use App\Admin as AdminModel;
use Illuminate\Contracts\Auth\MustVerifyEmail;

class Admin extends AdminModel implements MustVerifyEmail
{
    // ...
}
