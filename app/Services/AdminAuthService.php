<?php

namespace App\Services;

use App\Models\Admin;
use Illuminate\Support\Facades\Auth;
use Exception;

/**
 * Class AdminAuthService.
 */
class AdminAuthService
{
    /**
     * Attempt to login the admin user.
     *
     * @param string $email
     * @param string $password
     * @return bool
     * @throws Exception
     */
    public function login(string $email, string $password): bool
    {
        if (Auth::guard('admin')->attempt(['email' => $email, 'password' => $password])) {
            return true;
        }

        throw new Exception('Invalid Email or Password');
    }

    /**
     * Login the admin user directly using email.
     *
     * @param string $email
     * @return void
     * @throws Exception
     */
    public function direct_login(string $email): void
    {
        $admin_user = Admin::where('email', $email)->firstOrFail();
        Auth::guard('admin')->loginUsingId($admin_user->id);
    }
}
