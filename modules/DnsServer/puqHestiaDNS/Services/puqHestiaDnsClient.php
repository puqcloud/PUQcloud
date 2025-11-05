<?php

/*
 * PUQcloud - Free Cloud Billing System
 * Main billing system core logic
 *
 * Copyright (C) 2025 PUQ sp. z o.o.
 * Licensed under GNU GPLv3
 * https://www.gnu.org/licenses/gpl-3.0.html
 *
 * Author: Dmytro Kravchenko <dmytro@kravchenko.im>
 * Website: https://puqcloud.com
 * E-mail: support@puqcloud.com
 *
 * Do not remove this header.
 */

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class puqHestiaDnsClient
{
    public string $api_host;
    public string $username;
    public string $access_key;
    public string $secret_key;
    public int $api_timeout;

    public function __construct(array $config, $api_timeout = 300)
    {
        $this->api_host = $config['server'] ?? '';
        $this->username = $config['username'] ?? '';
        $this->access_key = $config['access_key'] ?? '';
        $this->secret_key = $config['secret_key'] ?? '';
        $this->api_timeout = $api_timeout;
    }

    /**
     * Send request to HestiaCP API
     */
    private function requestAPI(string $command, array $params = []): array
    {
        // Prepare API URL - ensure it has protocol and proper format
        $host = $this->api_host;
        
        // Add https:// if no protocol specified
        if (!preg_match('/^https?:\/\//i', $host)) {
            $host = 'https://' . $host;
        }
        
        // Add default port :8083 if no port specified
        if (!preg_match('/:\d+/', $host)) {
            $host = rtrim($host, '/') . ':8083';
        }
        
        $url = rtrim($host, '/') . '/api/';

        $this->logInfo('API Request - Init', [
            'command' => $command,
            'params' => $params,
            'url' => $url,
        ]);

        try {
            $postData = [
                'hash' => $this->access_key . ':' . $this->secret_key,
                'returncode' => 'json',
                'cmd' => $command,
            ];

            // Add parameters as arg1, arg2, arg3, etc.
            $argIndex = 1;
            foreach ($params as $param) {
                $postData['arg' . $argIndex] = $param;
                $argIndex++;
            }

            $http = Http::timeout($this->api_timeout)
                ->withOptions(['verify' => false])
                ->asForm();

            $response = $http->post($url, $postData);

            $statusCode = $response->status();
            $body = $response->body();

            // Try to decode JSON response
            $json = json_decode($body, true);

            if ($response->successful()) {
                // Check HestiaCP return code
                if (is_array($json)) {
                    // If response has error field
                    if (isset($json['error']) && $json['error'] != 0) {
                        $errorMessage = $json['error_msg'] ?? $json['error_text'] ?? 'Unknown HestiaCP error';
                        $this->logError('API Request - HestiaCP Error', $errorMessage, [
                            'url' => $url,
                            'command' => $command,
                            'params' => $params,
                            'response' => $json
                        ]);

                        return [
                            'status' => 'error',
                            'errors' => [$errorMessage],
                            'code' => $json['error'] ?? 1,
                        ];
                    }

                    $this->logInfo('API Request - Success', ['url' => $url, 'command' => $command, 'params' => $params], $json);
                    return [
                        'status' => 'success',
                        'data' => $json,
                        'code' => 0,
                    ];
                }

                // Response is not JSON
                $this->logInfo('API Request - Success (non-JSON)', ['url' => $url, 'command' => $command, 'params' => $params], $body);
                return [
                    'status' => 'success',
                    'data' => $body,
                    'code' => 0,
                ];
            }

            // HTTP error
            $errors = [];
            if (is_array($json) && isset($json['error_msg'])) {
                $errors = [$json['error_msg']];
            } elseif (is_array($json) && isset($json['error'])) {
                $errors = [$json['error']];
            } else {
                $errors = [$body ?: 'Unknown API error'];
            }

            $this->logError('API Request - Error Response', $body, [
                'url' => $url,
                'command' => $command,
                'params' => $params,
                'status' => $statusCode
            ]);

            return [
                'status' => 'error',
                'errors' => $errors,
                'code' => $statusCode,
            ];

        } catch (RequestException $e) {
            $this->logError('API Request - RequestException', $e->getMessage(), [
                'url' => $url,
                'command' => $command,
                'params' => $params
            ]);
            return ['status' => 'error', 'errors' => [$e->getMessage()]];
        } catch (\Throwable $e) {
            $this->logError('API Request - Throwable', $e->getMessage(), [
                'url' => $url,
                'command' => $command,
                'params' => $params
            ]);
            return ['status' => 'error', 'errors' => [$e->getMessage()]];
        }
    }

    /**
     * Test connection to HestiaCP API
     */
    public function testConnection(): array
    {
        $result = $this->requestAPI('v-list-user', [$this->username]);

        if ($result['status'] === 'success') {
            $userData = $result['data'][$this->username] ?? $result['data'];
            $name = $userData['NAME'] ?? $this->username;
            
            return [
                'status' => 'success',
                'message' => 'Connection to HestiaCP API successful',
                'data' => [
                    'user' => $name,
                    'username' => $this->username,
                ],
            ];
        }

        return [
            'status' => 'error',
            'message' => 'Failed to connect to HestiaCP API',
            'errors' => $result['errors'] ?? ['Unknown error'],
        ];
    }

    /**
     * List DNS domains for user
     */
    public function listDnsDomains(): array
    {
        return $this->requestAPI('v-list-dns-domains', [$this->username, 'json']);
    }

    /**
     * List DNS records for domain
     */
    public function listDnsRecords(string $domain): array
    {
        return $this->requestAPI('v-list-dns-records', [$this->username, $domain, 'json']);
    }

    /**
     * Add DNS domain
     * v-add-dns-domain USER DOMAIN IP [NS1] [NS2] [NS3] [NS4] [NS5] [NS6] [NS7] [NS8] [RESTART]
     */
    public function addDnsDomain(string $domain, string $ip, array $nameservers = [], bool $restart = true): array
    {
        $params = [$this->username, $domain, $ip];
        
        // Add up to 8 nameservers
        for ($i = 0; $i < 8; $i++) {
            $params[] = $nameservers[$i] ?? '';
        }
        
        // Add restart parameter
        $params[] = $restart ? 'yes' : 'no';

        return $this->requestAPI('v-add-dns-domain', $params);
    }

    /**
     * Delete DNS domain
     * v-delete-dns-domain USER DOMAIN
     */
    public function deleteDnsDomain(string $domain): array
    {
        return $this->requestAPI('v-delete-dns-domain', [$this->username, $domain]);
    }

    /**
     * Add DNS record
     * v-add-dns-record USER DOMAIN RECORD TYPE VALUE [PRIORITY] [ID] [RESTART] [TTL]
     */
    public function addDnsRecord(string $domain, string $record, string $type, string $value, ?int $priority = null, ?int $id = null, bool $restart = true, ?int $ttl = null): array
    {
        $params = [
            $this->username,
            $domain,
            $record,
            $type,
            $value,
            $priority ?? '',
            $id ?? '',
            $restart ? 'yes' : 'no',
            $ttl ?? '',
        ];

        return $this->requestAPI('v-add-dns-record', $params);
    }

    /**
     * Change DNS record
     * v-change-dns-record USER DOMAIN ID RECORD TYPE VALUE [PRIORITY] [RESTART] [TTL]
     */
    public function changeDnsRecord(string $domain, int $id, string $record, string $type, string $value, ?int $priority = null, bool $restart = true, ?int $ttl = null): array
    {
        $params = [
            $this->username,
            $domain,
            $id,
            $record,
            $type,
            $value,
            $priority ?? '',
            $restart ? 'yes' : 'no',
            $ttl ?? '',
        ];

        return $this->requestAPI('v-change-dns-record', $params);
    }

    /**
     * Delete DNS record
     * v-delete-dns-record USER DOMAIN ID [RESTART]
     */
    public function deleteDnsRecord(string $domain, int $id, bool $restart = true): array
    {
        $this->logInfo('deleteDnsRecord - Called', [
            'domain' => $domain,
            'id' => $id,
            'restart' => $restart,
            'username' => $this->username
        ], []);
        
        $params = [
            $this->username,
            $domain,
            $id,
            $restart ? 'yes' : 'no',
        ];

        $result = $this->requestAPI('v-delete-dns-record', $params);
        
        $this->logInfo('deleteDnsRecord - Result', [
            'domain' => $domain,
            'id' => $id,
            'result_status' => $result['status'] ?? 'unknown',
            'result_errors' => $result['errors'] ?? [],
            'result_code' => $result['code'] ?? null
        ], $result);

        return $result;
    }

    /**
     * Change DNS domain SOA
     * v-change-dns-domain-soa USER DOMAIN SOA [RESTART]
     */
    public function changeDnsDomainSoa(string $domain, string $soa, bool $restart = true): array
    {
        $params = [
            $this->username,
            $domain,
            $soa,
            $restart ? 'yes' : 'no',
        ];

        return $this->requestAPI('v-change-dns-domain-soa', $params);
    }

    /**
     * Change DNS domain TTL
     * v-change-dns-domain-ttl USER DOMAIN TTL [RESTART]
     */
    public function changeDnsDomainTtl(string $domain, int $ttl, bool $restart = true): array
    {
        $params = [
            $this->username,
            $domain,
            $ttl,
            $restart ? 'yes' : 'no',
        ];

        return $this->requestAPI('v-change-dns-domain-ttl', $params);
    }

    /**
     * Change DNS domain IP
     * v-change-dns-domain-ip USER DOMAIN IP [RESTART]
     */
    public function changeDnsDomainIp(string $domain, string $ip, bool $restart = true): array
    {
        $params = [
            $this->username,
            $domain,
            $ip,
            $restart ? 'yes' : 'no',
        ];

        return $this->requestAPI('v-change-dns-domain-ip', $params);
    }

    private function logDebug(string $action, array $details = [], mixed $message = ''): void
    {
        if (function_exists('logModule')) {
            logModule('DnsServer', 'puqHestiaDNS', $action, 'debug', $details, $message);
        }
    }

    private function logInfo(string $action, array $details = [], mixed $message = ''): void
    {
        if (function_exists('logModule')) {
            logModule('DnsServer', 'puqHestiaDNS', $action, 'info', $details, $message);
        }
    }

    private function logError(string $action, string $message, mixed $details = []): void
    {
        if (function_exists('logModule')) {
            logModule('DnsServer', 'puqHestiaDNS', $action, 'error', $details, $message);
        }
    }
}
