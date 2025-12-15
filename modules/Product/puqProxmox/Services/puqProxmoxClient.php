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

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use phpseclib3\Net\SSH2;

class puqProxmoxClient
{
    public SSH2 $ssh;

    public string $ssh_host;
    public int $ssh_port;
    public string $ssh_username;
    public string $ssh_password;
    public int $ssh_timeout;

    public string $api_host;
    public int $api_port;
    public string $api_token_id;
    public string $api_token;
    public int $api_timeout;

    public function __construct(array $config, $ssh_timeout = 300, $api_timeout = 300)
    {
        $this->ssh_host = $config['ssh_host'] ?? '';
        $this->ssh_port = (int) ($config['ssh_port'] ?? 22);
        $this->ssh_username = $config['ssh_username'] ?? '';
        $this->ssh_password = Crypt::decryptString($config['ssh_password']);

        $this->api_host = $config['api_host'] ?? '';
        $this->api_port = (int) ($config['api_port'] ?? 8006);
        $this->api_token_id = $config['api_token_id'] ?? '';
        $this->api_token = Crypt::decryptString($config['api_token']);

        $this->ssh_timeout = $ssh_timeout;
        $this->api_timeout = $api_timeout;

        $this->ssh = new SSH2($this->ssh_host, $this->ssh_port, $this->ssh_timeout);

        $this->ssh->setTimeout($this->ssh_timeout);
        $this->ssh->setKeepAlive(5);
    }

    private function requestAPI(string $path, string $method = 'GET', array $data = []): array
    {
        $url = "https://{$this->api_host}:{$this->api_port}/api2/json{$path}";

//        $this->logDebug('API Request - Init', [
//            'path' => $path,
//            'method' => $method,
//            'data' => $data,
//            'url' => $url,
//        ]);

        try {
            $method = strtoupper($method);

//            $this->logDebug('API Request - Preparing HTTP client', [
//                'timeout' => $this->api_timeout,
//                'token_id' => $this->api_token_id,
//            ]);

            $http = Http::withHeaders([
                'Authorization' => "PVEAPIToken={$this->api_token_id}={$this->api_token}",
            ])->timeout($this->api_timeout)
                ->withOptions(['verify' => false]);

//            $this->logDebug('API Request - Sending Request', [
//                'method' => $method,
//                'url' => $url,
//                'data' => $data,
//            ]);

            $response = match ($method) {
                'GET' => $http->get($url, $data),
                'POST' => $http->post($url, $data),
                'PUT' => $http->put($url, $data),
                'PATCH' => $http->patch($url, $data),
                'DELETE' => $http->delete($url, $data),
                default => throw new \Exception("Unsupported method: $method"),
            };

            $statusCode = $response->status();

            $logContext = [
                'url' => $url,
                'method' => $method,
                'data' => $data,
                'status' => $statusCode,
                'headers' => $response->headers(),
            ];

            if ($response->successful()) {

                return [
                    'status' => 'success',
                    'data' => $response->json()['data'] ?? $response->json(),
                    'code' => $statusCode,
                ];
            }

            $this->logError('API Request - Error Response', $response->body(), $logContext);

            $json = $response->json();

            if (!empty($json['message'])) {
                $errors = [$json['message']];
            } else {
                $errors = [$json];
            }

            return [
                'status' => 'error',
                'errors' => $errors ?: ['Unknown API error'],
                'code' => $statusCode,
            ];

        } catch (RequestException $e) {
            $this->logError('API Request - RequestException', $e->getMessage(), [
                'url' => $url,
                'method' => $method,
                'data' => $data,
            ]);

            return [
                'status' => 'error',
                'errors' => [$e->getMessage()],
            ];
        } catch (\Throwable $e) {
            $this->logError('API Request - Throwable', $e->getMessage(), [
                'url' => $url,
                'method' => $method,
                'data' => $data,
            ]);

            return [
                'status' => 'error',
                'errors' => [$e->getMessage()],
            ];
        }
    }

