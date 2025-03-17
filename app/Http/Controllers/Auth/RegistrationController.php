<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;

class RegistrationController extends Controller
{
    public function index(){
        return view ('auth.register');
    }
}
