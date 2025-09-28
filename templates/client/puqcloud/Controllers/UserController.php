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
use App\Models\Verification;
use Illuminate\Contracts\View\Factory;
use Illuminate\Foundation\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use RobThree\Auth\Providers\Qr\EndroidQrCodeProvider;
use RobThree\Auth\TwoFactorAuth;
use RobThree\Auth\TwoFactorAuthException;
use Yajra\DataTables\DataTables;

class UserController extends Controller
{
    public function profile(): View|Factory|Application
    {
        $title = __('main.My Account');

        return view_client('user.profile', compact('title'));
    }

    public function getProfile(): JsonResponse
    {
        $user = app('user');

        $allowedKeys = [
            'firstname',
            'lastname',
            'language',
            'email',
            'phone_number',
            'email_verified',
        ];

        $filteredUserData = collect($user->toArray())
            ->only($allowedKeys)
            ->toArray();

        $language = [];

        foreach (config('locale.client.locales') as $key => $value) {
            if ($key == $user->language) {
                $language = [
                    'id' => $key,
                    'text' => $value['name'].' ('.$value['native'].')',
                ];
            }
        }

        $filteredUserData['language_data'] = $language;

        return response()->json([
            'data' => $filteredUserData,
        ], 200);
    }

    public function putProfile(Request $request): JsonResponse
    {
        $user = app('user');
        $locales = array_keys(config('locale.client.locales'));

        $validator = Validator::make($request->all(), [
            'email' => 'nullable|email|unique:users,email,'.$user->uuid.',uuid',
            'firstname' => 'nullable|string',
            'lastname' => 'nullable|string',
            'language' => 'nullable|in:'.implode(',', $locales),
        ], [
            'email.email' => __('error.The email must be a valid email address'),
            'email.unique' => __('error.This email is already taken'),
            'firstname.string' => __('error.The firstname must be a valid string'),
            'lastname.string' => __('error.The lastname must be a valid string'),
            'language.in' => __('error.The selected language is invalid'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        if ($request->has('email') && ! empty($request->input('email'))) {
            $user->email = $request->input('email');
        }

        if (! empty($request->input('firstname'))) {
            $user->firstname = $request->input('firstname');
        }

        if (! empty($request->input('lastname'))) {
            $user->lastname = $request->input('lastname');
        }

        if (! empty($request->input('language'))) {
            $user->language = $request->input('language');
        }

        if ($request->has('phone_number') and $request->has('country_code')) {
            $tel = str_replace(' ', '', $request->input('country_code').$request->input('phone_number'));
            if (! empty($tel)) {
                $user->phone_number = $tel;
                if (User::where('phone_number', $user->phone_number)
                    ->where('uuid', '!=', $user->uuid)
                    ->exists()) {
                    return response()->json([
                        'status' => 'error',
                        'errors' => [__('error.This phone number is already taken')],
                    ], 422);
                }
            }
        }

        if (! Hash::check($request->existingpw, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => ['existingpw' => [__('error.Existing password is incorrect')]],
            ],
                422);
        }

        if ($user->status === 'new') {
            $user->status = 'active';
        }

        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
        ]);
    }

    public function changePassword(): View|Factory|Application
    {
        $title = __('main.Change Password');

        return view_client('user.change_password', compact('title'));
    }

    public function putChangePassword(Request $request): JsonResponse
    {

        $validator = Validator::make($request->all(), [
            'existingpw' => 'required',
            'newpw' => ['required', 'string', 'min:6', 'regex:/[0-9]/', 'regex:/[!@#$%^&*(),.?":{}|<>]/'],
            'confirmpw' => 'required|same:newpw',
        ], [
            'existingpw.required' => __('error.Existing password is required'),
            'newpw.required' => __('error.New password is required'),
            'newpw.min' => __('error.Password must be at least 6 characters'),
            'newpw.regex' => __('error.Password must include a number and a special character'),
            'confirmpw.required' => __('error.Confirm password is required'),
            'confirmpw.same' => __('error.Passwords must match'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $user = app('user');

        if (! Hash::check($request->existingpw, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => ['existingpw' => [__('error.Existing password is incorrect')]],
            ],
                422);
        }

        $user->password = Hash::make($request->newpw);
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
        ]);

    }

    public function verificationCenter(): View|Factory|Application
    {
        $title = __('main.Verification Center');

        return view_client('user.verification_center', compact('title'));
    }