    private function requestSSH(string $path, string $method = 'GET', array $data = [], bool $jsonOutput = true): array
    {
        $commandsMap = [
            'POST' => 'create',
            'PUT' => 'set',
            'DELETE' => 'delete',
            'GET' => 'get',
            '?' => 'ls',
            'PATCH' => 'set',
            '??' => 'usage',
        ];

        if (!isset($commandsMap[$method])) {
            return [
                'status' => 'error',
                'errors' => ["Unsupported method: $method"],
            ];
        }

        $opts = '';
        foreach ($data as $key => $value) {
            $opts .= ' --'.$key.' '.escapeshellarg($value);
        }

        $execCommand = 'pvesh '.$commandsMap[$method].' '.$path.$opts;

        if ($jsonOutput) {
            $execCommand .= ' --output-format=json';
        }

//        $this->logDebug('SSH Request - Init', [
//            'command' => $path,
//            'options' => $data,
//            'execCommand' => $execCommand,
//        ]);

        try {
            if (!$this->ssh->isConnected() || !$this->ssh->isAuthenticated()) {
                if (!$this->ssh->login($this->ssh_username, $this->ssh_password)) {
                    $errors = $this->ssh->getErrors();
                    $this->logError('SSH login', 'SSH login failed', [
                        'command' => $execCommand,
                        'errors' => $errors,
                    ]);

                    return [
                        'status' => 'error',
                        'errors' => $errors,
                    ];
                }
            }
            $this->ssh->setTimeout($this->ssh_timeout);

            $output = $this->ssh->exec($execCommand);
            $errors = $this->ssh->getErrors();
            $filteredErrors = array_filter($errors, fn($err) => !str_contains($err, 'SSH_MSG_GLOBAL_REQUEST'));

            if (!empty($filteredErrors)) {
                $errors = $this->ssh->getErrors();
                $this->logError('SSH exec', 'SSH command execution error', [
                    'command' => $execCommand,
                    'errors' => $errors,
                ]);

                return [
                    'status' => 'error',
                    'errors' => $errors,
                ];
            }

//            $this->logDebug('SSH Request - Command executed', [
//                'execCommand' => $execCommand,
//                'output' => $output,
//            ]);

            if ($jsonOutput) {
                $decoded = json_decode($output, true);
                if (!is_array($decoded)) {
                    $this->logError('requestSSH', 'Invalid JSON response', $output);

                    return [
                        'status' => 'error',
                        'errors' => ['The answer is not valid JSON'],
                        'data' => $output,
                    ];
                }

                return [
                    'status' => 'success',
                    'data' => $decoded,
                ];
            }

            return [
                'status' => 'success',
                'raw' => $output,
            ];

        } catch (\Throwable $e) {
            $this->logError('requestSSH', 'SSH command execution exception', [
                'command' => $execCommand,
                'exception' => $e->getMessage(),
            ]);

            return [
                'status' => 'error',
                'errors' => [$e->getMessage()],
            ];
        }
    }

    public function executeSSH(string $command, string $node_ip = null, bool $jsonOutput = false): array
    {
        if ($node_ip) {
            $encodedScript = base64_encode($command);
            //$execCommand = "ssh -o StrictHostKeyChecking=no -o ServerAliveInterval=60 -o ServerAliveCountMax=5 root@{$node_ip} \"echo {$encodedScript} | base64 -d | bash\" 2>&1";
            $execCommand = "ssh -q -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null -o ServerAliveInterval=60 -o ServerAliveCountMax=5 root@{$node_ip} \"echo {$encodedScript} | base64 -d | bash\"";
        } else {
            $execCommand = $command;
        }

        $startTime = microtime(true);

        try {
            if (!$this->ssh->isConnected() || !$this->ssh->isAuthenticated()) {
                if (!$this->ssh->login($this->ssh_username, $this->ssh_password)) {
                    $errors = $this->ssh->getErrors();
                    $this->logError('SSH login', 'SSH login failed', [
                        'command' => $execCommand,
                        'errors' => $errors,
                        'duration' => round(microtime(true) - $startTime, 2, PHP_ROUND_HALF_UP),
                    ]);

                    return [
                        'status' => 'error',
                        'errors' => $errors,
                        'duration' => round(microtime(true) - $startTime, 2, PHP_ROUND_HALF_UP),
                    ];
                }
            }

            $this->ssh->setTimeout($this->ssh_timeout);
            $output = $this->ssh->exec($execCommand);
            $duration = round(microtime(true) - $startTime, 2, PHP_ROUND_HALF_UP);

            $errors = $this->ssh->getErrors();
            $filteredErrors = array_filter($errors, fn($err) => !str_contains($err, 'SSH_MSG_GLOBAL_REQUEST'));

            if (!empty($filteredErrors)) {
                $this->logError('SSH exec', 'SSH command execution error', [
                    'command' => $execCommand,
                    'errors' => $filteredErrors,
                    'duration' => $duration,
                ]);

                return [
                    'status' => 'error',
                    'errors' => $filteredErrors,
                    'duration' => $duration,
                ];
            }

            if ($jsonOutput) {
                $decoded = json_decode($output, true);
                if (!is_array($decoded)) {
                    $this->logError('executeSSH', 'Invalid JSON response', [
                        'output' => $output,
                        'duration' => $duration,
                    ]);

                    return [
                        'status' => 'error',
                        'errors' => ['The answer is not valid JSON'],
                        'data' => $output,
                        'duration' => $duration,
                    ];
                }

                return [
                    'status' => 'success',
                    'data' => $decoded,
                    'duration' => $duration,
                ];
            }

            return [
                'status' => 'success',
                'data' => $output,
                'duration' => $duration,
            ];

        } catch (\Throwable $e) {
            $duration = round(microtime(true) - $startTime, 2, PHP_ROUND_HALF_UP);
            $this->logError('executeSSH', 'SSH command execution exception', [
                'command' => $execCommand,
                'exception' => $e->getMessage(),
                'duration' => $duration,
            ]);

            return [
                'status' => 'error',
                'errors' => [$e->getMessage()],
                'duration' => $duration,
            ];
        }
    }


