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

namespace Modules\Product\puqProxmox\Models;

use App\Models\Task;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use puqProxmoxClient;

class PuqPmCluster extends Model
{
    protected $table = 'puq_pm_clusters';

    protected $primaryKey = 'uuid';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'uuid',
        'name',
        'puq_pm_cluster_group_uuid',
        'description',
        'disable',
        'default',
        'max_accounts',

        'vncwebproxy_domain',
        'vncwebproxy_api_key',

    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });
    }

    public function puqPmClusterGroup(): BelongsTo
    {
        return $this->belongsTo(puqPmClusterGroup::class, 'puq_pm_cluster_group_uuid', 'uuid');
    }

    public function puqPmAccessServers(): HasMany
    {
        return $this->hasMany(PuqPmAccessServer::class, 'puq_pm_cluster_uuid', 'uuid')->orderBy('updated_at', 'desc');
    }

    public function puqPmNodes(): HasMany
    {
        return $this->hasMany(PuqPmNode::class, 'puq_pm_cluster_uuid', 'uuid');
    }

    public function puqPmStorages(): HasMany
    {
        return $this->hasMany(PuqPmStorage::class, 'puq_pm_cluster_uuid', 'uuid');
    }

    public function puqPmPublicNetworks(): HasMany
    {
        return $this->hasMany(PuqPmPublicNetwork::class, 'puq_pm_cluster_uuid', 'uuid');
    }

    public function puqPmPrivateNetworks(): HasMany
    {
        return $this->hasMany(PuqPmPrivateNetwork::class, 'puq_pm_cluster_uuid', 'uuid');
    }

    public function getLocalPrivateNetworks(): Collection
    {
        return $this->puqPmPrivateNetworks()->where('type', 'local_private')->get();
    }

    public function getGlobalPrivateNetworks(): Collection
    {
        return $this->puqPmPrivateNetworks()->where('type', 'global_private')->get();
    }

    public function puqPmLxcInstances(): HasMany
    {
        return $this->hasMany(PuqPmLxcInstance::class, 'puq_pm_cluster_uuid', 'uuid');
    }

    public function getUseAccounts(): int
    {
        $puq_pm_lxc_instance_count = $this->puqPmLxcInstances()->count();

        return $puq_pm_lxc_instance_count;
    }

    public function getNodesByTags(array $tags = []): Collection
    {
        $tag_nodes = $this->puqPmNodes()
            ->whereHas('puqPmTags', function ($query) use ($tags) {
                $query->whereIn('name', $tags);
            })
            ->orderByRaw('(maxmem - mem) DESC')
            ->get();

        return $tag_nodes;
    }

    public function getStoragesByTags(array $tags = []): Collection
    {
        $tag_nodes = $this->puqPmStorages()
            ->whereHas('puqPmTags', function ($query) use ($tags) {
                $query->whereIn('name', $tags);
            })
            ->orderByRaw('(maxdisk - disk) DESC')
            ->get();

        return $tag_nodes;
    }

    public function getPublicNetworksByTags(array $tags = []): Collection
    {
        $tag_nodes = $this->puqPmPublicNetworks()
            ->whereHas('puqPmTags', function ($query) use ($tags) {
                $query->whereIn('name', $tags);
            })
            ->get();

        return $tag_nodes;
    }

    // --------------------------------------------------------

    protected function firstSuccessfulResponse(callable $callback, string $command = ''): array
    {
        foreach ($this->puqPmAccessServers as $access_server) {
            $response = $callback($access_server);

            if (isset($response['status']) && $response['status'] === 'success') {
                $access_server->save();

                return $response;
            } else {
                $description = $response['error'] ?? 'Unknown error';
                logModule(
                    'Product',
                    'puqProxmox',
                    $description,
                    'error',
                    [
                        'command' => $command,
                        'access_server' => $access_server,
                    ],
                    $response
                );
            }
        }

        if (isset($response['status']) && $response['status'] === 'error') {
            return $response;
        }

        return ['status' => 'error', 'error' => 'No Access Servers Available'];
    }

    public function getClusterResources(bool $force = false): array
    {
        $cacheKey = 'cluster_resources_'.$this->uuid;

        if (!$force) {
            $cached = Cache::get($cacheKey);
            if ($cached) {
                return $cached;
            }
        }

        if ($force) {
            Cache::forget($cacheKey);
        }

        $response = $this->firstSuccessfulResponse(function ($access_server) {
            $client = new puqProxmoxClient($access_server->toArray());

            return $client->getClusterResources();
        }, 'getClusterResources');

        if (isset($response['status']) && $response['status'] === 'success') {
            Cache::put($cacheKey, $response, 10);

            return $response;
        }

        return Cache::get($cacheKey, $response);
    }

    public function getSyncClusterInfo(): array
    {
        $command = 'testConnection';

        $response = $this->firstSuccessfulResponse(function ($server) {
            return $server->testConnection();
        }, $command);

        if ($response['status'] === 'success') {
            $this->description = json_encode($response['data']);
            $this->save();
        }

        return $response;
    }

    public function getSyncResources(array $data = []): array
    {
        if (empty($data) || !isset($data['status']) || $data['status'] !== 'success') {
            $data = $this->getClusterResources();
            if (!isset($data['status']) || $data['status'] !== 'success') {
                return $data;
            }
        }
        $this->getSyncClusterNodes($data);
        $this->getSyncClusterStorages($data);
        $this->getSyncClusterLxc($data);

        return $data;
    }

    public function getSyncClusterNodes(array $data = []): array
    {
        if (empty($data) || !isset($data['status']) || $data['status'] !== 'success') {
            $data = $this->getClusterResources();
            if (!isset($data['status']) || $data['status'] !== 'success') {
                return $data;
            }
        }

        $remoteNodeIds = [];
        foreach ($data['data'] as $resource) {
            if ($resource['type'] !== 'node') {
                continue;
            }

            $remoteNodeIds[] = $resource['id'];

            PuqPmNode::updateOrCreate(
                ['id' => $resource['id']],
                [
                    'puq_pm_cluster_uuid' => $this->uuid,
                    'name' => $resource['node'] ?? '',
                    'cpu' => $resource['cpu'] ?? 0,
                    'level' => $resource['level'] ?? '',
                    'maxcpu' => $resource['maxcpu'] ?? 0,
                    'maxmem' => $resource['maxmem'] ?? 0,
                    'mem' => $resource['mem'] ?? 0,
                    'status' => $resource['status'] ?? '',
                    'uptime' => $resource['uptime'] ?? 0,
                ]
            );
        }

        return ['status' => 'success', 'data' => $data];
    }

    public function getSyncClusterStorages(array $data = []): array
    {
        if (empty($data) || !isset($data['status']) || $data['status'] !== 'success') {
            $data = $this->getClusterResources();
            if (!isset($data['status']) || $data['status'] !== 'success') {
                return $data;
            }
        }

        $nodes = $this->puqPmNodes()->get()->keyBy('name');
        $remoteStorageIds = [];

        foreach ((array) $data['data'] as $resource) {
            if ($resource['type'] !== 'storage') {
                continue;
            }

            $node = $nodes[$resource['node']] ?? null;
            if (!$node) {
                continue;
            }

            $remoteStorageIds[] = $resource['id'];

            PuqPmStorage::updateOrCreate(
                ['id' => $resource['id']],
                [
                    'puq_pm_node_uuid' => $node->uuid,
                    'puq_pm_cluster_uuid' => $this->uuid,
                    'name' => $resource['storage'] ?? '',
                    'maxdisk' => $resource['maxdisk'] ?? 0,
                    'disk' => $resource['disk'] ?? 0,
                    'status' => $resource['status'] ?? '',
                    'shared' => $resource['shared'] ?? false,
                    'content' => $resource['content'] ?? '',
                    'plugintype' => $resource['plugintype'] ?? '',
                ]
            );
        }

        return ['status' => 'success', 'data' => $data];
    }

    public function getSyncClusterLxc(array $data = []): array
    {
        if (empty($data) || !isset($data['status']) || $data['status'] !== 'success') {
            $data = $this->getClusterResources();
            if (!isset($data['status']) || $data['status'] !== 'success') {
                return $data;
            }
        }

        $puq_pm_lxc_instances = $this->puqPmLxcInstances()->get()->keyBy('vmid');

        foreach ((array) $data['data'] as $resource) {
            if ($resource['type'] !== 'lxc') {
                continue;
            }

            $puq_pm_lxc_instance = $puq_pm_lxc_instances[$resource['vmid']] ?? null;
            if (!$puq_pm_lxc_instance) {
                continue;
            }
            $puq_pm_lxc_instance->saveStatus($resource);
        }

        return ['status' => 'success', 'data' => $data];
    }

    public function syncLxcTemplatesToStorages(bool $deleteExtra = false): array
    {
        $data = [
            'module' => $this,
            'method' => 'syncLxcTemplatesToStoragesJob',
            'tries' => 1,
            'backoff' => 60,
            'timeout' => 600,
            'maxExceptions' => 1,
            'params' => [$deleteExtra],
        ];

        $tags = [
            'syncLxcTemplatesToStorages',
        ];

        Task::add('ModuleJob', 'puqProxmox-Cluster', $data, $tags);

        return ['status' => 'success'];
    }

    public function syncLxcTemplatesToStoragesJob(bool $deleteExtra = false): string
    {
        $this->getSyncClusterInfo();
        $this->getSyncClusterNodes();
        $this->getSyncClusterStorages();

        $storages = $this->puqPmStorages()
            ->where('content', 'like', '%vztmpl%')
            ->where('status', 'available')
            ->get();

        $handledShared = [];

        foreach ($storages as $storage) {
            $isShared = (bool) $storage->shared;
            $name = $storage->name;

            if ($isShared) {
                if (in_array($name, $handledShared)) {
                    continue;
                }
                $handledShared[] = $name;
            }

            $data = [
                'module' => $this,
                'method' => 'syncLxcTemplatesToStorageJob',
                'tries' => 1,
                'backoff' => 60,
                'timeout' => 600,
                'maxExceptions' => 1,
                'params' => [$storage, $deleteExtra],
            ];

            $tags = [
                'syncLxcTemplatesToStorage',
            ];

            Task::add('ModuleJob', 'puqProxmox-Cluster', $data, $tags);
        }

        return 'success';
    }

    public function syncLxcTemplatesToStorageJob(PuqPmStorage $storage, $deleteExtra = false): string
    {
        $allowedExtensions = ['.tar.gz', '.tar.xz', '.tar.zst'];
        $command = 'getStorageContent';

        // Get the current content of the storage from Proxmox
        $response = $this->firstSuccessfulResponse(function ($access_server) use ($storage) {
            $client = new puqProxmoxClient($access_server->toArray(), 300);

            return $client->getStorageContent($storage->puqPmNode->name, $storage->name);
        }, $command);

        if (isset($response['status']) && $response['status'] === 'success') {

            // Get all LXC templates from the database
            $lxcTemplates = PuqPmLxcTemplate::query()->get();

            // Prepare an array of template names from the database without extensions
            $dbTemplates = [];
            foreach ($lxcTemplates as $lxcTemplate) {
                $templateName = $lxcTemplate->name;
                foreach ($allowedExtensions as $ext) {
                    if (str_ends_with($templateName, $ext)) {
                        $templateName = substr($templateName, 0, -strlen($ext));
                        break;
                    }
                }
                $dbTemplates[$templateName] = $lxcTemplate;
            }

            // Prepare an array of templates currently on the storage without extensions
            // Save their 'volid' for deletion if needed
            $storageTemplates = [];
            foreach ((array) $response['data'] as $content) {
                if (($content['content'] ?? '') === 'vztmpl') {
                    $filename = basename($content['volid']);
                    foreach ($allowedExtensions as $ext) {
                        if (str_ends_with($filename, $ext)) {
                            $nameWithoutExt = substr($filename, 0, -strlen($ext));
                            $storageTemplates[$nameWithoutExt] = $content['volid'];
                            break;
                        }
                    }
                }
            }

            // Download missing templates: those in the database but not on storage
            foreach ($dbTemplates as $templateName => $lxcTemplate) {
                if (!isset($storageTemplates[$templateName])) {
                    $command = 'postLxcTemplateStorageDownloadUrl';

                    $response = $this->firstSuccessfulResponse(function ($access_server) use ($storage, $lxcTemplate) {
                        $client = new puqProxmoxClient($access_server->toArray());

                        return $client->postLxcTemplateStorageDownloadUrl(
                            $storage->puqPmNode->name,
                            $storage->name,
                            $lxcTemplate->name,
                            $lxcTemplate->url
                        );
                    }, $command);
                }
            }

            // Delete extra templates: those on storage but not in the database
            if ($deleteExtra) {
                foreach ($storageTemplates as $templateName => $volid) {
                    if (!isset($dbTemplates[$templateName])) {
                        $command = 'deleteLxcTemplateStorageContent';

                        $response = $this->firstSuccessfulResponse(function ($access_server) use ($storage, $volid) {
                            $client = new puqProxmoxClient($access_server->toArray());

                            return $client->deleteLxcTemplateStorageContent(
                                $storage->puqPmNode->name,
                                $storage->name,
                                $volid
                            );
                        }, $command);
                    }
                }
            }
        }

        return 'success';
    }

    public function vncwebproxyTestConnection(): array
    {
        try {
            // Check Proxmox node
            $puq_pm_node = $this->puqPmNodes()->where('status', 'online')->first();
            if (!$puq_pm_node) {
                return ['status' => 'error', 'errors' => ['Proxmox node not found or offline']];
            }

            // Check Access Server
            $access_server = $this->puqPmAccessServers()->first();
            if (!$access_server) {
                return ['status' => 'error', 'errors' => ['Access server not configured']];
            }

            $api_host = $access_server->api_host ?? null;
            $api_port = $access_server->api_port ?? null;
            $ssh_username = $access_server->ssh_username ?? null;
            $ssh_password_encrypted = $access_server->ssh_password ?? null;

            if (!$api_host || !$api_port) {
                return ['status' => 'error', 'errors' => ['API host or port missing']];
            }

            if (!$ssh_username || !$ssh_password_encrypted) {
                return ['status' => 'error', 'errors' => ['SSH credentials missing']];
            }

            if ($ssh_username !== 'root') {
                return ['status' => 'error', 'errors' => ['SSH username must be root']];
            }

            try {
                $ssh_password = Crypt::decryptString($ssh_password_encrypted);
            } catch (\Exception $e) {
                return ['status' => 'error', 'errors' => ['Invalid SSH password decryption']];
            }

            // Authenticate with Proxmox
            $response = Http::withOptions(['verify' => false])
                ->asForm()
                ->post("https://{$api_host}:{$api_port}/api2/json/access/ticket", [
                    'username' => "$ssh_username@pam",
                    'password' => $ssh_password,
                ]);

            if (!$response->successful()) {
                return ['status' => 'error', 'errors' => ['Failed to authenticate with Proxmox']];
            }

            $data = $response->json();
            if (!isset($data['data']['ticket'], $data['data']['CSRFPreventionToken'])) {
                return ['status' => 'error', 'errors' => ['Invalid authentication response from Proxmox']];
            }

            $ticket = $data['data']['ticket'];
            $csrfToken = $data['data']['CSRFPreventionToken'];

            // Request VNC shell
            $postData = ['node' => $puq_pm_node->name, 'websocket' => 1];

            $vncproxy_q = Http::withOptions(['verify' => false])
                ->withHeaders([
                    'Cookie' => "PVEAuthCookie=$ticket",
                    'CSRFPreventionToken' => $csrfToken,
                ])
                ->post("https://{$api_host}:{$api_port}/api2/json/nodes/{$puq_pm_node->name}/vncshell", $postData);

            if (!$vncproxy_q->successful()) {
                return ['status' => 'error', 'errors' => ['Failed to create VNC proxy session']];
            }

            $vncproxy = $vncproxy_q->json();
            if (!isset($vncproxy['data']['port'], $vncproxy['data']['ticket'])) {
                return ['status' => 'error', 'errors' => ['Invalid response from Proxmox VNC proxy']];
            }

            $data = "wss://{$api_host}:{$api_port}/api2/json/nodes/{$puq_pm_node->name}/vncwebsocket"
                .'?port='.$vncproxy['data']['port']
                .'&vncticket='.rawurlencode($vncproxy['data']['ticket']);

            $hash = md5($vncproxy['data']['ticket']);
            $vncwebproxy_url = "https://{$this->vncwebproxy_domain}/api/proxy";

            // Register proxy session
            $response = Http::withHeaders([
                'X-API-Key' => $this->vncwebproxy_api_key,
            ])->post($vncwebproxy_url, [
                'hash' => $hash,
                'proxmox_ws_url' => $data,
                'cookie' => $ticket,
                'csrfp_revention_token' => $csrfToken,
            ]);

            if (!$response->successful()) {
                $errors = $response->json('errors') ?? [];
                $errors[] = 'Failed to connect to VNC Web Proxy';

                return ['status' => 'error', 'errors' => $errors];
            }

            // Final VNC URL
            $url = "https://{$this->vncwebproxy_domain}/vnc.html?autoconnect=1"
                ."&password=".rawurlencode($vncproxy['data']['ticket'])
                ."&encrypt=1&resize=scale&path=vncproxy/{$hash}";

            return ['status' => 'success', 'data' => $url];

        } catch (\Throwable $e) {
            return [
                'status' => 'error',
                'errors' => ['Unexpected error: '.$e->getMessage()],
            ];
        }
    }

    // LXC actions

    public function createLxc($data): array
    {
        if (empty($data['vmid'])) {
            $get_vmid = $this->getFreeVmid();
            if ($get_vmid['status'] == 'error') {
                return $get_vmid;
            }
            $data['vmid'] = $get_vmid['data'];
        }

        $command = 'postLxc';
        $response = $this->firstSuccessfulResponse(function ($access_server) use ($data) {
            $client = new puqProxmoxClient($access_server->toArray(), 10);

            return $client->postLxc($data);
        }, $command);

        if ($response['status'] == 'error') {
            return $response;
        }

        return [
            'status' => 'success',
            'data' => [
                'upid' => $response['data'],
                'vmid' => $data['vmid'],
            ],
        ];

    }

    public function deleteLxc($vmid): array
    {
        $lxc_data = $this->getLxcResources($vmid);

        if ($lxc_data['status'] == 'error') {
            return $lxc_data;
        }
        $lxc_data = $lxc_data['data'];

        if ($lxc_data['status'] == 'running') {
            $stop = $this->stopLxc($lxc_data['node'], $vmid);
            if ($stop['status'] == 'error') {
                return $stop;
            }
        }

        $command = 'deleteLxc';
        $delete = $this->firstSuccessfulResponse(function ($access_server) use ($lxc_data, $vmid) {
            $client = new puqProxmoxClient($access_server->toArray(), 20);
            $data = ['node' => $lxc_data['node'], 'vmid' => $vmid];

            return $client->deleteLxc($data);
        }, $command);

        if ($delete['status'] != 'success') {
            return $delete;
        }

        return $this->waitForTask($delete['data'], 10);
    }


    public function getLxcFirewallOptions(array $data): array
    {
        $command = 'getLxcFirewallOptions';
        $restore = $this->firstSuccessfulResponse(function ($access_server) use ($data) {
            $client = new puqProxmoxClient($access_server->toArray(), 20);

            return $client->getLxcFirewallOptions($data);
        }, $command);

        return $restore;
    }

    public function setLxcFirewallOptions(array $data): array
    {
        $command = 'putLxcFirewallOptions';
        $restore = $this->firstSuccessfulResponse(function ($access_server) use ($data) {
            $client = new puqProxmoxClient($access_server->toArray(), 20);

            return $client->putLxcFirewallOptions($data);
        }, $command);

        return $restore;
    }

    public function getLxcFirewallRules(array $data): array
    {
        $command = 'getLxcFirewallRules';
        $restore = $this->firstSuccessfulResponse(function ($access_server) use ($data) {
            $client = new puqProxmoxClient($access_server->toArray(), 20);

            return $client->getLxcFirewallRules($data);
        }, $command);

        return $restore;
    }

    public function setLxcFirewallRuleUpdate(array $data): array
    {
        $command = 'putLxcFirewallRuleUpdate';
        $restore = $this->firstSuccessfulResponse(function ($access_server) use ($data) {
            $client = new puqProxmoxClient($access_server->toArray(), 20);

            return $client->putLxcFirewallRuleUpdate($data);
        }, $command);

        return $restore;
    }

    public function setLxcFirewallRuleDelete(array $data): array
    {
        $command = 'deleteLxcFirewallRuleDelete';
        $restore = $this->firstSuccessfulResponse(function ($access_server) use ($data) {
            $client = new puqProxmoxClient($access_server->toArray(), 20);

            return $client->deleteLxcFirewallRuleDelete($data);
        }, $command);

        return $restore;
    }

    public function createLxcFirewallRule(array $data): array
    {
        $command = 'createLxcFirewallRule';
        $restore = $this->firstSuccessfulResponse(function ($access_server) use ($data) {
            $client = new puqProxmoxClient($access_server->toArray(), 20);

            return $client->createLxcFirewallRule($data);
        }, $command);

        return $restore;
    }


    public function startLxc(string $node, int $vmid): array
    {
        $command = 'startLxc';
        $response = $this->firstSuccessfulResponse(function ($access_server) use ($node, $vmid) {
            $client = new puqProxmoxClient($access_server->toArray());
            $data = ['node' => $node, 'vmid' => $vmid];

            return $client->startLxc($data);
        }, $command);

        if ($response['status'] == 'error') {
            return $response;
        }

        return $this->waitForTask($response['data'], 20);
    }

    public function stopLxc(string $node, int $vmid): array
    {
        $command = 'stopLxc';
        $response = $this->firstSuccessfulResponse(function ($access_server) use ($node, $vmid) {
            $client = new puqProxmoxClient($access_server->toArray());
            $data = ['node' => $node, 'vmid' => $vmid];

            return $client->stopLxc($data);
        }, $command);

        if ($response['status'] == 'error') {
            return $response;
        }

        return $this->waitForTask($response['data'], 20);
    }

    public function consoleLxc(string $node, int $vmid): array
    {
        $command = 'vncproxyLxc';
        $vncproxy = $this->firstSuccessfulResponse(function ($access_server) use ($node, $vmid) {
            $client = new puqProxmoxClient($access_server->toArray());
            $data = [
                'node' => $node,
                'vmid' => $vmid,
                'websocket' => 1,
            ];

            return [
                'status' => 'success',
                'data' => [
                    'vncproxy' => $client->vncproxyLxc($data),
                    'server' => $access_server,
                ],
            ];

        }, $command);

        $server = $vncproxy['data']['server'];
        $vncproxy = $vncproxy['data']['vncproxy'];

        if ($vncproxy['status'] == 'error') {
            return (array) $vncproxy;
        }

        $data = 'wss://'.$server['api_host'].':'.$server['api_port'].'/api2/json/nodes/'
            .$node.'/lxc/'.$vmid.'/vncwebsocket'
            .'?port='.$vncproxy['data']['port']
            .'&vncticket='.rawurlencode($vncproxy['data']['ticket']);

        $api_token_id = $server->api_token_id;
        $api_token = Crypt::decryptString($server->api_token);

        $hash = md5($vncproxy['data']['ticket']);
        $vncwebproxy_url = 'https://'.$this->vncwebproxy_domain.'/api/proxy';
        $response = Http::withHeaders([
            'X-API-Key' => 'QWEqwe123',
        ])->post($vncwebproxy_url, [
            'hash' => $hash,
            'proxmox_token' => $api_token_id.'='.$api_token,
            'proxmox_ws_url' => $data,
        ]);

        if (!$response->successful()) {
            $errors = $response->json('errors') ?? [];
            $errors[] = 'Vncwebproxy connect problem';

            return [
                'status' => 'error',
                'errors' => $errors,
            ];
        }

        $url = 'https://'.$this->vncwebproxy_domain.'/vnc.html?autoconnect=1&password='.rawurlencode($vncproxy['data']['ticket'])."&encrypt=1&resize=scale&path=vncproxy/$hash";

        return [
            'status' => 'success',
            'data' => $url,
        ];
    }

    public function getLxcConfig(string $node, int $vmid): array
    {
        $command = 'getLxcConfig';
        $response = $this->firstSuccessfulResponse(function ($access_server) use ($node, $vmid) {
            $client = new puqProxmoxClient($access_server->toArray());
            $data = ['node' => $node, 'vmid' => $vmid];

            return $client->getLxcConfig($data);
        }, $command);

        return $response;
    }

    public function deleteLxcConfig(string $node, int $vmid, string $keys): array
    {
        $command = 'deleteLxcConfig';
        $response = $this->firstSuccessfulResponse(function ($access_server) use ($node, $vmid, $keys) {
            $client = new puqProxmoxClient($access_server->toArray());
            $data = [
                'node' => $node,
                'vmid' => $vmid,
                'delete' => $keys,
            ];

            return $client->deleteLxcConfig($data);
        }, $command);

        return $response;
    }

    public function setLxcConfig(string $node, int $vmid, array $data, $ssh = false): array
    {
        $command = 'setLxcConfig';
        $response = $this->firstSuccessfulResponse(function ($access_server) use ($node, $vmid, $data, $ssh) {

            $client = new puqProxmoxClient($access_server->toArray());
            $data['node'] = $node;
            $data['vmid'] = $vmid;

            return $client->putLxcConfig($data, $ssh);
        }, $command);

        return $response;
    }

    public function resizeLxcDisk(string $node, int $vmid, array $data): array
    {
        $command = 'resizeLxcDisk';
        $response = $this->firstSuccessfulResponse(function ($access_server) use ($node, $vmid, $data) {

            $client = new puqProxmoxClient($access_server->toArray());
            $data['node'] = $node;
            $data['vmid'] = $vmid;

            return $client->resizeLxcDisk($data);
        }, $command);


        return $this->waitForTask($response['data'], 100, 1);
    }

    public function getLxcResources($vmid): array
    {
        $command = 'getClusterResources';
        $response = $this->firstSuccessfulResponse(function ($access_server) {
            $client = new puqProxmoxClient($access_server->toArray(), 20);

            return $client->getClusterResources();
        }, $command);

        if ($response['status'] != 'success') {
            return $response;
        }

        $lxc_data = [];
        foreach ((array) $response['data'] as $item) {
            if (empty($item['type'] or $item['type'] !== 'lxc')) {
                continue;
            }
            if (empty($item['vmid']) or $item['vmid'] !== $vmid) {
                continue;
            }
            if ($item['vmid'] === $vmid) {
                $lxc_data = $item;
                break;
            }
        }

        if (empty($lxc_data)) {
            return ['status' => 'error', 'errors' => ['LXC not found']];
        }

        return ['status' => 'success', 'data' => $lxc_data];
    }

    public function getXlcRrdData(string $node, int $vmid, string $timeframe): array
    {
        $command = 'getXlcRrdData';
        $response = $this->firstSuccessfulResponse(function ($access_server) use ($node, $vmid, $timeframe) {
            $client = new puqProxmoxClient($access_server->toArray(), 20);

            $data = [
                'node' => $node,
                'vmid' => $vmid,
                'timeframe' => $timeframe,
            ];

            return $client->getXlcRrdData($data);
        }, $command);

        return $response;
    }

    public function getBackups(string $node, int $vmid, string $storage): array
    {
        $command = 'getBackups';
        $response = $this->firstSuccessfulResponse(function ($access_server) use ($node, $vmid, $storage) {
            $client = new puqProxmoxClient($access_server->toArray(), 20);

            $data = [
                'node' => $node,
                'vmid' => $vmid,
                'storage' => $storage,
            ];

            return $client->getBackups($data);
        }, $command);

        return $response;

    }

    public function backupNow(
        string $note,
        string $node,
        int $vmid,
        string $storage,
        string $mode,
        string $compress,
        string $bwlimit
    ): array {
        $command = 'backupNow';
        $response = $this->firstSuccessfulResponse(function ($access_server) use (
            $note,
            $node,
            $vmid,
            $storage,
            $mode,
            $compress,
            $bwlimit
        ) {
            $client = new puqProxmoxClient($access_server->toArray(), 20);

            $data = [
                'node' => $node,
                'vmid' => $vmid,
                'storage' => $storage,
                'mode' => $mode,
                'notes-template' => $note,
                'bwlimit' => $bwlimit,
                'compress' => $compress,
            ];

            return $client->backupNow($data);
        }, $command);

        return $response;
    }

    public function deleteFile(string $node, string $storage, string $volid): array
    {
        $command = 'deleteFile';
        $delete = $this->firstSuccessfulResponse(function ($access_server) use ($node, $storage, $volid) {
            $client = new puqProxmoxClient($access_server->toArray(), 20);

            $data = [
                'node' => $node,
                'storage' => $storage,
                'volid' => $volid,
            ];

            return $client->deleteFile($data);
        }, $command);

        if ($delete['status'] != 'success') {
            return $delete;
        }

        return $this->waitForTask($delete['data'], 20, 2);
    }

    public function restoreLxcBackup(array $data): array
    {
        $command = 'restoreBackup';
        $restore = $this->firstSuccessfulResponse(function ($access_server) use ($data) {
            $client = new puqProxmoxClient($access_server->toArray(), 20);
            $data['force'] = true;
            $data['restore'] = true;
            $data['start'] = true;

            return $client->restoreLxcBackup($data);
        }, $command);

        return $restore;
    }


    public function getFreeVmid(): array
    {
        $command = 'getClusterResources';
        $response = $this->firstSuccessfulResponse(function ($access_server) {
            $client = new puqProxmoxClient($access_server->toArray(), 20);

            return $client->getClusterResources();
        }, $command);

        if ($response['status'] != 'success') {
            return $response;
        }

        $cluster_vmids = [];
        foreach ((array) $response['data'] as $resource) {
            if (!empty($resource['vmid'])) {
                $cluster_vmids[] = (int) $resource['vmid'];
            }
        }

        sort($cluster_vmids);

        $minVmid = 100;
        $maxVmid = 999999999;
        $tries = 0;
        $maxTries = $maxVmid - $minVmid;

        while ($tries < $maxTries) {
            $vmid = (int) (microtime(true) * 1000) % ($maxVmid - $minVmid + 1) + $minVmid;
            $vmid += random_int(0, 50);

            $existsInDb = PuqPmLxcInstance::where('vmid', $vmid)->exists();
            $existsInCluster = in_array($vmid, $cluster_vmids);

            if (!$existsInDb && !$existsInCluster) {
                return [
                    'status' => 'success',
                    'data' => $vmid,
                ];
            }

            $tries++;
        }

        return ['status' => 'error', 'errors' => ['Vmid not found']];
    }


    public function runSshScriptOnLxc($puq_pm_lxc_instance, $script, $puq_pm_script = null): array
    {
        $vmid = $puq_pm_lxc_instance->vmid;
        $lxc_data = $this->getLxcResources($vmid);

        if ($lxc_data['status'] == 'error') {
            return $lxc_data;
        }

        $lxc_data = $lxc_data['data'];

        if ($lxc_data['status'] !== 'running') {
            return [
                'status' => 'error',
                'errors' => ["LXC ID:{$vmid} not running"],
            ];
        }

        $command = 'getClusterStatus';
        $cluster_status = $this->firstSuccessfulResponse(function ($access_server) {
            $client = new puqProxmoxClient($access_server->toArray(), 20);

            return $client->getClusterStatus();
        }, $command);

        if ($cluster_status['status'] != 'success') {
            return $cluster_status;
        }

        $cluster_status = $cluster_status['data'];

        // looking for target node IP
        $node_ip = '';
        foreach ((array) $cluster_status as $item) {
            if ($item['type'] !== 'node') {
                continue;
            }
            if ($lxc_data['node'] !== $item['name']) {
                continue;
            }
            $node_ip = $item['ip'];
            break;
        }
        if (empty($node_ip)) {
            return [
                'status' => 'error',
                'errors' => ['Node IP not found'],
            ];
        }

        $encodedScript = base64_encode($script);
        $pct_script = "pct exec {$vmid} -- bash -c 'echo {$encodedScript} | base64 -d | bash'";

        $command = 'executeSSH';
        $execute = $this->firstSuccessfulResponse(function ($access_server) use ($pct_script, $node_ip) {
            $client = new puqProxmoxClient($access_server->toArray(), 600);

            return $client->executeSSH($pct_script, $node_ip);
        }, $command);

        if (!empty($puq_pm_script)) {
            $puq_pm_script_log = new PuqPmScriptLog();
            $puq_pm_script_log->puq_pm_script_uuid = $puq_pm_script->uuid ?? null;
            $puq_pm_script_log->input = $script;
            $puq_pm_script_log->output = $execute['data'] ?? null;
            $puq_pm_script_log->status = $execute['status'] ?? null;
            $puq_pm_script_log->errors = $execute['errors'] ?? [];
            $puq_pm_script_log->duration = (int) $execute['duration'] ?? 0;
            $puq_pm_script_log->model = $puq_pm_lxc_instance::class;
            $puq_pm_script_log->model_uuid = $puq_pm_lxc_instance->uuid;
            $puq_pm_script_log->save();
        }

        return $execute;
    }

    public function waitForTask(string $upid, int $retries = 10, int $delay = 1): array
    {
        while ($retries > 0) {
            $command = 'getTaskStatus';
            $status = $this->firstSuccessfulResponse(function ($access_server) use ($upid) {
                $client = new puqProxmoxClient($access_server->toArray());

                return $client->getTaskStatus($upid);
            }, $command);

            if ($status['status'] !== 'error' && isset($status['data']['status']) && $status['data']['status'] === 'stopped') {
                if (isset($status['data']['exitstatus']) && $status['data']['exitstatus'] === 'OK') {
                    return ['status' => 'success', 'data' => $status['data']];
                }

                return ['status' => 'error', 'errors' => $status['data']];
            }

            $retries--;
            sleep($delay);
        }

        return ['status' => 'error', 'errors' => ['Task did not finish within retries']];
    }

}
