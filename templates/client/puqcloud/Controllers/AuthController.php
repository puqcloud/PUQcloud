<?php

/*
 * PUQcloud - Free Cloud Billing System
 * Main billing system core logic
 *
 * Copyright (C) 2025 PUQ sp. z o.o.
 * Licensed under GNU GPLv3
 * https://www.gnu.org/licenses/gpl-3.0.html
 *
 * Author: Ruslan Polovyi <ruslan@polovyi.com>
 * Website: https://puqcloud.com
 * E-mail: support@puqcloud.com
 *
 * Do not remove this header.
 */

namespace Template\Client\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\HookService;
use App\Services\SettingService;
use Illuminate\Contracts\View\Factory;
use Illuminate\Foundation\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function loginForm(): View|Factory|Application
    {
        $title = __('main.Login');

        return view_client('login.login_form', compact('title'));
    }

    public function signUpForm(): View|Factory|Application
    {
        $title = __('main.Registration');

        return view_client('login.sign_up_form', compact('title'));
    }

    public function passwordLostForm(): View|Factory|Application
    {
        $title = __('main.Request new password');

        return view_client('login.password_lost', compact('title'));
    }

    public function passwordLostRequested(): View|Factory|Application
    {
        $title = __('main.Request new password');

        return view_client('login.password_lost_requested', compact('title'));
    }

    public function passwordResetForm(Request $request, $token): View|Factory|Application
    {
        $title = __('main.Request new password');

        if (! $token) {
            abort(404);
        }

        $expire = SettingService::get('client.user_reset_password_url_expire');

        $user = User::query()
            ->where('password_reset_token', $token)
            ->where('password_reset_token_created_at', '>=', now()->subMinutes($expire))
            ->first();

        if (! $user) {
            abort(404);
        }

        return view_client('login.password_lost_reset', compact('title', 'token', 'user'));
    }

    public function postLogin(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ], [
            'email.required' => __('error.Email is required'),
            'email.email' => __('error.Invalid email format'),
            'password.required' => __('error.Password is required'),
            'password.string' => __('error.Password must be a string'),
            'password.min' => __('error.Password must be at least 6 characters'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $credentials = $request->only('email', 'password');
        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'message' => ['email' => [__('error.Invalid email or password')], 'password' => [__('error.Invalid email or password')]],
            ], 401);
        }

        if ($user->two_factor) {
            $verification = $user->verifications()->where('default', true)->first();
            if (! $verification) {
                return response()->json([
                    'errors' => [__('error.Verification method for two-factor authentication not set')],
                ], 404);
            }

            if (! $request->has('code')) {
                $generate_code = $verification->confirmationSendVerificationCode();

                return response()->json([
                    'status' => 'success',
                    'data' => $generate_code,
                ]);
            }

            $verify = $verification->confirmationVerifyCode($request->get('code'));

            if (! $verify) {
                return response()->json([
                    'errors' => [__('error.Verification failed')],
                    'message' => ['code' => [__('error.Invalid code')]],
                ], 422);
            }

        }

        app(HookService::class)->callHooks('UserBeforeLogin', $credentials);
        if (Auth::guard('client')->attempt($credentials, true)) {
            $user = Auth::guard('client')->user();
            app(HookService::class)->callHooks('UserAfterLogin', ['user' => $user]);
            logActivity(
                'info',
                get_class($user).':'.$user->uuid,
                'Client area login',
                request()->ip(),
                null,
                $user->uuid
            );
            if ($user->disable) {
                app(HookService::class)->callHooks('UserBeforeLogout', ['user' => $user]);
                Auth::guard('client')->logout();
                app(HookService::class)->callHooks('UserAfterLogout', ['user' => $user]);
                logActivity(
                    'info',
                    get_class($user).':'.$user->uuid,
                    'Client area logout',
                    request()->ip(),
                    null,
                    $user->uuid
                );

                return response()->json([
                    'errors' => [__('error.Your account is disabled')],
                    'message' => ['email' => [__('error.Your account is disabled')], 'password' => [__('error.Your account is disabled')]],
                ], 401);
            }

            $user->status = 'active';
            $user->save();

            return response()->json([
                'message' => __('message.Successful authorization'),
                'redirect' => route('client.web.panel.dashboard'),
            ], 200);
        }

        $credentials['date'] = Date::now();
        $credentials['ip'] = $request->ip();
        $credentials['r_dns'] = gethostbyaddr($credentials['ip']);
        app(HookService::class)->callHooks('UserFailedAuthorization', $credentials);
        logActivity(
            'warning',
            'Invalid email or password. Failed Authorization: '.$credentials['email'],
            'Client area login',
            request()->ip(),
        );

        return response()->json([
            'message' => ['email' => [__('error.Invalid email or password')], 'password' => [__('error.Invalid email or password')]],
        ], 401);

    }

    public function postSignUp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed', // requires 'password_confirmation' field
        ], [
            'email.required' => __('error.Email is required'),
            'email.email' => __('error.Invalid email format'),
            'email.unique' => __('error.Email already taken'),
            'password.required' => __('error.Password is required'),
            'password.string' => __('error.Password must be a string'),
            'password.min' => __('error.Password must be at least 6 characters'),
            'password.confirmed' => __('error.Passwords do not match'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $data = [
            'email' => $request->input('email'),
            'password' => Hash::make($request->input('password')),
        ];

        app(HookService::class)->callHooks('UserBeforeRegister', $data);

        $user = new User;
        $user->email = $data['email'];
        $user->password = $data['password'];
        $user->firstname = explode('@', $data['email'])[0];
        $user->language = session('locale', config('locale.client.default'));
        $user->save();

        logActivity(
            'info',
            get_class($user).':'.$user->uuid,
            'The user has registered',
            request()->ip(),
            null,
            $user->uuid
        );
        app(HookService::class)->callHooks('UserAfterRegister', ['user' => $user]);

        Auth::guard('client')->login($user, true);

        app(HookService::class)->callHooks('UserAfterLogin', ['user' => $user]);
        logActivity(
            'info',
            get_class($user).':'.$user->uuid,
            'Client area auto login after registration',
            request()->ip(),
            null,
            $user->uuid
        );

        return response()->json([
            'message' => __('message.Successful registration'),
            'redirect' => route('client.web.panel.dashboard'),
        ], 200);
    }

    public function postResetPassword(Request $request): JsonResponse
    {
        $token = $request->get('token');

        if (! empty($token)) {

            $validator = Validator::make($request->all(), [
                'password' => 'required|string|min:6|confirmed', // requires 'password_confirmation' field
            ], [
                'password.required' => __('error.Password is required'),
                'password.string' => __('error.Password must be a string'),
                'password.min' => __('error.Password must be at least 6 characters'),
                'password.confirmed' => __('error.Passwords do not match'),
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => $validator->errors(),
                ], 422);
            }

            $expire = SettingService::get('client.user_reset_password_url_expire');
            $user = User::query()
                ->where('password_reset_token', $token)
                ->where('password_reset_token_created_at', '>=', now()->subMinutes($expire))
                ->first();

            if (! $user) {
                return response()->json([
                    'errors' => [__('error.User does not exist or the token has expired')],
                ], 404);
            }

            $user->password = Hash::make($request->input('password'));
            $user->password_reset_token = null;
            $user->password_reset_token_created_at = now()->subMinutes($expire);
            $user->save();

            logActivity(
                'info',
                get_class($user).':'.$user->uuid,
                'Password reset completed by user',
                request()->ip(),
                null,
                $user->uuid
            );

            return response()->json([
                'message' => __('message.Successfully'),
                'redirect' => route('client.web.panel.login'),
            ], 200);
        }

        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ], [
            'email.required' => __('error.Email is required'),
            'email.email' => __('error.Invalid email format'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $data = [
            'email' => $request->input('email'),
        ];

        $user = User::query()->where('email', $data['email'])->first();
        if ($user) {
            $data['user'] = $user;
            $data['password_reset_token'] = $user->generatePasswordResetToken();
            $data['reset_password_url'] = route('client.web.panel.password_reset', $data['password_reset_token']);
            $data['expire'] = SettingService::get('client.user_reset_password_url_expire');
            logActivity(
                'info',
                get_class($user).':'.$user->uuid,
                'The user has requested a password reset',
                request()->ip(),
                null,
                $user->uuid
            );
            app(HookService::class)->callHooks('UserResetPassword', $data);
        }

        return response()->json([
            'message' => __('message.Successfully'),
            'redirect' => route('client.web.panel.password_lost.requested'),
        ], 200);
    }

    public function getLogout(): JsonResponse
    {
        if (Auth::guard('client')->check()) {
            $user = Auth::guard('client')->user();
            app(HookService::class)->callHooks('UserBeforeLogout', ['user' => $user]);
            Auth::guard('client')->logout();
            app(HookService::class)->callHooks('UserAfterLogout', ['user' => $user]);
            logActivity(
                'info',
                get_class($user).':'.$user->uuid,
                'Client area logout',
                request()->ip(),
                null,
                $user->uuid
            );
        }

        session()->forget('client_uuid');

        return response()->json([
            'message' => __('message.Logout out successfully'),
            'redirect' => route('client.web.home'),
        ], 200);
    }

    public function localeSwitch(Request $request, $locale): RedirectResponse
    {
        if (array_key_exists($locale, config('locale.client.locales'))) {
            session(['locale' => $locale]);
            App::setLocale($locale);
            app()->setLocale($locale);
        }

        $referer = $request->headers->get('referer');

        return redirect()->to($referer);
    }
}