    public function getVerifications(Request $request): JsonResponse
    {
        $user = app('user');

        $allowedKeys = [
            'uuid',
            'type',
            'device_name',
            'value',
            'verified',
            'default',
            'last_used_at',
        ];

        $query = $user->verifications()->select($allowedKeys);

        return response()->json([
            'data' => DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && ! empty($request->search['value'])) {
                        $search = $request->search['value'];
                        $query->where(function ($q) use ($search) {
                            $q->where('type', 'like', "%{$search}%")
                                ->orWhere('value', 'like', "%{$search}%")
                                ->orWhere('device_name', 'like', "%{$search}%");
                        });
                    }
                })
                ->addColumn('urls', function ($query) {
                    $urls = [];
                    $urls['get'] = route('client.api.user.verification.get', ['uuid' => $query->uuid]);
                    if (! $query->verified) {
                        $urls['verify'] = route('client.api.user.verification.verify.get', ['uuid' => $query->uuid]);
                    }
                    $urls['delete'] = route('client.api.user.verification.delete', ['uuid' => $query->uuid]);

                    return $urls;
                })
                ->make(true),
        ], 200);
    }

    public function getVerification(Request $request, $uuid): JsonResponse
    {
        $user = app('user');
        $verification = $user->verifications()->where('uuid', $uuid)->where('verified', true)->first();
        if (empty($verification)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $generate_code = $verification->confirmationSendVerificationCode();

        return response()->json([
            'status' => 'success',
            'data' => $generate_code,
        ]);
    }

    public function getVerificationVerify(Request $request, $uuid): JsonResponse
    {
        $user = app('user');
        $verification = $user->verifications()->where('uuid', $uuid)->where('verified', false)->first();
        if (empty($verification)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $generate_code = $verification->verifySendVerificationCode();

        return response()->json([
            'status' => 'success',
            'data' => $generate_code,
        ]);
    }

    public function postVerificationVerify(Request $request): JsonResponse
    {
        $user = app('user');
        $uuid = $request->get('uuid');
        $code = $request->get('code');
        $verification = $user->verifications()->where('uuid', $uuid)->first();
        if (empty($verification)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $verify = $verification->verifyVerifyCode($code);

        if (! $verify) {
            return response()->json([
                'errors' => [__('error.Verification failed')],
            ], 422);
        }

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
        ]);
    }

    public function postVerificationDefault(Request $request): JsonResponse
    {
        $user = app('user');
        $uuid = $request->get('uuid');
        $code = $request->get('code');
        $verification = $user->verifications()->where('uuid', $uuid)->first();
        if (empty($verification)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $verify = $verification->confirmationVerifyCode($code);

        if (! $verify) {
            return response()->json([
                'errors' => [__('error.Verification failed')],
            ], 422);
        }

        $verification->makeDefault();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
        ]);
    }

    public function deleteVerification(Request $request, $uuid): JsonResponse
    {
        $user = app('user');
        $code = $request->get('code');
        $verification = $user->verifications()->where('default', true)->first();
        if (empty($verification)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $verify = $verification->confirmationVerifyCode($code);

        if (! $verify) {
            return response()->json([
                'errors' => [__('error.Verification failed')],
            ], 422);
        }

        $verification = $user->verifications()->where('uuid', $uuid)->first();
        if (empty($verification)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $verification->delete();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
        ]);
    }

    /**
     * @throws TwoFactorAuthException
     */
    public function getVerificationTotpAdd(Request $request): JsonResponse
    {
        $user = app('user');
        $host = parse_url(env('APP_URL'), PHP_URL_HOST);
        $tfa = new TwoFactorAuth(new EndroidQrCodeProvider, $host ?? 'PUQcloud');
        $secret = $tfa->createSecret();

        session(['totp_secret' => $secret]);

        $qrCodeDataUri = $tfa->getQRCodeImageAsDataUri($user->email, $secret);

        return response()->json([
            'status' => 'success',
            'data' => [
                'code' => $secret,
                'qrcode_uri' => $qrCodeDataUri,
            ],
        ]);
    }

    /**
     * @throws TwoFactorAuthException
     */
    public function postVerificationTotpAdd(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|digits:6',
            'device_name' => 'required',
        ], [
            'code.required' => __('error.Verify Key is required'),
            'code.digits' => __('error.Verify Key must be 6 digits'),
            'device_name.required' => __('error.Device Name is required'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $user = app('user');
        $host = parse_url(env('APP_URL'), PHP_URL_HOST);
        $tfa = new TwoFactorAuth(new EndroidQrCodeProvider, $host ?? 'PUQcloud');

        $secret = session('totp_secret');
        if (! $secret) {
            return response()->json(
                ['status' => 'error',
                    'errors' => [__('error.No secret found. Try again.')],
                ],
                400);
        }

        $isValid = $tfa->verifyCode($secret, $request->input('code'));

        if ($isValid) {
            $verification = new Verification;
            $verification->user_uuid = $user->uuid;
            $verification->secret = session('totp_secret');
            $verification->type = 'totp';
            $verification->value = $request->get('device_name');
            $verification->device_name = $request->get('device_name');
            $verification->last_used_at = now();
            $verification->verified = true;
            $verification->save();

            return response()->json(['status' => 'success', 'message' => __('message.Saved successfully')]);
        }

        return response()->json([
            'status' => 'error',
            'message' => ['code' => [__('error.Invalid code')]],
        ], 400);
    }

    public function twoFactorAuthentication(): View|Factory|Application
    {
        $title = __('main.Two Factor Authentication');

        return view_client('user.two_factor_authentication', compact('title'));
    }

    public function postTwoFactorAuthenticationEnable(Request $request): JsonResponse
    {
        $user = app('user');
        $code = $request->get('code');
        $verification = $user->verifications()->where('default', true)->first();
        if (empty($verification)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $verify = $verification->confirmationVerifyCode($code);

        if (! $verify) {
            return response()->json([
                'errors' => [__('error.Verification failed')],
                'message' => ['code' => [__('error.Invalid code')]],
            ], 422);
        }

        $user->two_factor = true;
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
        ]);
    }

    public function postTwoFactorAuthenticationDisable(Request $request): JsonResponse
    {
        $user = app('user');
        $code = $request->get('code');
        $verification = $user->verifications()->where('default', true)->first();
        if (empty($verification)) {
            return response()->json([
                'errors' => [__('error.Not found')],
            ], 404);
        }

        $verify = $verification->confirmationVerifyCode($code);

        if (! $verify) {
            return response()->json([
                'errors' => [__('error.Verification failed')],
                'message' => ['code' => [__('error.Invalid code')]],
            ], 422);
        }

        $user->two_factor = false;
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => __('message.Updated successfully'),
        ]);
    }
}
