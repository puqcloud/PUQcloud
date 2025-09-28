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

use App\Models\Client;
use App\Models\Country;
use App\Models\Currency;
use App\Models\HomeCompany;
use App\Models\Region;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ClientFactory extends Factory
{
    /**
     * Model corresponding to factory.
     *
     * @var string
     */
    protected $model = Client::class;

    /**
     * Static arrays to track used values and ensure uniqueness
     */
    private static array $usedEmails = [];

    private static array $usedCompanyNames = [];

    private static array $usedTaxIds = [];

    /**
     * Define the default state of the model.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Get default currency
        $currency = Currency::getDefaultCurrency();

        return [
            'uuid' => Str::uuid(),
            'firstname' => $this->faker->firstName(),
            'lastname' => $this->faker->lastName(),
            'status' => $this->faker->randomElement(['new', 'active', 'inactive']),
            'language' => $this->faker->randomElement(['en', 'ru', 'de', 'fr', 'es']),
            'currency_uuid' => $currency->uuid,
            'credit_limit' => $this->faker->randomFloat(2, 0, 10000),
            'notes' => $this->faker->optional(0.3)->paragraph(),
            'admin_notes' => $this->faker->optional(0.2)->sentence(),
        ];
    }

    /**
     * Generate unique company name
     */
    public function generateUniqueCompanyName(): ?string
    {
        $maxAttempts = 50; // Reduced max attempts before adding suffix
        $attempts = 0;

        // 70% chance to have a company name
        if ($this->faker->boolean(70)) {
            do {
                // Add unique suffix after first few attempts to avoid endless loops
                if ($attempts < 10) {
                    $name = $this->generateRealisticCompanyName();
                } elseif ($attempts < 30) {
                    $name = $this->generateRealisticCompanyName().' '.$this->faker->numberBetween(1, 999);
                } else {
                    // Use timestamp + random number for guaranteed uniqueness
                    $name = $this->generateRealisticCompanyName().' '.substr(microtime(true) * 1000, -6).$this->faker->numberBetween(10, 99);
                }

                $attempts++;

                if ($attempts >= $maxAttempts) {
                    // Final fallback with guaranteed unique identifier
                    $name = $this->generateRealisticCompanyName().'#'.uniqid();
                    break;
                }
            } while (in_array($name, self::$usedCompanyNames) || $this->companyNameExistsInDatabase($name));

            self::$usedCompanyNames[] = $name;

            return $name;
        }

        return null;
    }

    /**
     * Check if company name exists in database
     */
    private function companyNameExistsInDatabase(string $name): bool
    {
        return \App\Models\Client::where('company_name', $name)->exists();
    }

    /**
     * Generate realistic company names based on actual business patterns
     */
    private function generateRealisticCompanyName(): string
    {
        // Real business name components
        $businessTypes = [
            'Technologies', 'Systems', 'Solutions', 'Services', 'Consulting',
            'Digital', 'Innovation', 'Software', 'Cloud', 'Data',
            'Security', 'Networks', 'Communications', 'Development', 'Media',
        ];

        $businessSuffixes = [
            'LLC', 'Inc', 'Corp', 'Ltd', 'Co', 'Group', 'Partners',
            'Associates', 'Holdings', 'Enterprises', 'Industries',
        ];

        $prefixes = [
            'Global', 'International', 'Advanced', 'Smart', 'Pro', 'Elite',
            'Prime', 'Alpha', 'Beta', 'Cyber', 'Tech', 'Digital', 'Cloud',
            'Next', 'Future', 'Modern', 'Innovative', 'Strategic',
        ];

        $words = [
            'Logic', 'Vision', 'Focus', 'Core', 'Edge', 'Peak', 'Apex',
            'Zenith', 'Matrix', 'Vector', 'Vertex', 'Nexus', 'Flux',
            'Sync', 'Link', 'Bridge', 'Path', 'Wave', 'Stream',
        ];

        // Different naming patterns used in real businesses
        $patterns = [
            // Pattern 1: [Prefix] [Type] [Suffix] (e.g., "Global Technologies LLC")
            function () use ($prefixes, $businessTypes, $businessSuffixes) {
                return $this->faker->randomElement($prefixes).' '.
                       $this->faker->randomElement($businessTypes).' '.
                       $this->faker->randomElement($businessSuffixes);
            },

            // Pattern 2: [Word] [Type] [Suffix] (e.g., "Matrix Solutions Inc")
            function () use ($words, $businessTypes, $businessSuffixes) {
                return $this->faker->randomElement($words).' '.
                       $this->faker->randomElement($businessTypes).' '.
                       $this->faker->randomElement($businessSuffixes);
            },

            // Pattern 3: [Prefix] [Word] [Suffix] (e.g., "Smart Logic Corp")
            function () use ($prefixes, $words, $businessSuffixes) {
                return $this->faker->randomElement($prefixes).' '.
                       $this->faker->randomElement($words).' '.
                       $this->faker->randomElement($businessSuffixes);
            },

            // Pattern 4: [Word][Word] [Suffix] (e.g., "DataStream Ltd")
            function () use ($words, $businessSuffixes) {
                return $this->faker->randomElement($words).
                       $this->faker->randomElement($words).' '.
                       $this->faker->randomElement($businessSuffixes);
            },

            // Pattern 5: [LastName] [Type] [Suffix] (e.g., "Johnson Consulting LLC")
            function () use ($businessTypes, $businessSuffixes) {
                return $this->faker->lastName().' '.
                       $this->faker->randomElement($businessTypes).' '.
                       $this->faker->randomElement($businessSuffixes);
            },
        ];

        // Select random pattern and execute it
        $selectedPattern = $this->faker->randomElement($patterns);

        return $selectedPattern();
    }

    /**
     * Generate unique TAX ID
     */
    public function generateUniqueTaxId(): ?string
    {
        $maxAttempts = 50; // Reduced max attempts before adding suffix
        $attempts = 0;

        // 60% chance to have a tax ID
        if ($this->faker->boolean(60)) {
            do {
                // Add unique suffix after first few attempts to avoid endless loops
                if ($attempts < 10) {
                    $taxId = $this->generateRealisticTaxId();
                } elseif ($attempts < 30) {
                    $taxId = $this->generateRealisticTaxId().'-'.$this->faker->numberBetween(100, 999);
                } else {
                    // Use timestamp for guaranteed uniqueness
                    $taxId = $this->generateRealisticTaxId().'-'.substr(microtime(true) * 1000, -6);
                }

                $attempts++;

                if ($attempts >= $maxAttempts) {
                    // Final fallback with guaranteed unique identifier
                    $taxId = $this->generateRealisticTaxId().'#'.uniqid();
                    break;
                }
            } while (in_array($taxId, self::$usedTaxIds));

            self::$usedTaxIds[] = $taxId;

            return $taxId;
        }

        return null;
    }

    /**
     * Generate realistic TAX IDs based on different country formats
     */
    private function generateRealisticTaxId(): string
    {
        $formats = [
            // US EIN format: XX-XXXXXXX
            function () {
                return $this->faker->numerify('##-#######');
            },

            // EU VAT format: DE123456789
            function () {
                $countries = ['DE', 'FR', 'ES', 'IT', 'NL', 'BE', 'AT', 'PL'];

                return $this->faker->randomElement($countries).$this->faker->numerify('#########');
            },

            // UK VAT format: GB123456789
            function () {
                return 'GB'.$this->faker->numerify('#########');
            },

            // Canadian BN format: 123456789RT0001
            function () {
                return $this->faker->numerify('#########').'RT0001';
            },

            // Australian ABN format: 12 345 678 901
            function () {
                return $this->faker->numerify('## ### ### ###');
            },

            // Generic format: 12345678901
            function () {
                return $this->faker->numerify('###########');
            },
        ];

        $selectedFormat = $this->faker->randomElement($formats);

        return $selectedFormat();
    }

    /**
     * Generate unique email for the user
     */
    public function generateUniqueEmail(string $firstname, string $lastname): string
    {
        $maxAttempts = 50; // Reduced max attempts before adding suffix
        $attempts = 0;

        do {
            // Add unique suffix after first few attempts to avoid endless loops
            if ($attempts < 10) {
                $email = $this->generateRealisticEmail($firstname, $lastname);
            } elseif ($attempts < 30) {
                $email = $this->generateRealisticEmailWithNumber($firstname, $lastname);
            } else {
                // Use timestamp for guaranteed uniqueness
                $email = $this->generateRealisticEmailWithTimestamp($firstname, $lastname);
            }

            $attempts++;

            if ($attempts >= $maxAttempts) {
                // Final fallback with guaranteed unique identifier
                $emailParts = explode('@', $this->generateRealisticEmail($firstname, $lastname));
                $email = $emailParts[0].uniqid().'@'.$emailParts[1];
                break;
            }
        } while (in_array($email, self::$usedEmails) || $this->emailExistsInDatabase($email));

        self::$usedEmails[] = $email;

        return $email;
    }

    /**
     * Check if email exists in database
     */
    private function emailExistsInDatabase(string $email): bool
    {
        return \App\Models\User::where('email', $email)->exists();
    }

    /**
     * Generate realistic email with number suffix
     */
    private function generateRealisticEmailWithNumber(string $firstname, string $lastname): string
    {
        $domains = [
            'gmail.com', 'outlook.com', 'yahoo.com', 'hotmail.com', 'icloud.com',
            'protonmail.com', 'mail.com', 'aol.com', 'live.com', 'msn.com',
        ];

        $firstName = strtolower(str_replace(' ', '', $firstname));
        $lastName = strtolower(str_replace(' ', '', $lastname));
        $number = $this->faker->numberBetween(1, 9999);

        $patterns = [
            $firstName.'.'.$lastName.$number.'@'.$this->faker->randomElement($domains),
            $firstName.$lastName.$number.'@'.$this->faker->randomElement($domains),
            $firstName.$number.'@'.$this->faker->randomElement($domains),
        ];

        return $this->faker->randomElement($patterns);
    }

    /**
     * Generate realistic email with timestamp suffix
     */
    private function generateRealisticEmailWithTimestamp(string $firstname, string $lastname): string
    {
        $domains = [
            'gmail.com', 'outlook.com', 'yahoo.com', 'hotmail.com', 'icloud.com',
            'protonmail.com', 'mail.com', 'aol.com', 'live.com', 'msn.com',
        ];

        $firstName = strtolower(str_replace(' ', '', $firstname));
        $lastName = strtolower(str_replace(' ', '', $lastname));
        $timestamp = substr(microtime(true) * 1000, -6);

        return $firstName.'.'.$lastName.$timestamp.'@'.$this->faker->randomElement($domains);
    }

    /**
     * Generate realistic email addresses
     */
    private function generateRealisticEmail(string $firstname, string $lastname): string
    {
        $domains = [
            'gmail.com', 'outlook.com', 'yahoo.com', 'hotmail.com', 'icloud.com',
            'protonmail.com', 'mail.com', 'aol.com', 'live.com', 'msn.com',
            'yandex.com', 'mail.ru', 'gmx.com', 'tutanota.com', 'zoho.com',
        ];

        $businessDomains = [
            'company.com', 'business.net', 'corp.com', 'enterprise.org',
            'solutions.com', 'services.net', 'consulting.com', 'tech.io',
        ];

        // Normalize names for email
        $firstName = strtolower(str_replace(' ', '', $firstname));
        $lastName = strtolower(str_replace(' ', '', $lastname));

        $patterns = [
            // firstname.lastname@domain.com
            function () use ($firstName, $lastName, $domains) {
                return $firstName.'.'.$lastName.'@'.$this->faker->randomElement($domains);
            },

            // firstname_lastname@domain.com
            function () use ($firstName, $lastName, $domains) {
                return $firstName.'_'.$lastName.'@'.$this->faker->randomElement($domains);
            },

            // firstnamelastname@domain.com
            function () use ($firstName, $lastName, $domains) {
                return $firstName.$lastName.'@'.$this->faker->randomElement($domains);
            },

            // firstname123@domain.com
            function () use ($firstName, $domains) {
                return $firstName.$this->faker->numberBetween(1, 999).'@'.$this->faker->randomElement($domains);
            },

            // f.lastname@domain.com
            function () use ($firstName, $lastName, $domains) {
                return substr($firstName, 0, 1).'.'.$lastName.'@'.$this->faker->randomElement($domains);
            },

            // Business email: firstname@businessdomain.com
            function () use ($firstName, $businessDomains) {
                return $firstName.'@'.$this->faker->randomElement($businessDomains);
            },
        ];

        $selectedPattern = $this->faker->randomElement($patterns);

        return $selectedPattern();
    }

    /**
     * Generate realistic billing address data
     */
    public function generateBillingAddressData(): array
    {
        // Get random country and region
        $country = Country::inRandomOrder()->first();
        $region = null;

        if ($country && $country->regions()->exists()) {
            $region = $country->regions()->inRandomOrder()->first();
        }

        // Fallback to home company location if no country/region found
        if (! $country || ! $region) {
            $homeCompany = HomeCompany::where('default', true)->first();
            if ($homeCompany) {
                $country = $homeCompany->country;
                $region = $homeCompany->region;
            }
        }

        return [
            'name' => 'Billing Address',
            'type' => 'billing',
            'contact_name' => $this->faker->name(),
            'contact_phone' => $this->faker->phoneNumber(),
            'contact_email' => null, // Will be set to user's email
            'address_1' => $this->faker->streetAddress(),
            'address_2' => $this->faker->optional(0.3)->secondaryAddress(),
            'city' => $this->faker->city(),
            'postcode' => $this->faker->postcode(),
            'country_uuid' => $country ? $country->uuid : null,
            'region_uuid' => $region ? $region->uuid : null,
            'notes' => $this->faker->optional(0.2)->sentence(),
        ];
    }

    /**
     * Create client with company name
     */
    public function withCompany(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'company_name' => $this->generateUniqueCompanyName(),
                'tax_id' => $this->generateUniqueTaxId(),
            ];
        });
    }

    /**
     * Create client without company (individual)
     */
    public function individual(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'company_name' => null,
                'tax_id' => null,
            ];
        });
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
     * Reset used values (useful for testing)
     */
    public static function resetUsedValues(): void
    {
        self::$usedEmails = [];
        self::$usedCompanyNames = [];
        self::$usedTaxIds = [];
    }

    /**
     * Get used values for debugging
     */
    public static function getUsedValues(): array
    {
        return [
            'emails' => self::$usedEmails,
            'company_names' => self::$usedCompanyNames,
            'tax_ids' => self::$usedTaxIds,
        ];
    }
}
