<?php

namespace App\Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Http\Requests\LoginRequest;
use App\Modules\Auth\Services\AuthService;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService
    ) {
    }

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request)
    {
        return $this->authService->login(
            $request->validated(),
            $request
        );
    }

    public function selectRolePage()
    {
        return $this->authService->selectRolePage();
    }

    public function setRole(Request $request)
    {
        return $this->authService->setRole(
            $request->role
        );
    }

    public function logout(Request $request)
    {
        return $this->authService->logout(
            $request
        );
    }
}