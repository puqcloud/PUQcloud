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

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\ClientAddress;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Invoice;
use App\Models\PaymentGateway;
use App\Models\Region;
use App\Models\User;
use App\Services\TranslationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PUQCloudCreateClient extends Command
{
    // Command signature with all parameters
    protected $signature = 'puqcloud:create-client
                            {--firstname= : Client first name (required)}
                            {--lastname= : Client last name (optional)}
                            {--email= : User email address (required, unique)}
                            {--password= : User password (required)}
                            {--company= : Company name (optional, unique)}
                            {--tax-id= : Tax ID (optional, unique)}
                            {--language=en : Language code (en, ru, de, fr, es)}
                            {--status=active : Client status (new, active, inactive, closed, fraud)}
                            {--address1= : Address line 1 (required)}
                            {--address2= : Address line 2 (optional)}
                            {--city= : City (required)}
                            {--postcode= : Postal code (required)}
                            {--country= : Country code or UUID (required)}
                            {--region= : Region code or UUID (required)}
                            {--phone= : Phone number (optional)}
                            {--notes= : Client notes (optional)}
                            {--admin-notes= : Admin notes (optional)}
                            {--credit-limit=0 : Credit limit (default 0)}
                            {--extrapay= : Auto-add funds amount after client creation (optional)}';

    // Description of the command
    protected $description = 'Create a new client with user account and billing address using specific parameters';

    public function __construct()
    {
        parent::__construct();
        App::setLocale(config('locale.admin.default'));
        TranslationService::init('admin');
    }


    public function handle(): int
    {
        $this->info('=== PUQCloud Client Creator ===');
        $this->info('');

        // Get all options
        $options = $this->options();

        // Show help if no required parameters provided
        if (!$this->hasRequiredParameters($options)) {
            $this->showHelp();
            return 1;
        }

        // Validate parameters
        $validation = $this->validateParameters($options);
        if (!$validation['valid']) {
            $this->error('Validation failed:');
            foreach ($validation['errors'] as $error) {
                $this->error("• {$error}");
            }
            return 1;
        }

        // Create client
        try {
            $result = $this->createClient($validation['data']);

            if ($result['success']) {
                $this->info('✅ Client created successfully!');
                $this->info('');
                $this->info('Client Details:');
                $this->info("• Client UUID: {$result['client']->uuid}");
                $this->info("• User UUID: {$result['user']->uuid}");
                $this->info("• Name: {$result['client']->firstname} {$result['client']->lastname}");
                $this->info("• Email: {$result['user']->email}");
                if ($result['client']->company_name) {
                    $this->info("• Company: {$result['client']->company_name}");
                }
                $this->info("• Status: {$result['client']->status}");
                $this->info("• Language: {$result['client']->language}");
                $this->info("• Address: {$result['address']->address_1}, {$result['address']->city}");

                // Show payment information if extrapay was processed
                if ($result['proforma']) {
                    $this->info('');
                    $this->info('💰 Payment Processing:');
                    $this->info("• Proforma Invoice: {$result['proforma']->number} ({$result['proforma']->uuid})");
                    $this->info("• Amount: {$result['proforma']->total} {$result['proforma']->currency_code}");
                    $this->info("• Status: {$result['proforma']->status}");

                    if ($result['payment_result']) {
                        if ($result['payment_result']['status'] === 'success') {
                            $this->info('• ✅ Payment processed successfully!');
                            $this->info('• Client balance has been updated');

                            // Reload proforma to check if it was converted to invoice
                            $result['proforma']->refresh();
                            if ($result['proforma']->status === 'invoiced' && $result['proforma']->invoice_uuid) {
                                $this->info("• Regular invoice created: {$result['proforma']->invoice_uuid}");
                            }
                        } else {
                            $this->error('• ❌ Payment processing failed');
                            if (!empty($result['payment_result']['errors'])) {
                                foreach ($result['payment_result']['errors'] as $error) {
                                    $this->error("  - {$error}");
                                }
                            }
                        }
                    } else {
                        $this->error('• ⚠️ Payment gateway not found - manual payment required');
                    }
                }

                $this->info('');
                return 0;
            } else {
                $this->error('❌ Failed to create client:');
                foreach ($result['errors'] as $error) {
                    $this->error("• {$error}");
                }
                return 1;
            }
        } catch (\Exception $e) {
            $this->error("❌ Error creating client: " . $e->getMessage());
            Log::error('Client creation error: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Check if required parameters are provided
     */
    private function hasRequiredParameters(array $options): bool
    {
        $required = ['firstname', 'email', 'password', 'address1', 'city', 'postcode', 'country', 'region'];

        foreach ($required as $param) {
            if (empty($options[$param])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Show help information
     */
    private function showHelp(): void
    {
        $this->error('Missing required parameters!');
        $this->info('');
        $this->info('<fg=yellow>📋 Usage Examples:</fg=yellow>');
        $this->info('');
        $this->info('<fg=white>Basic individual client:</fg=white>');
        $this->info('  <fg=green>php artisan puqcloud:create-client</fg=green>');
        $this->info('    <fg=green>--firstname="John"</fg=green>');
        $this->info('    <fg=green>--lastname="Doe"</fg=green>');
        $this->info('    <fg=green>--email="demo-client@puqcloud.com"</fg=green>');
        $this->info('    <fg=green>--password="Demo@1234"</fg=green>');
        $this->info('    <fg=green>--address1="123 Main St"</fg=green>');
        $this->info('    <fg=green>--city="New York"</fg=green>');
        $this->info('    <fg=green>--postcode="10001"</fg=green>');
        $this->info('    <fg=green>--country="US"</fg=green>');
        $this->info('    <fg=green>--region="NY"</fg=green>');
        $this->info('');
        $this->info('<fg=white>Client with automatic balance top-up:</fg=white>');
        $this->info('  <fg=green>php artisan puqcloud:create-client</fg=green>');
        $this->info('    <fg=green>--firstname="Mike"</fg=green>');
        $this->info('    <fg=green>--email="demo-client@puqcloud.com"</fg=green>');
        $this->info('    <fg=green>--password="Demo@1234"</fg=green>');
        $this->info('    <fg=green>--address1="789 Test Ave"</fg=green>');
        $this->info('    <fg=green>--city="Chicago"</fg=green>');
        $this->info('    <fg=green>--postcode="60601"</fg=green>');
        $this->info('    <fg=green>--country="US"</fg=green>');
        $this->info('    <fg=green>--region="IL"</fg=green>');
        $this->info('    <fg=green>--extrapay="25000"</fg=green>');
        $this->info('');
        $this->info('<fg=white>Company client with all options:</fg=white>');
        $this->info('  <fg=green>php artisan puqcloud:create-client </fg=green>');
        $this->info('    <fg=green>--firstname="Jane"</fg=green>');
        $this->info('    <fg=green>--lastname="Smith"</fg=green>');
        $this->info('    <fg=green>--email="demo-client@puqcloud.com"</fg=green>');
        $this->info('    <fg=green>--password="Demo@1234"</fg=green>');
        $this->info('    <fg=green>--company="Tech Solutions LLC"</fg=green>');
        $this->info('    <fg=green>--tax-id="US123456789"</fg=green>');
        $this->info('    <fg=green>--language="en"</fg=green>');
        $this->info('    <fg=green>--status="active"</fg=green>');
        $this->info('    <fg=green>--address1="456 Business Ave"</fg=green>');
        $this->info('    <fg=green>--address2="Suite 200"</fg=green>');
        $this->info('    <fg=green>--city="Los Angeles"</fg=green>');
        $this->info('    <fg=green>--postcode="90210"</fg=green>');
        $this->info('    <fg=green>--country="US"</fg=green>');
        $this->info('    <fg=green>--region="CA"</fg=green>');
        $this->info('    <fg=green>--phone="+1234567890"</fg=green>');
        $this->info('    <fg=green>--credit-limit="5000"</fg=green>');
        $this->info('    <fg=green>--notes="Demo client"</fg=green>');
        $this->info('    <fg=green>--extrapay="10000"</fg=green>');
        $this->info('');
        $this->info('<fg=yellow>📝 Required Parameters:</fg=yellow>');
        $this->info('• --firstname    : Client first name');
        $this->info('• --email        : User email address (must be unique)');
        $this->info('• --password     : User password');
        $this->info('• --address1     : Primary address line');
        $this->info('• --city         : City name');
        $this->info('• --postcode     : Postal/ZIP code');
        $this->info('• --country      : Country code (US, GB, DE) or UUID');
        $this->info('• --region       : Region/State code (NY, CA, etc.) or UUID');
        $this->info('');
        $this->info('<fg=yellow>⚙️ Optional Parameters:</fg=yellow>');
        $this->info('• --lastname     : Client last name');
        $this->info('• --company      : Company name (must be unique if provided)');
        $this->info('• --tax-id       : Tax identification number (must be unique)');
        $this->info('• --language     : Language code (en, ru, de, fr, es) - default: en');
        $this->info('• --status       : Client status (new, active, inactive, closed, fraud) - default: active');
        $this->info('• --address2     : Secondary address line');
        $this->info('• --phone        : Phone number');
        $this->info('• --notes        : Client notes');
        $this->info('• --admin-notes  : Admin-only notes');
        $this->info('• --credit-limit : Credit limit amount - default: 0');
        $this->info('• --extrapay     : Auto-add funds amount after client creation');
        $this->info('');
        $this->info('<fg=yellow>💡 Tips:</fg=yellow>');
        $this->info('• Email must be unique across all users');
        $this->info('• Company name must be unique if provided');
        $this->info('• Tax ID must be unique if provided');
        $this->info('• Country and region must exist in the system');
        $this->info('• Client balance will be automatically created with 0 balance');
        $this->info('• User will be automatically set as client owner');
        $this->info('• Default currency will be assigned automatically');
        $this->info('• Use --extrapay to automatically add funds via proforma invoice and bank transfer payment');
    }

    /**
     * Validate all parameters
     */
    private function validateParameters(array $options): array
    {
        $data = [
            'firstname' => trim($options['firstname']),
            'lastname' => trim($options['lastname'] ?? ''),
            'email' => trim($options['email']),
            'password' => $options['password'],
            'company_name' => trim($options['company'] ?? ''),
            'tax_id' => trim($options['tax-id'] ?? ''),
            'language' => trim($options['language']),
            'status' => trim($options['status']),
            'address1' => trim($options['address1']),
            'address2' => trim($options['address2'] ?? ''),
            'city' => trim($options['city']),
            'postcode' => trim($options['postcode']),
            'country' => trim($options['country']),
            'region' => trim($options['region']),
            'phone' => trim($options['phone'] ?? ''),
            'notes' => trim($options['notes'] ?? ''),
            'admin_notes' => trim($options['admin-notes'] ?? ''),
            'credit_limit' => floatval($options['credit-limit']),
            'extrapay' => floatval($options['extrapay'] ?? 0),
        ];

        $errors = [];

        // Validate required fields
        if (empty($data['firstname'])) {
            $errors[] = 'First name is required';
        }

        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Valid email address is required';
        }

        if (empty($data['password']) || strlen($data['password']) < 6) {
            $errors[] = 'Password must be at least 6 characters';
        }

        if (empty($data['address1'])) {
            $errors[] = 'Address line 1 is required';
        }

        if (empty($data['city'])) {
            $errors[] = 'City is required';
        }

        if (empty($data['postcode'])) {
            $errors[] = 'Postal code is required';
        }

        // Validate language
        $allowedLanguages = ['en', 'ru', 'de', 'fr', 'es'];
        if (!in_array($data['language'], $allowedLanguages)) {
            $errors[] = 'Language must be one of: ' . implode(', ', $allowedLanguages);
        }

        // Validate status
        $allowedStatuses = ['new', 'active', 'inactive', 'closed', 'fraud'];
        if (!in_array($data['status'], $allowedStatuses)) {
            $errors[] = 'Status must be one of: ' . implode(', ', $allowedStatuses);
        }

        // Validate uniqueness
        if (User::where('email', $data['email'])->exists()) {
            $errors[] = 'Email address already exists';
        }

        if (!empty($data['company_name']) && Client::where('company_name', $data['company_name'])->exists()) {
            $errors[] = 'Company name already exists';
        }

        if (!empty($data['tax_id']) && Client::where('tax_id', $data['tax_id'])->exists()) {
            $errors[] = 'Tax ID already exists';
        }

        // Validate country and region
        $country = $this->findCountry($data['country']);
        if (!$country) {
            $errors[] = 'Country not found. Use country code (US, GB, DE) or UUID';
        } else {
            $data['country_uuid'] = $country->uuid;

            $region = $this->findRegion($data['region'], $country);
            if (!$region) {
                $errors[] = 'Region not found for the specified country. Use region code or UUID';
            } else {
                $data['region_uuid'] = $region->uuid;
            }
        }

        // Validate credit limit
        if ($data['credit_limit'] < 0) {
            $errors[] = 'Credit limit cannot be negative';
        }

        // Validate extrapay
        if ($data['extrapay'] < 0) {
            $errors[] = 'Extra payment amount cannot be negative';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'data' => $data
        ];
    }

    /**
     * Find country by code or UUID
     */
    private function findCountry(string $identifier): ?Country
    {
        // Try UUID first
        if (strlen($identifier) === 36) {
            return Country::find($identifier);
        }

        // Try by code
        return Country::where('code', strtoupper($identifier))->first();
    }

    /**
     * Find region by code or UUID within country
     */
    private function findRegion(string $identifier, Country $country): ?Region
    {
        // Try UUID first
        if (strlen($identifier) === 36) {
            return Region::where('uuid', $identifier)
                         ->where('country_uuid', $country->uuid)
                         ->first();
        }

        // Try by code within the country
        return Region::where('code', strtoupper($identifier))
                     ->where('country_uuid', $country->uuid)
                     ->first();
    }

    /**
     * Create client with all associated data
     */
    private function createClient(array $data): array
    {
        return DB::transaction(function () use ($data) {
            try {
                // Get default currency
                $currency = Currency::getDefaultCurrency();
                if (!$currency) {
                    throw new \Exception('No default currency found');
                }

                // Create user first
                $user = User::create([
                    'email' => $data['email'],
                    'password' => Hash::make($data['password']),
                    'firstname' => $data['firstname'],
                    'lastname' => $data['lastname'] ?: null,
                    'language' => $data['language'],
                    'disable' => false,
                ]);

                // Create client
                $client = new Client([
                    'firstname' => $data['firstname'],
                    'lastname' => $data['lastname'] ?: null,
                    'company_name' => $data['company_name'] ?: null,
                    'tax_id' => $data['tax_id'] ?: null,
                    'status' => $data['status'],
                    'language' => $data['language'],
                    'currency_uuid' => $currency->uuid,
                    'credit_limit' => $data['credit_limit'],
                    'notes' => $data['notes'] ?: null,
                    'admin_notes' => $data['admin_notes'] ?: null,
                ]);
                $client->save();
                $client->refresh(); // Ensure UUID is loaded

                // Associate user with client as owner
                $user->clients()->attach($client->uuid, [
                    'owner' => true,
                    'permissions' => json_encode([]), // Empty permissions for now
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Create billing address
                $address = ClientAddress::create([
                    'name' => 'Billing Address',
                    'client_uuid' => $client->uuid,
                    'type' => 'billing',
                    'contact_name' => $data['firstname'] . ' ' . ($data['lastname'] ?: ''),
                    'contact_phone' => $data['phone'] ?: null,
                    'contact_email' => $data['email'],
                    'address_1' => $data['address1'],
                    'address_2' => $data['address2'] ?: null,
                    'city' => $data['city'],
                    'postcode' => $data['postcode'],
                    'region_uuid' => $data['region_uuid'],
                    'country_uuid' => $data['country_uuid'],
                    'notes' => null,
                ]);

                // Handle auto payment if extrapay is specified
                $proforma = null;
                $payment_result = null;
                if ($data['extrapay'] > 0) {
                    try {
                        // Create proforma invoice for add funds
                        $proformaUuid = $client->createInvoiceProformaAddFunds($data['extrapay']);
                        $proforma = Invoice::find($proformaUuid);

                        if ($proforma) {
                            // Find bank transfer payment gateway
                            $homeCompany = $proforma->homeCompany;
                            $bankTransferGateway = $homeCompany->paymentGateways()
                                ->whereHas('module', function ($query) {
                                    $query->where('name', 'puqBankTransfer');
                                })
                                ->first();

                            if (!$bankTransferGateway) {
                                // Try to find by key as fallback
                                $bankTransferGateway = $homeCompany->paymentGateways()
                                    ->where('key', 'like', '%Bank Transfer%')
                                    ->first();
                            }

                            if ($bankTransferGateway) {
                                // Generate transaction ID
                                $transactionId = 'CLIENT-CREATION-' . strtoupper(uniqid()) . '-' . rand(1000, 9999);
                                $description = 'Automatic payment during client creation';

                                // Add payment using the payment gateway
                                $payment_result = $proforma->addPaymentByPaymentGateway(
                                    (float) $proforma->total,
                                    0.00,
                                    $transactionId,
                                    $description,
                                    $bankTransferGateway->uuid
                                );
                            }
                        }
                    } catch (\Exception $e) {
                        // Log the error but don't fail client creation
                        Log::error('Auto payment failed during client creation: ' . $e->getMessage());
                    }
                }

                return [
                    'success' => true,
                    'client' => $client,
                    'user' => $user,
                    'address' => $address,
                    'proforma' => $proforma,
                    'payment_result' => $payment_result,
                    'errors' => []
                ];

            } catch (\Exception $e) {
                throw $e;
            }
        });
    }
}