    public function testConnection(): array
    {

        $startApi = microtime(true);
        $api_resources_result = $this->getClusterResources();
        $apiResponseTime = round((microtime(true) - $startApi) * 1000, 0, PHP_ROUND_HALF_UP); // ms

        $startSsh = microtime(true);
        $ssh_resources_result = $this->getClusterResources(true);
        $sshResponseTime = round((microtime(true) - $startSsh) * 1000, 0, PHP_ROUND_HALF_UP); // ms


        if ($api_resources_result['status'] !== 'success') {
            return $api_resources_result;
        }

        if ($ssh_resources_result['status'] !== 'success') {
            return $ssh_resources_result;
        }

        $resources = $api_resources_result['data'];
        $nodesOnline = $nodesOffline = $vmsRunning = $vmsStopped = $lxcRunning = $lxcStopped = 0;

        foreach ($resources as $item) {
            switch ($item['type']) {
                case 'node':
                    $item['status'] === 'online' ? $nodesOnline++ : $nodesOffline++;
                    break;
                case 'qemu':
                    $item['status'] === 'running' ? $vmsRunning++ : $vmsStopped++;
                    break;
                case 'lxc':
                    $item['status'] === 'running' ? $lxcRunning++ : $lxcStopped++;
                    break;
            }
        }


        $api_version_result = $this->getVersion();
        if ($api_version_result['status'] !== 'success') {
            return $api_version_result;
        }
        $version = $api_version_result['data']['version'] ?? 0;

        $api_cluster_status = $this->getClusterStatus();
        foreach ($api_cluster_status['data'] ?? [] as $item) {
            if ($item['type'] === 'cluster') {
                $cluster = $item['name'] ?? '';
                $quorate = $item['quorate'] ?? false;
            }
        }

        return [
            'status' => 'success',
            'data' => [
                'api_response_time' => $apiResponseTime,
                'ssh_response_time' => $sshResponseTime,
                'version' => $version,
                'cluster' => $cluster ?? '',
                'quorate' => $quorate ?? '',
                'nodes' => [
                    'online' => $nodesOnline,
                    'offline' => $nodesOffline,
                ],
                'vms' => [
                    'running' => $vmsRunning,
                    'stopped' => $vmsStopped,
                ],
                'lxc' => [
                    'running' => $lxcRunning,
                    'stopped' => $lxcStopped,
                ],
            ],
            'raw' => $resources,
        ];
    }

    // ------------------------------------------------------------------------------------
    private function request(
        string $path,
        string $method = 'GET',
        array $data = [],
        bool $ssh = false,
        bool $json = true
    ): array {
        if ($ssh) {
            return $this->requestSSH($path, $method, $data, $json);
        } else {
            return $this->requestAPI($path, $method, $data);
        }
    }

    public function getClusterResources(bool $ssh = false): array
    {
        return $this->request('/cluster/resources', 'GET', [], $ssh);
    }

    public function getVersion(bool $ssh = false): array
    {
        return $this->request('/version', 'GET', [], $ssh);
    }

    public function getClusterStatus(bool $ssh = false): array
    {
        return $this->request('/cluster/status', 'GET', [], $ssh);
    }

