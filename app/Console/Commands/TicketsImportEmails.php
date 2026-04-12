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

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\File;
use App\Models\Ticket;
use App\Models\TicketDepartment;
use App\Models\TicketEmailBlacklist;
use App\Models\TicketReply;
use App\Models\TicketStatus;
use App\Models\User;
use App\Services\HookService;
use App\Services\SettingService;
use App\Services\TranslationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TicketsImportEmails extends Command
{
    protected $signature = 'Tickets:ImportEmails';
    protected $description = 'Import emails from configured department mailboxes and create/update tickets';

    public function __construct()
    {
        parent::__construct();
        App::setLocale(config('locale.admin.default'));
        TranslationService::init('admin');
    }

    public function handle(): void
    {
        $lock = Cache::lock('tickets:import-emails', 3600);
        if (! $lock->get()) {
            return;
        }

        try {
            $departments = TicketDepartment::where('mail_import_enabled', true)->get();
            $totalImported = 0;
            $departmentsProcessed = 0;

            foreach ($departments as $department) {
                try {
                    $imported = $this->processDepartment($department);
                    $totalImported += $imported;
                    $departmentsProcessed++;
                } catch (\Throwable $e) {
                    logActivity(
                        'error',
                        'Tickets:ImportEmails: failed to process department "'.$department->name.'" - '.$e->getMessage(),
                        'cron'
                    );
                }
            }

            if ($totalImported > 0) {
                logActivity(
                    'info',
                    'Tickets:ImportEmails completed. Imported '.$totalImported.' emails from '.$departmentsProcessed.' departments',
                    'cron'
                );
            }
        } finally {
            $lock->release();
        }
    }

    private function processDepartment(TicketDepartment $department): int
    {
        $encryption = $department->mail_import_encryption;
        if ($encryption === 'none' || ! $encryption) {
            $encryption = false;
        }

        $config = [
            'host' => $department->mail_import_host,
            'port' => $department->mail_import_port,
            'encryption' => $encryption,
            'validate_cert' => false,
            'username' => $department->mail_import_username,
            'password' => $department->mail_import_password,
            'protocol' => $department->mail_import_type ?: 'imap',
        ];

        $logContext = ['host' => $config['host'], 'port' => $config['port'], 'protocol' => $config['protocol'], 'department' => $department->name];

        try {
            $client = (new \Webklex\PHPIMAP\ClientManager())->make($config);
            $client->connect();
        } catch (\Throwable $e) {
            logModule('TicketMailImport', $department->name, 'connect', 'error', $logContext, ['error' => $e->getMessage()]);
            throw $e;
        }

        logModule('TicketMailImport', $department->name, 'connect', 'info', $logContext, ['status' => 'connected']);

        $imported = 0;

        try {
            $folder = $client->getFolder('INBOX');
            if (! $folder) {
                logModule('TicketMailImport', $department->name, 'import', 'error', $logContext, ['error' => 'INBOX folder not found']);

                return 0;
            }

            $messages = $folder->query()->unseen()->get();

            logModule('TicketMailImport', $department->name, 'import', 'info', $logContext, ['unseen_messages' => count($messages)]);

            foreach ($messages as $message) {
                try {
                    $this->processMessage($message, $department);
                    $imported++;

                    $message->setFlag('Seen');

                    if ($department->mail_import_delete_after) {
                        $message->delete(expunge: true);
                    }
                } catch (\Throwable $e) {
                    logModule('TicketMailImport', $department->name, 'process_message', 'error', $logContext,
                        ['error' => $e->getMessage(), 'subject' => (string) $message->getSubject()]
                    );
                    logActivity(
                        'error',
                        'Tickets:ImportEmails: failed to process message in "'.$department->name.'" - '.$e->getMessage(),
                        'cron'
                    );
                }
            }
        } finally {
            $client->disconnect();
        }

        if ($imported > 0) {
            logModule('TicketMailImport', $department->name, 'import', 'info', $logContext, ['imported' => $imported]);
        }

        return $imported;
    }

    private function processMessage($message, TicketDepartment $department): void
    {
        $subject = (string) $message->getSubject();
        $fromEmail = (string) $message->getFrom()[0]->mail;
        $fromName = (string) ($message->getFrom()[0]->personal ?? $fromEmail);
        $date = $message->getDate()?->toDate();

        if (TicketEmailBlacklist::isBlacklisted($fromEmail)) {
            return;
        }

        if ($message->hasTextBody()) {
            $body = (string) $message->getTextBody();
        } elseif ($message->hasHTMLBody()) {
            $body = (string) $message->getHTMLBody();
            $body = preg_replace('/<style[^>]*>.*?<\/style>/is', '', $body);
            $body = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $body);
            $body = preg_replace('/<head[^>]*>.*?<\/head>/is', '', $body);
            $body = strip_tags($body);
        } else {
            $body = '';
        }

        // Normalize whitespace: collapse multiple blank lines into one
        $body = preg_replace('/\r\n|\r/', "\n", $body);
        $body = preg_replace('/[^\S\n]+/', ' ', $body);
        $body = preg_replace('/\n{2,}/', "\n", $body);
        $body = trim($body);
        $body = nl2br(e($body));

        // Try to find client by email
        $user = User::where('email', $fromEmail)->first();
        $clientUuid = null;
        $userUuid = null;

        if ($user) {
            $userUuid = $user->uuid;
            $client = DB::table('client_x_user')
                ->where('user_uuid', $user->uuid)
                ->first();
            if ($client) {
                $clientUuid = $client->client_uuid;
            }
        }

        // Skip unknown senders if department requires clients only
        if ($department->mail_import_clients_only && ! $clientUuid) {
            return;
        }

        // Check if this is a reply to an existing ticket
        if (preg_match('/\[Ticket #(\d+)\]/', $subject, $matches)) {
            $ticketNumber = (int) $matches[1];
            $ticket = Ticket::where('number', $ticketNumber)->first();

            if ($ticket) {
                $reply = $this->createReply($ticket, $body, $fromEmail, $fromName, $userUuid, $message);
                $this->updateTicketStatusForReply($ticket);
                $ticket->load(['department', 'client', 'status']);
                app(HookService::class)->callHooks('TicketRepliedByClient', ['ticket' => $ticket, 'reply' => $reply]);

                return;
            }
        }

        // Skip new ticket creation if department only allows replies
        if ($department->mail_import_replies_only) {
            return;
        }

        // Create new ticket
        $openStatus = TicketStatus::where('title', 'Open')->first();

        $ticket = Ticket::create([
            'subject' => $subject ?: 'No Subject',
            'department_uuid' => $department->uuid,
            'status_uuid' => $openStatus?->uuid,
            'priority' => 'medium',
            'client_uuid' => $clientUuid,
            'user_uuid' => $userUuid,
            'email' => $clientUuid ? null : $fromEmail,
            'name' => $clientUuid ? null : $fromName,
            'last_reply_at' => $date ?? now(),
        ]);

        $reply = $this->createReply($ticket, $body, $fromEmail, $fromName, $userUuid, $message);

        $ticket->load(['department', 'client', 'status']);
        app(HookService::class)->callHooks('TicketCreated', ['ticket' => $ticket]);
    }

    private function createReply(Ticket $ticket, string $body, string $email, string $name, ?string $userUuid, $message): TicketReply
    {
        $reply = TicketReply::create([
            'ticket_uuid' => $ticket->uuid,
            'user_uuid' => $userUuid,
            'name' => $name,
            'email' => $email,
            'message' => $body,
            'is_admin_note' => false,
        ]);

        $ticket->update(['last_reply_at' => now()]);

        // Handle attachments with department/global limits
        $department = $ticket->department;

        $allowedExtensions = $department?->allowed_file_extensions
            ?: SettingService::get('support.ticket_allowed_file_extensions', 'jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,txt,zip,rar');
        $allowedExtensions = array_map('trim', array_map('strtolower', explode(',', $allowedExtensions)));

        $maxSizeMb = $department?->max_file_size_mb
            ?: (int) SettingService::get('support.ticket_max_file_size_mb', 10);

        $maxCount = $department?->max_files_count
            ?: (int) SettingService::get('support.ticket_max_attachments_per_reply', 5);

        $attachments = $message->getAttachments();
        $imported = 0;

        foreach ($attachments as $attachment) {
            if ($imported >= $maxCount) {
                break;
            }

            try {
                $fileName = $attachment->getName() ?: 'attachment';
                $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                $content = $attachment->getContent();
                $sizeMb = strlen($content) / 1048576;

                if (! empty($extension) && ! in_array($extension, $allowedExtensions)) {
                    continue;
                }

                if ($sizeMb > $maxSizeMb) {
                    continue;
                }

                $file = new File();
                $file->type = $attachment->getMimeType();
                $file->model_type = TicketReply::class;
                $file->model_uuid = $reply->uuid;
                $file->model_field = 'attachment';
                $file->saveContent(
                    'tickets/'.$ticket->uuid,
                    $fileName,
                    $content
                );

                $imported++;
            } catch (\Throwable $e) {
                logActivity(
                    'error',
                    'Tickets:ImportEmails: failed to save attachment for ticket #'.$ticket->number.' - '.$e->getMessage(),
                    'cron'
                );
            }
        }

        return $reply;
    }

    private function updateTicketStatusForReply(Ticket $ticket): void
    {
        $customerReplyStatus = TicketStatus::where('title', 'Customer-Reply')->first();
        if ($customerReplyStatus) {
            $ticket->update(['status_uuid' => $customerReplyStatus->uuid]);
        }
    }
}
