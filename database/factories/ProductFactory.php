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

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    /**
     * Model corresponding to factory.
     *
     * @var string
     */
    protected $model = Product::class;

    /**
     * Static array to track used names and ensure uniqueness
     */
    private static array $usedNames = [];

    /**
     * Define the default state of the model.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => Str::uuid(),
            'key' => $this->generateUniqueKey(),
            'module_uuid' => null, // Will be set if needed
            'welcome_email_uuid' => null, // Will be set if needed
            'suspension_email_uuid' => null, // Will be set if needed
            'unsuspension_email_uuid' => null, // Will be set if needed
            'termination_email_uuid' => null, // Will be set if needed
            'hidden' => $this->faker->boolean(15), // 15% chance to be hidden
            'retired' => $this->faker->boolean(5), // 5% chance to be retired
            'stock_control' => $this->faker->boolean(30), // 30% chance to have stock control
            'quantity' => $this->faker->numberBetween(0, 500),
            'configuration' => json_encode([
                'featured' => $this->faker->boolean(20), // 20% chance to be featured
                'popular' => $this->faker->boolean(25),  // 25% chance to be popular
                'bestseller' => $this->faker->boolean(15), // 15% chance to be bestseller
                'new_product' => $this->faker->boolean(10), // 10% chance to be new
                'recommended' => $this->faker->boolean(30), // 30% chance to be recommended
                'enterprise_grade' => $this->faker->boolean(40), // 40% chance to be enterprise grade
                'type' => $this->faker->randomElement(['vps', 'cloud', 'dedicated', 'shared', 'hosting', 'storage', 'backup']),
                'performance_tier' => $this->faker->randomElement(['basic', 'standard', 'premium', 'enterprise']),
                'region' => $this->faker->randomElement(['us-east', 'us-west', 'eu-central', 'asia-pacific']),
                'sla_uptime' => $this->faker->randomElement(['99.9%', '99.95%', '99.99%', '99.999%']),
                'support_level' => $this->faker->randomElement(['community', 'standard', 'priority', 'enterprise']),
                'deployment_time' => $this->faker->randomElement(['instant', '5 minutes', '15 minutes', '1 hour']),
                'backup_included' => $this->faker->boolean(70), // 70% chance to include backup
                'monitoring_included' => $this->faker->boolean(60), // 60% chance to include monitoring
                'security_features' => $this->generateSecurityFeatures(),
                'compliance' => $this->generateComplianceFeatures(),
            ]),
            'termination_delay_hours' => $this->faker->randomElement([0, 24, 48, 72, 168]), // 0, 1 day, 2 days, 3 days, 1 week
            'notes' => $this->generateProductNotes(),
        ];
    }

    /**
     * Generate unique key for product
     */
    private function generateUniqueKey(): string
    {
        $name = $this->generateUniqueProductName();

        return Str::slug($name).'-'.substr(Str::uuid()->toString(), 0, 6);
    }

    /**
     * Generate unique realistic product names
     */
    public function generateUniqueProductName(): string
    {
        $maxAttempts = 1000;
        $attempts = 0;

        do {
            $name = $this->generateRealisticProductName();
            $attempts++;

            if ($attempts >= $maxAttempts) {
                // If we can't find unique name, add timestamp to ensure uniqueness
                $name = $name.' '.date('His');
                break;
            }
        } while (in_array($name, self::$usedNames));

        self::$usedNames[] = $name;

        return $name;
    }

    /**
     * Generate realistic product names based on actual hosting industry patterns
     */
    private function generateRealisticProductName(): string
    {
        // Real hosting industry terms
        $brands = [
            'CloudScale', 'ServerMax', 'HostForce', 'VirtualSpace', 'NetCore', 'DataCenter',
            'CyberHost', 'WebEngine', 'CloudNet', 'HostingPro', 'ServerCloud', 'VPS-Pro',
            'EliteHost', 'MegaCloud', 'TurboHost', 'FastServer', 'PowerHost', 'SecureCloud',
            'WebForce', 'HostMax', 'CloudForce', 'ServerPro', 'WebCloud', 'HostEngine',
            'CloudPower', 'ServerForce', 'WebMax', 'HostCloud', 'VirtualMax', 'NetForce',
        ];

        $serverTypes = [
            'VPS', 'Cloud Server', 'Dedicated Server', 'Virtual Server', 'Web Hosting',
            'Cloud Hosting', 'Managed Server', 'Container', 'Instance', 'Node',
            'Droplet', 'Compute', 'Virtual Machine', 'Cloud Instance', 'Server',
        ];

        $sizes = [
            'Nano', 'Micro', 'Small', 'Medium', 'Large', 'XL', 'XXL', '2X', '4X', '8X',
            'Mini', 'Standard', 'Advanced', 'Premium', 'Ultimate', 'Enterprise',
        ];

        $tiers = [
            'Starter', 'Basic', 'Standard', 'Professional', 'Business', 'Enterprise',
            'Developer', 'Production', 'Performance', 'High-Performance', 'Economy',
            'Optimized', 'Enhanced', 'Managed', 'Self-Managed', 'Fully Managed',
        ];

        $features = [
            'SSD', 'NVMe', 'High-Memory', 'CPU-Optimized', 'Storage-Optimized',
            'Network-Optimized', 'GPU-Enabled', 'Burst', 'Dedicated-CPU',
            'Shared-CPU', 'Balanced', 'Compute-Optimized', 'Memory-Optimized',
        ];

        $locations = [
            'US-East', 'US-West', 'EU-Central', 'EU-West', 'Asia-Pacific',
            'Frankfurt', 'London', 'Amsterdam', 'New York', 'Singapore',
            'Toronto', 'Sydney', 'Tokyo', 'Mumbai', 'São Paulo',
        ];

        // Different naming patterns used in real hosting industry
        $patterns = [
            // Pattern 1: Brand + Server Type + Size (e.g., "CloudScale VPS Medium")
            function () use ($brands, $serverTypes, $sizes) {
                return $this->faker->randomElement($brands).' '.
                       $this->faker->randomElement($serverTypes).' '.
                       $this->faker->randomElement($sizes);
            },

            // Pattern 2: Tier + Feature + Server Type (e.g., "Professional SSD Cloud Server")
            function () use ($tiers, $features, $serverTypes) {
                return $this->faker->randomElement($tiers).' '.
                       $this->faker->randomElement($features).' '.
                       $this->faker->randomElement($serverTypes);
            },

            // Pattern 3: Size + Feature + Type (e.g., "Large CPU-Optimized Instance")
            function () use ($sizes, $features, $serverTypes) {
                return $this->faker->randomElement($sizes).' '.
                       $this->faker->randomElement($features).' '.
                       $this->faker->randomElement($serverTypes);
            },

            // Pattern 4: Brand + Tier + Size (e.g., "HostForce Premium XL")
            function () use ($brands, $tiers, $sizes) {
                return $this->faker->randomElement($brands).' '.
                       $this->faker->randomElement($tiers).' '.
                       $this->faker->randomElement($sizes);
            },

            // Pattern 5: Feature + Tier + Type (e.g., "NVMe Professional VPS")
            function () use ($features, $tiers, $serverTypes) {
                return $this->faker->randomElement($features).' '.
                       $this->faker->randomElement($tiers).' '.
                       $this->faker->randomElement($serverTypes);
            },

            // Pattern 6: Location-specific naming (e.g., "Frankfurt Enterprise Cloud")
            function () use ($locations, $tiers, $serverTypes) {
                return $this->faker->randomElement($locations).' '.
                       $this->faker->randomElement($tiers).' '.
                       $this->faker->randomElement($serverTypes);
            },

            // Pattern 7: Size + Brand + Type (e.g., "Medium CloudNet Server")
            function () use ($sizes, $brands, $serverTypes) {
                return $this->faker->randomElement($sizes).' '.
                       $this->faker->randomElement($brands).' '.
                       $this->faker->randomElement($serverTypes);
            },

            // Pattern 8: Tier + Location + Type (e.g., "Business Amsterdam VPS")
            function () use ($tiers, $locations, $serverTypes) {
                return $this->faker->randomElement($tiers).' '.
                       $this->faker->randomElement($locations).' '.
                       $this->faker->randomElement($serverTypes);
            },
        ];

        // Select random pattern and execute it
        $selectedPattern = $this->faker->randomElement($patterns);

        return $selectedPattern();
    }

    /**
     * Generate short description for product
     */
    public function generateShortDescription(string $name): string
    {
        $shortDescriptions = [
            'High-performance cloud infrastructure solution',
            'Enterprise-grade hosting with premium support',
            'Scalable virtual private server environment',
            'Managed cloud storage with automatic backup',
            'Professional web hosting for business applications',
            'Dedicated server resources with full control',
            'Cost-effective cloud computing solution',
            'Advanced security and monitoring platform',
            'Reliable hosting with 99.9% uptime guarantee',
            'Flexible cloud infrastructure for modern apps',
            'Premium managed services with 24/7 support',
            'High-availability platform for critical workloads',
            'Secure cloud environment with compliance features',
            'Optimized performance for demanding applications',
            'Complete hosting solution with integrated tools',
        ];

        // Generate contextual short description based on product name
        if (str_contains(strtolower($name), 'vps') || str_contains(strtolower($name), 'virtual')) {
            $vpsDescriptions = [
                'Powerful virtual private server with dedicated resources',
                'Scalable VPS hosting for growing businesses',
                'High-performance virtual server environment',
                'Flexible VPS solution with full root access',
            ];

            return $this->faker->randomElement($vpsDescriptions);
        }

        if (str_contains(strtolower($name), 'storage') || str_contains(strtolower($name), 'backup')) {
            $storageDescriptions = [
                'Reliable cloud storage with automatic synchronization',
                'Secure data backup and recovery solution',
                'Scalable storage infrastructure for all data needs',
                'Enterprise-grade backup with point-in-time recovery',
            ];

            return $this->faker->randomElement($storageDescriptions);
        }

        if (str_contains(strtolower($name), 'dedicated') || str_contains(strtolower($name), 'server')) {
            $dedicatedDescriptions = [
                'Powerful dedicated server with premium hardware',
                'High-performance bare metal server solution',
                'Enterprise dedicated hosting with full control',
                'Premium server infrastructure for demanding workloads',
            ];

            return $this->faker->randomElement($dedicatedDescriptions);
        }

        return $this->faker->randomElement($shortDescriptions);
    }

    /**
     * Generate realistic product description
     */
    public function generateProductDescription(string $name): string
    {
        $performanceFeatures = [
            'High-performance SSD storage for lightning-fast data access',
            'Dedicated CPU cores ensuring consistent performance',
            'Enterprise-grade NVMe drives for maximum I/O performance',
            'Advanced caching mechanisms for optimal response times',
            'Guaranteed IOPS for database-intensive applications',
            'Burst performance capabilities during peak loads',
            'Low-latency network connectivity',
            'High-frequency processors for compute-intensive tasks',
        ];

        $reliabilityFeatures = [
            '99.99% uptime SLA with proactive monitoring',
            'Redundant network connections for maximum availability',
            'Hardware RAID configuration for data protection',
            'Automated daily backups with point-in-time recovery',
            'DDoS protection and advanced security filtering',
            'Multiple data center locations for geographic redundancy',
            '24/7 network operations center monitoring',
            'Hot-swappable components for zero-downtime maintenance',
        ];

        $scalabilityFeatures = [
            'Instant vertical scaling without downtime',
            'Auto-scaling capabilities based on resource utilization',
            'Load balancer integration for horizontal scaling',
            'Flexible resource allocation with real-time adjustments',
            'Container orchestration support',
            'Microservices architecture compatibility',
            'API-driven infrastructure management',
            'Seamless migration between instance types',
        ];

        $managementFeatures = [
            'Intuitive control panel with one-click deployments',
            'Full root access with complete administrative control',
            'Pre-configured application stacks available',
            'Automated security updates and patch management',
            'Advanced monitoring dashboard with real-time metrics',
            'RESTful API for programmatic management',
            'Integration with popular DevOps tools',
            'Command-line interface for power users',
        ];

        $allFeatures = array_merge($performanceFeatures, $reliabilityFeatures, $scalabilityFeatures, $managementFeatures);

        // Select 4-6 random features
        $selectedFeatures = $this->faker->randomElements($allFeatures, $this->faker->numberBetween(4, 6));

        // Generate description based on product name context
        $description = "The {$name} delivers enterprise-grade cloud infrastructure designed for modern applications. ";
        $description .= "This solution combines cutting-edge technology with industry-leading reliability to meet your most demanding workloads.\n\n";
        $description .= "Key Features:\n";

        foreach ($selectedFeatures as $feature) {
            $description .= "• {$feature}\n";
        }

        $useCases = [
            'web applications and e-commerce platforms',
            'development and testing environments',
            'database servers and data analytics',
            'containerized applications and microservices',
            'CI/CD pipelines and DevOps workflows',
            'machine learning and AI workloads',
            'content management systems',
            'high-traffic websites and APIs',
        ];

        $selectedUseCases = $this->faker->randomElements($useCases, $this->faker->numberBetween(2, 4));
        $description .= "\nPerfect for ".implode(', ', $selectedUseCases).'. ';
        $description .= 'Scale your infrastructure with confidence and focus on building great applications.';

        return $description;
    }

    /**
     * Generate security features for configuration
     */
    private function generateSecurityFeatures(): array
    {
        $allFeatures = [
            'ssl_certificate', 'ddos_protection', 'firewall', 'intrusion_detection',
            'malware_scanning', 'vulnerability_assessment', 'access_control',
            'two_factor_auth', 'encryption_at_rest', 'encryption_in_transit',
            'audit_logging', 'security_monitoring', 'penetration_testing',
        ];

        return $this->faker->randomElements($allFeatures, $this->faker->numberBetween(2, 6));
    }

    /**
     * Generate compliance features for configuration
     */
    private function generateComplianceFeatures(): array
    {
        $allCompliance = [
            'gdpr', 'hipaa', 'pci_dss', 'sox', 'iso_27001', 'iso_9001',
            'soc_2', 'fips_140_2', 'common_criteria', 'fedramp',
        ];

        return $this->faker->randomElements($allCompliance, $this->faker->numberBetween(1, 4));
    }

    /**
     * Generate product notes
     */
    private function generateProductNotes(): string
    {
        $noteTemplates = [
            'High-performance solution designed for enterprise workloads',
            'Scalable infrastructure with automatic resource allocation',
            'Premium service with 24/7 technical support included',
            'Cost-effective solution for small to medium businesses',
            'Enterprise-grade security and compliance features',
            'Optimized for high-availability applications',
            'Ideal for development and testing environments',
            'Production-ready with advanced monitoring capabilities',
            'Fully managed service with automatic updates',
            'Self-service platform with complete control',
        ];

        return $this->faker->randomElement($noteTemplates);
    }

    /**
     * Reset used names (useful for testing)
     */
    public static function resetUsedNames(): void
    {
        self::$usedNames = [];
    }

    /**
     * Add translations to the product
     */
    public function withTranslations()
    {
        return $this->afterCreating(function (Product $product) {
            // Create a new instance to get fresh faker data
            $factory = new static;

            // Generate unique product name and descriptions
            $name = $factory->generateUniqueProductName();
            $shortDescription = $factory->generateShortDescription($name);
            $description = $factory->generateProductDescription($name);

            // Set translatable fields using direct assignment
            $product->name = $name;
            $product->short_description = $shortDescription;
            $product->description = $description;
            $product->save();
        });
    }
}
