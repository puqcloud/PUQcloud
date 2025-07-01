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

namespace App\Models;

use App\Services\NotificationService;
use App\Services\SettingService;
use App\Traits\ConvertsTimezone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use RobThree\Auth\Providers\Qr\EndroidQrCodeProvider;
use RobThree\Auth\TwoFactorAuth;
use RobThree\Auth\TwoFactorAuthException;

class Verification extends Model
{
    use ConvertsTimezone;

    protected $table = 'verifications';

    protected $primaryKey = 'uuid';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });

        static::saved(function ($model) {});

    }

    protected $fillable = [
        'user_uuid',
        'type',
        'value',
        'secret',
        'device_name',
        'expires_at',
        'last_used_at',
        'verified',
        'default',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    protected $dates = [
        'expires_at',
        'last_used_at',
        'created_at',
        'updated_at',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_uuid', 'uuid');
    }

    public function verifySendVerificationCode(): array
    {
        if ($this->verified) {
            return [];
        }

        return $this->sendVerificationCode();
    }

    public function verifyVerifyCode($code): bool
    {
        if ($this->verified) {
            return false;
        }

        if ($this->verifyCode($code)) {
            $this->verified = true;
            $this->save();

            return true;
        }

        return false;
    }

    public function confirmationSendVerificationCode(): array
    {
        if (! $this->verified) {
            return [];
        }

        return $this->sendVerificationCode();
    }

    public function confirmationVerifyCode($code): bool
    {
        if (! $this->verified) {
            return false;
        }

        return $this->verifyCode($code);
    }

    protected function sendVerificationCode(): array
    {
        if ($this->type == 'email') {
            $user_verification_code_lifetime = SettingService::get('time_based.user_verification_code_lifetime');
            $this->secret = rand(100000, 999999);
            $this->expires_at = now()->addMinutes((int) $user_verification_code_lifetime);
            $this->save();

            $user = $this->user;
            $home_company = HomeCompany::query()->where('default', true)->first();
            if (! $home_company) {
                return [];
            }
            $group = $home_company->group;
            $notification_rule = $group->notificationRules()->where('notification', 'Client One-time passcode')->where('category', 'clientAdministrative')->first();

            $notification_service = new NotificationService;
            $data = [
                'user' => $user,
                'code' => $this->secret,
            ];
            $notification_service->toUserOneTimeCode($user, $this->value, null, $notification_rule, $data);

            return [
                'lifetime' => $user_verification_code_lifetime * 60,
                'value' => $this->value,
                'type' => $this->type,
                'uuid' => $this->uuid,
            ];
        }

        if ($this->type == 'totp') {
            return [
                'lifetime' => 0,
                'value' => $this->value,
                'type' => $this->type,
                'uuid' => $this->uuid,
            ];
        }

        return [];
    }

    /**
     * @throws TwoFactorAuthException
     */
    protected function verifyCode($code): bool
    {
        $code = (string) $code;
        if ($this->type == 'email') {
            if ($this->secret == $code && $this->expires_at && $this->expires_at->isFuture()) {
                $this->secret = rand(100000, 999999);
                $this->expires_at = null;
                $this->last_used_at = now();
                $this->save();

                return true;
            }
            $this->secret = rand(100000, 999999);
            $this->expires_at = null;
            $this->save();

            return false;
        }

        if ($this->type == 'totp') {
            $tfa = new TwoFactorAuth(new EndroidQrCodeProvider, $host ?? 'PUQcloud');
            $isValid = $tfa->verifyCode((string) $this->secret, $code);
            if ($isValid) {
                $this->last_used_at = now();
                $this->save();

                return true;
            } else {
                return false;
            }
        }

        return false;
    }

    public function makeDefault(): void
    {
        $this->user->verifications()->update(['default' => false]);
        $this->default = true;
        $this->verified = true;
        $this->save();
    }

    public static function handleVerification(Request $request, ?string $caller = null): \Illuminate\Http\JsonResponse
    {
        $skipVerificationFor = [];

        if (in_array($caller, $skipVerificationFor)) {
            return response()->json(['status' => 'skipped'], 200);
        }

        $validator = Validator::make($request->all(), [
            'code' => 'required|digits:6',
        ], [
            'code.required' => __('error.Verify Key is required'),
            'code.digits' => __('error.Verify Key must be 6 digits'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'validation_error',
                'message' => $validator->errors(),
            ], 422);
        }

        $user = app('user');

        $verification = $user->verifications()->where('default', true)->first();
        if (! $verification) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('error.Verification method not set')],
            ], 404);
        }

        if (! $request->has('code')) {
            return response()->json([
                'status' => 'code_required',
                'errors' => [__('error.Verification code is missing')],
                'message' => ['code' => [__('error.Invalid code')]],
            ], 422);
        }

        if (! $verification->confirmationVerifyCode($request->get('code'))) {
            return response()->json([
                'status' => 'error',
                'errors' => [__('error.Verification failed')],
                'message' => ['code' => [__('error.Invalid code')]],
            ], 422);
        }

        return response()->json(['status' => 'success'], 200);
    }
}