    public function getNodes(bool $ssh = false): array
    {
        return $this->request('/nodes', 'GET', [], $ssh);
    }

    public function getStorageContent(string $node_name, string $storage_name, bool $ssh = false): array
    {
        return $this->request("/nodes/{$node_name}/storage/{$storage_name}/content", 'GET', [], $ssh);
    }

    public function postLxcTemplateStorageDownloadUrl(
        string $node_name,
        string $storage_name,
        string $file_name,
        string $url,
        bool $ssh = false
    ): array {
        $allowedExtensions = ['.tar.gz', '.tar.xz', '.tar.zst'];

        $hasValidExtension = false;
        foreach ($allowedExtensions as $ext) {
            if (str_ends_with($file_name, $ext)) {
                $hasValidExtension = true;
                break;
            }
        }

        if (!$hasValidExtension) {
            $urlExt = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION);

            if ($urlExt && in_array(".tar.$urlExt", $allowedExtensions)) {
                $file_name .= ".tar.$urlExt";
            } else {
                $file_name .= '.tar.zst';
            }
        }

        $data = ['url' => $url, 'filename' => $file_name, 'content' => 'vztmpl'];

        return $this->request("/nodes/$node_name/storage/$storage_name/download-url", 'POST', $data, $ssh);
    }

    public function deleteLxcTemplateStorageContent(
        string $node_name,
        string $storage_name,
        string $file_name,
        bool $ssh = false
    ): array {
        $allowedExtensions = ['.tar.gz', '.tar.xz', '.tar.zst'];

        $hasValidExtension = false;
        foreach ($allowedExtensions as $ext) {
            if (str_ends_with($file_name, $ext)) {
                $hasValidExtension = true;
                break;
            }
        }

        if (!$hasValidExtension) {
            $file_name .= '.tar.zst';
        }

        return $this->request("/nodes/{$node_name}/storage/{$storage_name}/content/".$file_name,
            'DELETE', [], $ssh);
    }


    public function postLxc(array $data, bool $ssh = false): array
    {
        $node = $data['node'];

        return $this->request("/nodes/{$node}/lxc", 'POST', $data, $ssh);
    }

    public function deleteLxc(array $data, bool $ssh = false): array
    {
        $node = $data['node'];
        $vmid = (int) $data['vmid'];

        return $this->request("/nodes/{$node}/lxc/{$vmid}", 'DELETE', [], $ssh);
    }

    public function startLxc(array $data, bool $ssh = false): array
    {
        $node = $data['node'];
        $vmid = $data['vmid'];

        return $this->request("/nodes/{$node}/lxc/{$vmid}/status/start", 'POST', $data, $ssh);
    }

    public function stopLxc(array $data, bool $ssh = false): array
    {
        $node = $data['node'];
        $vmid = $data['vmid'];

        return $this->request("/nodes/{$node}/lxc/{$vmid}/status/stop", 'POST', $data, $ssh);
    }

    public function vncproxyLxc(array $data, bool $ssh = false): array
    {
        $node = $data['node'];
        $vmid = $data['vmid'];

        return $this->request("/nodes/{$node}/lxc/{$vmid}/vncproxy", 'POST', $data, $ssh);
    }

    public function vncwebsocketLxc(array $data, bool $ssh = false): array
    {
        $node = $data['node'];
        $vmid = $data['vmid'];

        return $this->request("/nodes/{$node}/lxc/{$vmid}/vncwebsocket", 'GET', $data, $ssh);
    }

    public function vncShell(array $data, bool $ssh = false): array
    {
        $node = $data['node'];

        return $this->request("/nodes/{$node}/vncshell", 'POST', $data, $ssh);
    }

    public function getLxcConfig(array $data, bool $ssh = false): array
    {
        $node = $data['node'];
        $vmid = $data['vmid'];

        return $this->request("/nodes/{$node}/lxc/{$vmid}/config", 'GET', $data, $ssh);
    }

    public function deleteLxcConfig(array $data, bool $ssh = false): array
    {
        $node = $data['node'];
        $vmid = $data['vmid'];

        return $this->request("/nodes/{$node}/lxc/{$vmid}/config", 'PUT', $data, $ssh);
    }

    public function putLxcConfig(array $data, bool $ssh = false): array
    {
        $node = $data['node'];
        $vmid = $data['vmid'];

        if ($ssh) {
            unset($data['node']);
            unset($data['vmid']);
        }

        return $this->request("/nodes/{$node}/lxc/{$vmid}/config", 'PUT', $data, $ssh, false);
    }

    public function resizeLxcDisk(array $data, bool $ssh = false): array
    {
        $node = $data['node'];
        $vmid = $data['vmid'];

        return $this->request("/nodes/{$node}/lxc/{$vmid}/resize", 'PUT', $data, $ssh);
    }


    public function getXlcRrdData(array $data, bool $ssh = false): array
    {
        $node = $data['node'];
        $vmid = $data['vmid'];

        return $this->request("/nodes/{$node}/lxc/{$vmid}/rrddata", 'GET', $data, $ssh);
    }

    public function getBackups(array $data, bool $ssh = false): array
    {
        $node = $data['node'];
        $vmid = $data['vmid'];
        $storage = $data['storage'];

        return $this->request("/nodes/{$node}/storage/{$storage}/content", 'GET',
            ['content' => 'backup', 'vmid' => $vmid], $ssh);
    }

    public function deleteFile(array $data, bool $ssh = false): array
    {
        $node = $data['node'];
        $volid = $data['volid'];
        $storage = $data['storage'];

        return $this->request("/nodes/{$node}/storage/{$storage}/content/{$volid}", 'DELETE', [], $ssh);
    }

    public function backupNow(array $data, bool $ssh = false): array
    {
        $node = $data['node'];

        return $this->request("/nodes/{$node}/vzdump", 'POST', $data, $ssh);
    }

    public function restoreLxcBackup(array $data, bool $ssh = false): array
    {
        $node = $data['node'];

        return $this->request("/nodes/{$node}/lxc", 'POST', $data, $ssh);
    }

    public function getLxcFirewallOptions(array $data, bool $ssh = false): array
    {
        $node = $data['node'];
        $vmid = $data['vmid'];

        return $this->request("/nodes/{$node}/lxc/{$vmid}/firewall/options", 'GET', $data, $ssh);
    }

    public function getLxcFirewallRules(array $data, bool $ssh = false): array
    {
        $node = $data['node'];
        $vmid = $data['vmid'];

        return $this->request("/nodes/{$node}/lxc/{$vmid}/firewall/rules", 'GET', $data, $ssh);
    }

    public function putLxcFirewallOptions(array $data, bool $ssh = false): array
    {
        $node = $data['node'];
        $vmid = $data['vmid'];

        return $this->request("/nodes/{$node}/lxc/{$vmid}/firewall/options", 'PUT', $data, $ssh);
    }

    public function putLxcFirewallRuleUpdate(array $data, bool $ssh = false): array
    {
        $node = $data['node'];
        $vmid = $data['vmid'];
        $pos = $data['pos'];

        return $this->request("/nodes/{$node}/lxc/{$vmid}/firewall/rules/{$pos}", 'PUT', $data, $ssh);
    }

    public function deleteLxcFirewallRuleDelete(array $data, bool $ssh = false): array
    {
        $node = $data['node'];
        $vmid = $data['vmid'];
        $pos = $data['pos'];

        return $this->request("/nodes/{$node}/lxc/{$vmid}/firewall/rules/{$pos}", 'DELETE', [], $ssh);
    }

    public function createLxcFirewallRule(array $data, bool $ssh = false): array
    {
        $node = $data['node'];
        $vmid = $data['vmid'];

        return $this->request("/nodes/{$node}/lxc/{$vmid}/firewall/rules", 'POST', $data, $ssh);
    }


    public function getTaskStatus(string $upid, bool $ssh = false): array
    {
        $parts = explode(':', $upid);

        if (count($parts) < 3) {
            throw new \InvalidArgumentException("Invalid UPID format: $upid");
        }

        $node = $parts[1];
        $upidEncoded = urlencode($upid);

        return $this->request("/nodes/{$node}/tasks/{$upidEncoded}/status", 'GET', [], $ssh);
    }

    private function logDebug(string $action, array $details = [], mixed $message = ''): void
    {
        if (function_exists('logModule')) {
            logModule('Product', 'puqProxmox', $action, 'debug', $details, $message);
        }
    }

    private function logInfo(string $action, array $details = [], mixed $message = ''): void
    {
        if (function_exists('logModule')) {
            logModule('Product', 'puqProxmox', $action, 'info', $details, $message);
        }
    }

    private function logError(string $action, string $message, mixed $details = []): void
    {
        if (function_exists('logModule')) {
            logModule('Product', 'puqProxmox', $action, 'error', $details, $message);
        }
    }
}
