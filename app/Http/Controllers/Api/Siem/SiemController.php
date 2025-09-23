<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cookie;
use App\Notifications\NewUserRegisterNotification;
use Spatie\Permission\Models\Role;
use App\Events\UserlogProcessed;
use App\Models\UserCyberV6;
use OwenIt\Auditing\Facades\Auditor;


class SiemController extends Controller
{
    /**
     * Create a new SiemController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }


}
