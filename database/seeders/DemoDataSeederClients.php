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

namespace Database\Seeders;

use App\Models\Client;
use App\Models\ClientAddress;
use App\Models\Currency;
use App\Models\User;
use Database\Factories\ClientFactory;
use Database\Factories\UserFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoDataSeederClients extends Seeder
{
    public function run(int $count = 1): void
    {
        // Reset used values to ensure uniqueness in this run
        ClientFactory::resetUsedValues();

        // Output message about the start of the process
        $this->output("Starting creation of {$count} clients...");

        // Clear existing demo client data if needed
        $this->clearExistingData();

        // Check if we have required data
        if (! $this->checkRequiredData()) {
            $this->output('Required data missing. Please run seeders for Countries, Currencies first.');

            return;
        }

        // Define chunk size for processing large datasets
        $chunkSize = 100;
        $totalChunks = ceil($count / $chunkSize);

        // Create clients in chunks
        for ($i = 0; $i < $totalChunks; $i++) {
            $currentCount = min($chunkSize, $count - ($i * $chunkSize));

            $this->output('Processing chunk '.($i + 1)." of {$totalChunks} ({$currentCount} clients)");

            $this->createClientsChunk($currentCount, $i);
        }

        $this->output("Successfully created {$count} clients with unique data!");

        // Output some statistics
        $usedValues = ClientFactory::getUsedValues();
        $this->output('Created unique emails: '.count($usedValues['emails']));
        $this->output('Created unique company names: '.count($usedValues['company_names']));
        $this->output('Created unique tax IDs: '.count($usedValues['tax_ids']));
    }

    /**
     * Create a chunk of clients
     */
    private function createClientsChunk(int $count, int $chunkIndex): void
    {
        $clientFactory = new ClientFactory;
        $faker = \Faker\Factory::create();

        for ($i = 0; $i < $count; $i++) {
            DB::transaction(function () use ($clientFactory, $faker, $i) {
                // Create base client data using factory
                $clientData = $clientFactory->definition();

                // Determine if this should be a company or individual (70% companies)
                $isCompany = $faker->boolean(70);

                if ($isCompany) {
                    $clientData['company_name'] = $clientFactory->generateUniqueCompanyName();
                    $clientData['tax_id'] = $clientFactory->generateUniqueTaxId();
                }

                // Create the client first (without user association)
                $client = Client::create($clientData);
                $client->refresh();

                // Generate unique email for user
                $userEmail = $clientFactory->generateUniqueEmail($clientData['firstname'], $clientData['lastname']);

                // Create user data
                $userFactory = new UserFactory;
                $userData = $userFactory->definition();
                $userData['email'] = $userEmail;
                $userData['firstname'] = $clientData['firstname'];
                $userData['lastname'] = $clientData['lastname'];
                $userData['language'] = $clientData['language'];

                // Create user
                $user = User::create($userData);
                $user->refresh();

                // Associate user with client as owner
                $user->clients()->attach($client->uuid, [
                    'owner' => true,
                    'permissions' => json_encode([]), // Empty permissions for now
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Create billing address
                $addressData = $clientFactory->generateBillingAddressData();
                $addressData['client_uuid'] = $client->uuid;
                $addressData['contact_email'] = $userEmail;
                $addressData['contact_name'] = $clientData['firstname'].' '.$clientData['lastname'];

                ClientAddress::create($addressData);

                // Output progress every 10 clients
                if (($i + 1) % 10 === 0) {
                    $this->output('  Created '.($i + 1).' clients in current chunk');
                }
            });
        }
    }

    /**
     * Clear existing demo data
     */
    private function clearExistingData(): void
    {
        // Only clear if we're in a demo environment
        if (app()->environment(['local', 'testing'])) {
            $this->output('Clearing existing demo client data...');

            // Delete client addresses first (foreign key constraint)
            DB::table('client_addresses')->where('name', 'Billing Address')->delete();

            // Delete client-user relationships for demo clients
            $demoClientIds = DB::table('clients')
                ->where('notes', 'LIKE', '%Generated by DemoDataSeederClients%')
                ->pluck('uuid');

            if ($demoClientIds->isNotEmpty()) {
                DB::table('client_x_user')->whereIn('client_uuid', $demoClientIds)->delete();

                // Get associated user IDs before deleting clients
                $demoUserIds = DB::table('client_x_user')
                    ->whereIn('client_uuid', $demoClientIds)
                    ->pluck('user_uuid');

                // Delete demo clients
                DB::table('clients')->whereIn('uuid', $demoClientIds)->delete();

                // Delete demo users (only if they have no other clients)
                if ($demoUserIds->isNotEmpty()) {
                    $usersWithoutClients = DB::table('users')
                        ->whereIn('uuid', $demoUserIds)
                        ->whereNotIn('uuid', function ($query) {
                            $query->select('user_uuid')->from('client_x_user');
                        })
                        ->pluck('uuid');

                    if ($usersWithoutClients->isNotEmpty()) {
                        DB::table('users')->whereIn('uuid', $usersWithoutClients)->delete();
                    }
                }
            }
        }
    }

    /**
     * Check if required data exists
     */
    private function checkRequiredData(): bool
    {
        // Check if we have currencies
        if (! Currency::exists()) {
            return false;
        }

        // Check if we have default currency
        if (! Currency::where('default', true)->exists()) {
            return false;
        }

        // Check if we have countries and regions
        $countryCount = DB::table('countries')->count();
        $regionCount = DB::table('regions')->count();

        if ($countryCount === 0 || $regionCount === 0) {
            return false;
        }

        return true;
    }

    /**
     * Output message (compatible with both console and web execution)
     */
    private function output(string $message): void
    {
        if (app()->runningInConsole()) {
            echo $message."\n";
        } else {
            // If running from web, you could log or handle differently
            logger($message);
        }
    }
}
