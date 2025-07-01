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

use App\Models\Currency;
use App\Models\Price;
use Illuminate\Database\Eloquent\Factories\Factory;

class PriceFactory extends Factory
{
    /**
     * Model corresponding to the factory.
     *
     * @var string
     */
    protected $model = Price::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Get the default currency or create it
        $currency = Currency::getDefaultCurrency();

        // Define the price period
        $period = $this->faker->randomElement([
            'one-time', 'hourly', 'daily', 'weekly', 'bi-weekly',
            'monthly', 'quarterly', 'semi-annually', 'annually',
            'biennially', 'triennially',
        ]);

        // Define the price range based on the period
        $priceRange = match ($period) {
            'hourly' => [1, 5],
            'daily' => [5, 20],
            'weekly' => [20, 50],
            'monthly' => [30, 150],
            'quarterly' => [80, 400],
            'semi-annually' => [150, 700],
            'annually' => [250, 1200],
            'biennially' => [450, 2000],
            'triennially' => [650, 2800],
            'one-time' => [100, 300],
            'bi-weekly' => [15, 40],
            default => [10, 100],
        };

        $basePrice = $this->faker->numberBetween($priceRange[0], $priceRange[1]);

        return [
            'currency_uuid' => $currency->uuid,
            'period' => $period,
            'setup' => $this->generateSetupPrice($basePrice, $period),
            'base' => $basePrice,
            'idle' => $this->generateIdlePrice($basePrice),
            'switch_down' => $this->generateSwitchPrice($basePrice, 'down'),
            'switch_up' => $this->generateSwitchPrice($basePrice, 'up'),
            'uninstall' => $this->generateUninstallPrice($basePrice),
        ];
    }

    /**
     * Specify the specific price period
     */
    public function period(string $period): self
    {
        // Normalize the period to the format supported by the database
        $validPeriods = [
            'one-time', 'hourly', 'daily', 'weekly', 'bi-weekly',
            'monthly', 'quarterly', 'semi-annually', 'annually',
            'biennially', 'triennially',
        ];

        // Replace underscores with hyphens
        $normalized = str_replace('_', '-', $period);

        // If the period is not valid, use monthly as default
        if (! in_array($normalized, $validPeriods)) {
            $normalized = 'monthly';
        }

        return $this->state(function (array $attributes) use ($normalized) {
            // Define the price range based on the period
            $priceRange = match ($normalized) {
                'hourly' => [1, 5],
                'daily' => [5, 20],
                'weekly' => [20, 50],
                'monthly' => [30, 150],
                'quarterly' => [80, 400],
                'semi-annually' => [150, 700],
                'annually' => [250, 1200],
                'biennially' => [450, 2000],
                'triennially' => [650, 2800],
                'one-time' => [100, 300],
                'bi-weekly' => [15, 40],
                default => [10, 100],
            };

            $basePrice = $this->faker->numberBetween($priceRange[0], $priceRange[1]);

            return [
                'period' => $normalized,
                'setup' => $this->generateSetupPrice($basePrice, $normalized),
                'base' => $basePrice,
                'idle' => $this->generateIdlePrice($basePrice),
                'switch_down' => $this->generateSwitchPrice($basePrice, 'down'),
                'switch_up' => $this->generateSwitchPrice($basePrice, 'up'),
                'uninstall' => $this->generateUninstallPrice($basePrice),
            ];
        });
    }

    /**
     * Generate setup price based on base price
     */
    private function generateSetupPrice(float $basePrice, string $period): ?float
    {
        // 30% chance to have no setup fee
        if ($this->faker->boolean(30)) {
            return null;
        }

        // Setup price is typically 0.5x to 2x the base price
        $multiplier = $this->faker->randomFloat(2, 0.5, 2.0);

        // For longer periods, setup fees are usually lower relative to base price
        if (in_array($period, ['annually', 'biennially', 'triennially'])) {
            $multiplier *= 0.5;
        }

        return round($basePrice * $multiplier, 2);
    }

    /**
     * Generate idle price (usually lower than base price)
     */
    private function generateIdlePrice(float $basePrice): ?float
    {
        // 40% chance to have no idle pricing
        if ($this->faker->boolean(40)) {
            return null;
        }

        // Idle price is typically 10% to 50% of base price
        $multiplier = $this->faker->randomFloat(2, 0.1, 0.5);

        return round($basePrice * $multiplier, 2);
    }

    /**
     * Generate switch pricing (upgrade/downgrade fees)
     */
    private function generateSwitchPrice(float $basePrice, string $direction): ?float
    {
        // 50% chance to have no switch fees
        if ($this->faker->boolean(50)) {
            return null;
        }

        if ($direction === 'down') {
            // Downgrade fees are usually smaller (0.1x to 0.5x base price)
            $multiplier = $this->faker->randomFloat(2, 0.1, 0.5);
        } else {
            // Upgrade fees can be higher (0.2x to 1x base price)
            $multiplier = $this->faker->randomFloat(2, 0.2, 1.0);
        }

        return round($basePrice * $multiplier, 2);
    }

    /**
     * Generate uninstall/termination price
     */
    private function generateUninstallPrice(float $basePrice): ?float
    {
        // 70% chance to have no uninstall fee
        if ($this->faker->boolean(70)) {
            return null;
        }

        // Uninstall fees are typically low (0.1x to 0.3x base price)
        $multiplier = $this->faker->randomFloat(2, 0.1, 0.3);

        return round($basePrice * $multiplier, 2);
    }
}
