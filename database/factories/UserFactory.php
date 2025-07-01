<?php

/*
 * PUQcloud - Free Cloud Billing System
 * Main billing system core logic
 *
 * Copyright (C) 2025 PUQ sp. z o.o.
 * Licensed under GNU GPLv3
 * https://www.gnu.org/licenses/gpl-3.0.html
 *
 * Author: Dmytro Kravchenko <dmytro.kravchenko@ihostmi.com>
 * Website: https://puqcloud.com
 * E-mail: support@puqcloud.com
 *
 * Do not remove this header.
 */

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    /**
     * Model corresponding to factory.
     *
     * @var string
     */
    protected $model = User::class;

    /**
     * Define the default state of the model.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => Str::uuid(),
            'firstname' => $this->faker->firstName(),
            'lastname' => $this->faker->lastName(),
            'password' => Hash::make('password123'), // Default password
            'status' => $this->faker->randomElement(['new', 'active', 'inactive']),
            'two_factor' => false,
            'disable' => false,
            'email_verified' => $this->faker->boolean(80), // 80% verified
            'language' => $this->faker->randomElement(['en', 'ru', 'de', 'fr', 'es']),
            'notes' => $this->faker->optional(0.3)->paragraph(),
            'admin_notes' => $this->faker->optional(0.2)->sentence(),
            'phone_number' => $this->faker->optional(0.7)->phoneNumber(),
        ];
    }

    /**
     * Set specific status
     */
    public function status(string $status): self
    {
        return $this->state(function (array $attributes) use ($status) {
            return [
                'status' => $status,
            ];
        });
    }

    /**
     * Set specific language
     */
    public function language(string $language): self
    {
        return $this->state(function (array $attributes) use ($language) {
            return [
                'language' => $language,
            ];
        });
    }

    /**
     * Create verified user
     */
    public function verified(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'email_verified' => true,
            ];
        });
    }

    /**
     * Create unverified user
     */
    public function unverified(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'email_verified' => false,
            ];
        });
    }

    /**
     * Create disabled user
     */
    public function disabled(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'disable' => true,
                'status' => 'inactive',
            ];
        });
    }
}
