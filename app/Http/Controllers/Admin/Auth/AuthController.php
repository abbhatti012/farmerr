<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Services\AdminAuthService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    protected $authService;

    public function __construct(AdminAuthService $authService)
    {
        $this->authService = $authService;
    }

    public function redirectToLogin()
    {   
        
        return redirect()->route('admin.login');
    }

    public function loginForm()
    {
        return view('admin.auth.login');
    }

    public function login(Request $request)
    { 
        $this->validate($request, [
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);
        logger()->info('Login Admin', ['body' => $request->all()]);
        try {
            $this->authService->login($request->email, $request->password);
            logger()->error('Login Admin Success', ['body' => $request->all()]);
            return redirect()->route('admin.dashboard');
        } catch (Exception $e) {
            logger()->error('Login Admin Error', ['error' => $e->getMessage()]);

            return back()->withErrors($e->getMessage());
        }
    }

    public function logout()
    {
        Auth::guard('admin')->logout();
        return redirect()->route('admin.login');
    }
}
