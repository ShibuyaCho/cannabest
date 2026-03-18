<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Activity;
use App\Models\Role;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    public function __construct()
    {
        $this->middleware('guest')->except(['logout', 'showLoginForm']);
    }

    public function login(Request $request)
    {
        Log::info('Login attempt', ['email' => $request->email]);

        $this->validateLogin($request);

        if ($this->attemptLogin($request)) {
            Log::info('Login successful', ['email' => $request->email]);
            return $this->sendLoginResponse($request);
        }

        Log::info('Login failed', ['email' => $request->email]);
        return $this->sendFailedLoginResponse($request);
    }

     public function logout(Request $request)
    {
        Activity::insert([
            "user_id"   => Auth::user()->id,
            "datetime"  => date("Y-m-d h:i:s"),
            "activity"  => "Logout"
        ]);
        $this->guard()->logout();
        $request->session()->invalidate();
        return redirect('/login');
    }

    public function showLoginForm()
    {
        if (Auth::check()) {
            return $this->handleAuthenticatedUser(Auth::user());
        }

        $roles = Role::all();
        return view('auth.login', compact('roles'));
    }

    protected function sendLoginResponse(Request $request)
    {
        $request->session()->regenerate();
        $this->clearLoginAttempts($request);

        $user = $this->guard()->user();
        Log::info('Sending login response', ['user_id' => $user->id, 'role_id' => $user->role_id]);

        return $this->authenticated($request, $user)
            ?: redirect()->intended($this->redirectPath());
    }

    protected function authenticated(Request $request, $user)
    {
        return $this->handleAuthenticatedUser($user);
    }

    public function username()
    {
        return 'email';
    }

    protected function validateLogin(Request $request)
    {
        $request->validate([
            $this->username() => 'required|string',
            'password' => 'required|string',
        ]);
    }

    private function handleAuthenticatedUser($user)
    {
        Log::info('Handling authenticated user', ['user_id' => $user->id, 'role_id' => $user->role_id]);

        if ($user->role_id == 1) { // Super Admin
            return redirect()->route('superadmin.dashboard');
        }

        if ($user->role_id == 5) { // Retail customer
            return redirect()->route('retail.public-marketplace');
        }

        if ($user->role_id == 6) { // Wholesale customer
            return redirect()->route('wholesale.public-marketplace');
        }

        if (!$user->organization) {
            return $this->handleNoOrganization($user);
        }

        $orgType = $user->organization->type;
        $roleId = $user->role_id;

        try {
            switch ($orgType) {
                case 'wholesale':
                    return $this->wholesaleRedirect($roleId);
                case 'retail':
                    return $this->retailRedirect($roleId);
                default:
                    throw new \Exception('Unknown organization type');
            }
        } catch (\Exception $e) {
            Log::error('Login redirection error: ' . $e->getMessage());
            Auth::logout();
            return redirect()->route('login')
                ->with('error', 'An error occurred during login. Please try again or contact support.');
        }
    }

    private function wholesaleRedirect($roleId)
    {
        switch ($roleId) {
            case 2: // Admin
                return redirect()->route('admin.wholesale.dashboard');
            case 3: // Employee
                return redirect()->route('wholesale.employee.dashboard');
            case 6: // Customer
                return redirect()->route('wholesale.customer.dashboard');
            default:
                return redirect()->route('error.unknown-role');
        }
    }

   private function retailRedirect($roleId)
{
    switch ($roleId) {
        case 2: // Admin
        case 3: // Employee
        case 4: // Budtender (standard user)
            return redirect()->route('sales.create');

        case 5: // Customer
            if (! Route::has('retail.customer.dashboard')) {
                throw new \Exception('Retail customer dashboard route not defined');
            }
            return redirect()->route('retail.customer.dashboard');

        default:
            throw new \Exception('Unknown role for retail organization');
    }
}


    private function handleNoOrganization($user)
    {
        $intendedUrl = session('url.intended', '/');
        session()->forget('url.intended');
        return redirect()->to($intendedUrl);
    }
}