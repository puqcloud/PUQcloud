<?php

/*
 * PUQcloud - Free Cloud Billing System
 * Main billing system core logic
 *
 * Copyright (C) 2025-2026 PUQ Software
 * Licensed under GNU GPLv3
 * https://www.gnu.org/licenses/gpl-3.0.html
 *
 * Author: Ruslan Polovyi <ruslan@polovyi.com>
 * Website: https://puqcloud.com
 * E-mail: support@puqcloud.com
 *
 * Do not remove this header.
 */

use App\Http\Controllers\Admin\AdminAddOnsController;
use App\Http\Controllers\Admin\AdminAdminApiKeysController;
use App\Http\Controllers\Admin\AdminAdminsController;
use App\Http\Controllers\Admin\AdminApiDocumentationController;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminAutomationController;
use App\Http\Controllers\Admin\AdminClientActivityController;
use App\Http\Controllers\Admin\AdminClientAddressesController;
use App\Http\Controllers\Admin\AdminClientFinanceController;
use App\Http\Controllers\Admin\AdminClientsController;
use App\Http\Controllers\Admin\AdminClientUsersController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AdminDnsManagerController;
use App\Http\Controllers\Admin\AdminHomeCompaniesController;
use App\Http\Controllers\Admin\AdminInvoicesController;
use App\Http\Controllers\Admin\AdminPaymentGatewaysController;
use App\Http\Controllers\Admin\AdminTaxRulesController;
use App\Http\Controllers\Admin\AdminTicketDepartmentsController;
use App\Http\Controllers\Admin\AdminTicketsController;
use App\Http\Controllers\Admin\AdminTicketStatusesController;
use App\Http\Controllers\Admin\AdminTransactionsController;
use App\Http\Controllers\Admin\AdminGroupsController;
use App\Http\Controllers\Admin\AdminLogController;
use App\Http\Controllers\Admin\AdminMyAccountController;
use App\Http\Controllers\Admin\AdminNotificationsController;
use App\Http\Controllers\Admin\AdminProductAttributesController;
use App\Http\Controllers\Admin\AdminProductGroupsController;
use App\Http\Controllers\Admin\AdminProductOptionsController;
use App\Http\Controllers\Admin\AdminProductsController;
use App\Http\Controllers\Admin\AdminServicesController;
use App\Http\Controllers\Admin\AdminSettingsController;
use App\Http\Controllers\Admin\AdminSslManagerController;
use App\Http\Controllers\Admin\AdminTaskController;
use App\Http\Controllers\Admin\AdminUsersController;
use App\Http\Controllers\Admin\AdminWidgetsController;
use App\Http\Controllers\FileController;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

Route::get('logout', [AdminAuthController::class, 'logout'])->name('logout');

Route::get('my_account', [AdminMyAccountController::class, 'getMyAccount'])->name('my_account.get');
Route::put('my_account', [AdminMyAccountController::class, 'putMyAccount'])->name('my_account.put');

Route::get('my_account/bell_notification',
    [AdminMyAccountController::class, 'getBellNotification'])->name('my_account.bell_notification.get');
Route::get('my_account/bell_notification/mark_read', [
    AdminMyAccountController::class, 'getBellNotificationMarkRead',
])->name('my_account.bell_notification.mark_read.get');
Route::get('my_account/bell_notification/mark_all_read', [
    AdminMyAccountController::class, 'getBellNotificationMarkAllRead',
])->name('my_account.bell_notification.mark_all_read.get');

Route::get('my_account/notifications',
    [AdminMyAccountController::class, 'getNotifications'])->name('my_account.notifications.get');
Route::get('my_account/notification/{uuid}',
    [AdminMyAccountController::class, 'getNotification'])->name('my_account.notification.get');

Route::get('languages/select', [AdminController::class, 'getLanguagesSelect'])->name('languages.select.get');

// admins --------------------------------------------------------------------------------------------------------------
Route::get('admins', [AdminAdminsController::class, 'getAdmins'])->name('admins.get')
    ->middleware('ApiPermission:admins-view');
Route::get('admins/select', [AdminAdminsController::class, 'getAdminsSelect'])->name('admins.select.get')
    ->middleware('ApiPermission:admins-view');

Route::get('admin/{uuid}', [AdminAdminsController::class, 'getAdmin'])->name('admin.get')
    ->middleware('ApiPermission:admins-view');
Route::post('admin', [AdminAdminsController::class, 'postAdmin'])->name('admin.post')
    ->middleware('ApiPermission:admins-create');
Route::put('admin/{uuid}', [AdminAdminsController::class, 'putAdmin'])->name('admin.put')
    ->middleware('ApiPermission:admins-edit');
Route::delete('admin/{uuid}', [AdminAdminsController::class, 'deleteAdmin'])->name('admin.delete')
    ->middleware('ApiPermission:admins-delete');

// monitoring ----------------------------------------------------------------------------------------------------------
Route::get('admin_session_logs', [AdminLogController::class, 'getAdminSessions'])->name('admin_session_logs.get')
    ->middleware('ApiPermission:admin-session-log-view');
Route::get('client_session_logs', [AdminLogController::class, 'getClientSessions'])->name('client_session_logs.get')
    ->middleware('ApiPermission:client-session-log-view');
Route::get('activity_logs', [AdminLogController::class, 'getActivityLogs'])->name('activity_logs.get')
    ->middleware('ApiPermission:activity-log-view');
Route::get('activity_log/{uuid}', [AdminLogController::class, 'getActivityLog'])->name('activity_log.get')
    ->middleware('ApiPermission:activity-log-view');

Route::get('module_logs', [AdminLogController::class, 'getModuleLogs'])->name('module_logs.get')
    ->middleware('ApiPermission:module-log-view');

Route::delete('module_logs/delete_all', [AdminLogController::class, 'deleteModuleLogsDeleteAll'])->name('module_logs.delete_all.delete')
    ->middleware('ApiPermission:module-log-view');
Route::get('module_log/{uuid}', [AdminLogController::class, 'getModuleLog'])->name('module_log.get')
    ->middleware('ApiPermission:module-log-view');

Route::get('tasks', [AdminTaskController::class, 'getTasks'])->name('tasks.get')
    ->middleware('ApiPermission:task-queue-view');

Route::get('task/{uuid}', [AdminTaskController::class, 'getTask'])->name('task.get')
    ->middleware('ApiPermission:task-queue-view');

// groups --------------------------------------------------------------------------------------------------------------
Route::get('groups', [AdminGroupsController::class, 'getGroups'])->name('groups.get')
    ->middleware('ApiPermission:groups-view');
Route::get('groups/select', [AdminGroupsController::class, 'getGroupsSelect'])->name('groups.select.get')
    ->middleware('ApiPermission:groups-view');
Route::get('group/{uuid}', [AdminGroupsController::class, 'getGroup'])->name('group.get')
    ->middleware('ApiPermission:groups-view');
Route::post('group', [AdminGroupsController::class, 'postGroup'])->name('group.post')
    ->middleware('ApiPermission:groups-create');
Route::put('group/{uuid}', [AdminGroupsController::class, 'putGroup'])->name('group.put')
    ->middleware('ApiPermission:groups-edit');
Route::delete('group/{uuid}', [AdminGroupsController::class, 'deleteGroup'])->name('group.delete')
    ->middleware('ApiPermission:groups-delete');
Route::get('group/{uuid}/notification_rules',
    [AdminGroupsController::class, 'getGroupNotificationRules'])->name('group.notification_rules.get')
    ->middleware('ApiPermission:groups-view');
Route::get('group_types/select', [AdminGroupsController::class, 'getGroupTypesSelect'])->name('group_types.select.get')
    ->middleware('ApiPermission:groups-view');

Route::get('scheduler', [AdminAutomationController::class, 'getScheduler'])->name('scheduler.get')
    ->middleware('WebPermission:automation-scheduler-view');
Route::put('schedule/{uuid}', [AdminAutomationController::class, 'putSchedule'])->name('schedule.put')
    ->middleware('WebPermission:automation-scheduler-edit');

Route::get('dashboard/widgets', [AdminWidgetsController::class, 'getDashboardWidgets'])->name('dashboard.widgets.get');
Route::get('dashboard/widget', [AdminWidgetsController::class, 'getDashboardWidget'])->name('dashboard.widget.get');

Route::get('dashboard', [AdminWidgetsController::class, 'getDashboard'])->name('dashboard.get');
Route::put('dashboard', [AdminWidgetsController::class, 'putDashboard'])->name('dashboard.put');

// admin api keys --------------------------------------------------------------------------------------------------------------
Route::get('admin_api_keys', [AdminAdminApiKeysController::class, 'getAdminApiKeys'])->name('admin_api_keys.get')
    ->middleware('ApiPermission:admin-api-keys-view');
Route::get('admin_api_key/{uuid}', [AdminAdminApiKeysController::class, 'getAdminApiKey'])->name('admin_api_key.get')
    ->middleware('ApiPermission:admin-api-keys-view');
Route::post('admin_api_key', [AdminAdminApiKeysController::class, 'postAdminApiKey'])->name('admin_api_key.post')
    ->middleware('ApiPermission:admin-api-keys-create');
Route::put('admin_api_key/{uuid}', [AdminAdminApiKeysController::class, 'putAdminApiKey'])->name('admin_api_key.put')
    ->middleware('ApiPermission:admin-api-keys-edit');
Route::delete('admin_api_key/{uuid}', [AdminAdminApiKeysController::class, 'deleteAdminApiKey'])->name('admin_api_key.delete')
    ->middleware('ApiPermission:admin-api-keys-delete');

// notification_senders ------------------------------------------------------------------------------------------------
Route::get('notification_senders',
    [AdminNotificationsController::class, 'getNotificationSenders'])->name('notification_senders.get')
    ->middleware('ApiPermission:notification-senders-management');
Route::get('notification_sender/{uuid}',
    [AdminNotificationsController::class, 'getNotificationSender'])->name('notification_sender.get')
    ->middleware('ApiPermission:notification-senders-management');
Route::post('notification_sender',
    [AdminNotificationsController::class, 'postNotificationSender'])->name('notification_sender.post')
    ->middleware('ApiPermission:notification-senders-management');
Route::put('notification_sender/{uuid}',
    [AdminNotificationsController::class, 'putNotificationSender'])->name('notification_sender.put')
    ->middleware('ApiPermission:notification-senders-management');
Route::delete('notification_sender/{uuid}',
    [AdminNotificationsController::class, 'deleteNotificationSender'])->name('notification_sender.delete')
    ->middleware('ApiPermission:notification-senders-management');
Route::get('notification_modules/select',
    [AdminNotificationsController::class, 'getNotificationModulesSelect'])->name('notification_modules.select.get')
    ->middleware('ApiPermission:notification-senders-management');

// notification_layouts ------------------------------------------------------------------------------------------------
Route::get('notification_layouts',
    [AdminNotificationsController::class, 'getNotificationLayouts'])->name('notification_layouts.get')
    ->middleware('ApiPermission:notification-layouts-management');
Route::get('notification_layout/{uuid}',
    [AdminNotificationsController::class, 'getNotificationLayout'])->name('notification_layout.get')
    ->middleware('ApiPermission:notification-layouts-management');
Route::post('notification_layout',
    [AdminNotificationsController::class, 'postNotificationLayout'])->name('notification_layout.post')
    ->middleware('ApiPermission:notification-layouts-management');
Route::put('notification_layout/{uuid}',
    [AdminNotificationsController::class, 'putNotificationLayout'])->name('notification_layout.put')
    ->middleware('ApiPermission:notification-layouts-management');
Route::delete('notification_layout/{uuid}',
    [AdminNotificationsController::class, 'deleteNotificationLayout'])->name('notification_layout.delete')
    ->middleware('ApiPermission:notification-layouts-management');

// notification_templates ----------------------------------------------------------------------------------------------
Route::get('notification_templates',
    [AdminNotificationsController::class, 'getNotificationTemplates'])->name('notification_templates.get')
    ->middleware('ApiPermission:notification-templates-management');
Route::get('notification_template/{uuid}',
    [AdminNotificationsController::class, 'getNotificationTemplate'])->name('notification_template.get')
    ->middleware('ApiPermission:notification-templates-management');
Route::post('notification_template',
    [AdminNotificationsController::class, 'postNotificationTemplate'])->name('notification_template.post')
    ->middleware('ApiPermission:notification-templates-management');
Route::put('notification_template/{uuid}',
    [AdminNotificationsController::class, 'putNotificationTemplate'])->name('notification_template.put')
    ->middleware('ApiPermission:notification-templates-management');
Route::delete('notification_template/{uuid}',
    [AdminNotificationsController::class, 'deleteNotificationTemplate'])->name('notification_template.delete')
    ->middleware('ApiPermission:notification-templates-management');

// notification_rule ---------------------------------------------------------------------------------------------------
Route::post('notification_rule',
    [AdminNotificationsController::class, 'postNotificationRule'])->name('notification_rule.post')
    ->middleware('ApiPermission:notification-rules-management');
Route::get('notification_rule/{uuid}',
    [AdminNotificationsController::class, 'getNotificationRule'])->name('notification_rule.get')
    ->middleware('ApiPermission:notification-rules-management');
Route::put('notification_rule/mass_update', [
    AdminNotificationsController::class, 'putNotificationMassUpdateRules',
])->name('notification_rule.mass_update.put')
    ->middleware('ApiPermission:notification-rules-management');
Route::put('notification_rule/{uuid}',
    [AdminNotificationsController::class, 'putNotificationRule'])->name('notification_rule.put')
    ->middleware('ApiPermission:notification-rules-management');
Route::delete('notification_rule/{uuid}',
    [AdminNotificationsController::class, 'deleteNotificationRule'])->name('notification_rule.delete')
    ->middleware('ApiPermission:notification-rules-management');
Route::post('notification_rule/mass_creation', [
    AdminNotificationsController::class, 'postNotificationMassCreationRules',
])->name('notification_rule.mass_creation.post')
    ->middleware('ApiPermission:notification-rules-management');

// notification_history ------------------------------------------------------------------------------------------------
Route::get('notification_histories',
    [AdminNotificationsController::class, 'getNotificationHistories'])->name('notification_histories.get')
    ->middleware('ApiPermission:notification-history-view');
Route::get('notification_history/{uuid}',
    [AdminNotificationsController::class, 'getNotificationHistory'])->name('notification_history.get')
    ->middleware('ApiPermission:notification-history-view');

// dns_server_groups --------------------------------------------------------------------------------------------------
Route::get('dns_server_groups', [AdminDnsManagerController::class, 'getDnsServerGroups'])->name('dns_server_groups.get')
    ->middleware('ApiPermission:dns-manager-dns-server-groups');
Route::post('dns_server_group', [AdminDnsManagerController::class, 'postDnsServerGroup'])->name('dns_server_group.post')
    ->middleware('ApiPermission:dns-manager-dns-server-groups');
Route::get('dns_server_group/{uuid}',
    [AdminDnsManagerController::class, 'getDnsServerGroup'])->name('dns_server_group.get')
    ->middleware('ApiPermission:dns-manager-dns-server-groups');
Route::put('dns_server_group/{uuid}',
    [AdminDnsManagerController::class, 'putDnsServerGroup'])->name('dns_server_group.put')
    ->middleware('ApiPermission:dns-manager-dns-server-groups');
Route::delete('dns_server_group/{uuid}',
    [AdminDnsManagerController::class, 'deleteDnsServerGroup'])->name('dns_server_group.delete')
    ->middleware('ApiPermission:dns-manager-dns-server-groups');

Route::get('dns_server_group/{uuid}/reload_all_zones', [AdminDnsManagerController::class, 'getDnsServerGroupReloadAllZones'])->name('dns_server_group.reload_all_zones.get')
    ->middleware('ApiPermission:dns-manager-dns-zones');

// dns_servers --------------------------------------------------------------------------------------------------------
Route::get('dns_servers', [AdminDnsManagerController::class, 'getDnsServers'])->name('dns_servers.get')
    ->middleware('ApiPermission:dns-manager-dns-servers');
Route::post('dns_server', [AdminDnsManagerController::class, 'postDnsServer'])->name('dns_server.post')
    ->middleware('ApiPermission:dns-manager-dns-servers');
Route::get('dns_server/{uuid}', [AdminDnsManagerController::class, 'getDnsServer'])->name('dns_server.get')
    ->middleware('ApiPermission:dns-manager-dns-servers');
Route::put('dns_server/{uuid}', [AdminDnsManagerController::class, 'putDnsServer'])->name('dns_server.put')
    ->middleware('ApiPermission:dns-manager-dns-servers');
Route::delete('dns_server/{uuid}', [AdminDnsManagerController::class, 'deleteDnsServer'])->name('dns_server.delete')
    ->middleware('ApiPermission:dns-manager-dns-servers');

Route::get('dns_server/{uuid}/dns_zones', [AdminDnsManagerController::class, 'getDnsServerDnsZones'])->name('dns_server.dns_zones.get')
    ->middleware('ApiPermission:dns-manager-dns-servers');
Route::post('dns_server/{uuid}/import_zones', [AdminDnsManagerController::class, 'postDnsServerImportZones'])->name('dns_server.import_zones.post')
    ->middleware('ApiPermission:dns-manager-dns-servers');

Route::get('dns_server/{uuid}/test_connection',
    [AdminDnsManagerController::class, 'getDnsServerTestConnection'])->name('dns_server.test_connection.get')
    ->middleware('ApiPermission:dns-manager-dns-servers');

// dns_zones --------------------------------------------------------------------------------------------------------
Route::get('dns_zones', [AdminDnsManagerController::class, 'getDnsZones'])->name('dns_zones.get')
    ->middleware('ApiPermission:dns-manager-dns-zones');
Route::post('dns_zone', [AdminDnsManagerController::class, 'postDnsZone'])->name('dns_zone.post')
    ->middleware('ApiPermission:dns-manager-dns-zones');
Route::get('dns_zone/{uuid}', [AdminDnsManagerController::class, 'getDnsZone'])->name('dns_zone.get')
    ->middleware('ApiPermission:dns-manager-dns-zones');
Route::put('dns_zone/{uuid}', [AdminDnsManagerController::class, 'putDnsZone'])->name('dns_zone.put')
    ->middleware('ApiPermission:dns-manager-dns-zones');
Route::delete('dns_zone/{uuid}', [AdminDnsManagerController::class, 'deleteDnsZone'])->name('dns_zone.delete')
    ->middleware('ApiPermission:dns-manager-dns-zones');

Route::put('dns_zone/{uuid}/move_to', [AdminDnsManagerController::class, 'putDnsZoneMoveTo'])->name('dns_zone.move_to.put')
    ->middleware('ApiPermission:dns-manager-dns-zones');

Route::get('dns_zone/{uuid}/reload', [AdminDnsManagerController::class, 'getDnsZoneReload'])->name('dns_zone.reload.get')
    ->middleware('ApiPermission:dns-manager-dns-zones');

Route::get('dns_zone/{uuid}/export/bind', [AdminDnsManagerController::class, 'getDnsZoneExportBind'])->name('dns_zone.export.bind.get')
    ->middleware('ApiPermission:dns-manager-dns-zones');
Route::get('dns_zone/{uuid}/export/json', [AdminDnsManagerController::class, 'getDnsZoneExportJson'])->name('dns_zone.export.json.get')
    ->middleware('ApiPermission:dns-manager-dns-zones');

Route::post('dns_zone/{uuid}/import/bind', [AdminDnsManagerController::class, 'postDnsZoneImportBind'])->name('dns_zone.import.bind.post')
    ->middleware('ApiPermission:dns-manager-dns-zones');
Route::post('dns_zone/{uuid}/import/json', [AdminDnsManagerController::class, 'postDnsZoneImportJson'])->name('dns_zone.import.json.post')
    ->middleware('ApiPermission:dns-manager-dns-zones');

Route::delete('dns_zone/{uuid}/dns_records/bulk', [AdminDnsManagerController::class, 'deleteDnsZoneDnsRecordsBulk'])->name('dns_zone.dns_records.bulk_delete')
    ->middleware('ApiPermission:dns-manager-dns-records');

// dns_zones dns_records ------------------------------------------------------------------------------------------------
Route::get('dns_zone/{uuid}/dns_records', [AdminDnsManagerController::class, 'getDnsZoneDnsRecords'])->name('dns_zone.dns_records.get')
    ->middleware('ApiPermission:dns-manager-dns-records');
Route::post('dns_zone/{uuid}/dns_record', [AdminDnsManagerController::class, 'postDnsZoneDnsRecord'])->name('dns_zone.dns_record.post')
    ->middleware('ApiPermission:dns-manager-dns-records');
Route::get('dns_zone/{uuid}/dns_record/{r_uuid}', [AdminDnsManagerController::class, 'getDnsZoneDnsRecord'])->name('dns_zone.dns_record.get')
    ->middleware('ApiPermission:dns-manager-dns-records');
Route::put('dns_zone/{uuid}/dns_record/{r_uuid}', [AdminDnsManagerController::class, 'putDnsZoneDnsRecord'])->name('dns_zone.dns_record.put')
    ->middleware('ApiPermission:dns-manager-dns-records');
Route::delete('dns_zone/{uuid}/dns_record/{r_uuid}', [AdminDnsManagerController::class, 'deleteDnsZoneDnsRecord'])->name('dns_zone.dns_record.delete')
    ->middleware('ApiPermission:dns-manager-dns-records');

// DNS Manager
Route::get('dns_server_modules/select',
    [AdminDnsManagerController::class, 'getDnsServerModulesSelect'])->name('dns_server_modules.select.get')
    ->middleware('ApiPermission:dns-manager-dns-servers');
Route::get('dns_servers/select',
    [AdminDnsManagerController::class, 'getDnsServersSelect'])->name('dns_servers.select.get')
    ->middleware('ApiPermission:dns-manager-dns-servers');
Route::get('dns_server_groups/select',
    [AdminDnsManagerController::class, 'getDnsServerGroupsSelect'])->name('dns_server_groups.select.get')
    ->middleware('ApiPermission:dns-manager-dns-zones');
Route::get('dns_zones/select',
    [AdminDnsManagerController::class, 'getDnsZonesSelect'])->name('dns_zones.select.get');

Route::post('dns_zone/{uuid}/clone', [AdminDnsManagerController::class, 'postDnsZoneClone'])->name('dns_zone.clone.post')
    ->middleware('ApiPermission:dns-manager-dns-zones');
Route::post('dns_zone/{uuid}/save_as_template', [AdminDnsManagerController::class, 'postDnsZoneTemplateFromZone'])->name('dns_zone.save_as_template.post')
    ->middleware('ApiPermission:dns-manager-dns-zones');

// DNS Zone Templates
Route::get('dns_zone_templates', [AdminDnsManagerController::class, 'getDnsZoneTemplates'])->name('dns_zone_templates.get')
    ->middleware('ApiPermission:dns-manager-dns-zones');
Route::post('dns_zone_template', [AdminDnsManagerController::class, 'postDnsZoneTemplate'])->name('dns_zone_template.post')
    ->middleware('ApiPermission:dns-manager-dns-zones');
Route::get('dns_zone_template/{uuid}', [AdminDnsManagerController::class, 'getDnsZoneTemplate'])->name('dns_zone_template.get')
    ->middleware('ApiPermission:dns-manager-dns-zones');
Route::put('dns_zone_template/{uuid}', [AdminDnsManagerController::class, 'putDnsZoneTemplate'])->name('dns_zone_template.put')
    ->middleware('ApiPermission:dns-manager-dns-zones');
Route::delete('dns_zone_template/{uuid}', [AdminDnsManagerController::class, 'deleteDnsZoneTemplate'])->name('dns_zone_template.delete')
    ->middleware('ApiPermission:dns-manager-dns-zones');
Route::get('dns_zone_templates/select', [AdminDnsManagerController::class, 'getDnsZoneTemplatesSelect'])->name('dns_zone_templates.select.get')
    ->middleware('ApiPermission:dns-manager-dns-zones');

// Support Tickets -----------------------------------------------------------------------------------------------------------------

// ticket departments
Route::get('ticket_departments', [AdminTicketDepartmentsController::class, 'getTicketDepartments'])->name('ticket_departments.get')
    ->middleware('ApiPermission:tickets-departments');
Route::post('ticket_department', [AdminTicketDepartmentsController::class, 'postTicketDepartment'])->name('ticket_department.post')
    ->middleware('ApiPermission:tickets-departments');
Route::get('ticket_department/{uuid}', [AdminTicketDepartmentsController::class, 'getTicketDepartment'])->name('ticket_department.get')
    ->middleware('ApiPermission:tickets-departments');
Route::put('ticket_department/{uuid}', [AdminTicketDepartmentsController::class, 'putTicketDepartment'])->name('ticket_department.put')
    ->middleware('ApiPermission:tickets-departments');
Route::delete('ticket_department/{uuid}', [AdminTicketDepartmentsController::class, 'deleteTicketDepartment'])->name('ticket_department.delete')
    ->middleware('ApiPermission:tickets-departments');
Route::get('ticket_departments/select', [AdminTicketDepartmentsController::class, 'getTicketDepartmentsSelect'])->name('ticket_departments.select.get');
Route::post('ticket_department/{uuid}/test_mail', [AdminTicketDepartmentsController::class, 'testMailConnection'])->name('ticket_department.test_mail.post')
    ->middleware('ApiPermission:tickets-departments');

// ticket statuses
Route::get('ticket_statuses', [AdminTicketStatusesController::class, 'getTicketStatuses'])->name('ticket_statuses.get')
    ->middleware('ApiPermission:tickets-statuses');
Route::post('ticket_status', [AdminTicketStatusesController::class, 'postTicketStatus'])->name('ticket_status.post')
    ->middleware('ApiPermission:tickets-statuses');
Route::get('ticket_status/{uuid}', [AdminTicketStatusesController::class, 'getTicketStatus'])->name('ticket_status.get')
    ->middleware('ApiPermission:tickets-statuses');
Route::put('ticket_status/{uuid}', [AdminTicketStatusesController::class, 'putTicketStatus'])->name('ticket_status.put')
    ->middleware('ApiPermission:tickets-statuses');
Route::delete('ticket_status/{uuid}', [AdminTicketStatusesController::class, 'deleteTicketStatus'])->name('ticket_status.delete')
    ->middleware('ApiPermission:tickets-statuses');
Route::get('ticket_statuses/select', [AdminTicketStatusesController::class, 'getTicketStatusesSelect'])->name('ticket_statuses.select.get');

// tickets
Route::get('tickets', [AdminTicketsController::class, 'getTickets'])->name('tickets.get')
    ->middleware('ApiPermission:tickets-view');
Route::post('ticket', [AdminTicketsController::class, 'postTicket'])->name('ticket.post')
    ->middleware('ApiPermission:tickets-create');
Route::get('ticket/{uuid}', [AdminTicketsController::class, 'getTicket'])->name('ticket.get')
    ->middleware('ApiPermission:tickets-view');
Route::put('ticket/{uuid}', [AdminTicketsController::class, 'putTicket'])->name('ticket.put')
    ->middleware('ApiPermission:tickets-edit');
Route::delete('ticket/{uuid}', [AdminTicketsController::class, 'deleteTicket'])->name('ticket.delete')
    ->middleware('ApiPermission:tickets-delete');
Route::post('ticket/{uuid}/reply', [AdminTicketsController::class, 'postTicketReply'])->name('ticket.reply.post')
    ->middleware('ApiPermission:tickets-edit');
Route::post('ticket/{uuid}/note', [AdminTicketsController::class, 'postTicketNote'])->name('ticket.note.post')
    ->middleware('ApiPermission:tickets-edit');
Route::post('ticket/{uuid}/merge', [AdminTicketsController::class, 'postTicketMerge'])->name('ticket.merge.post')
    ->middleware('ApiPermission:tickets-edit');
Route::get('ticket/{uuid}/replies', [AdminTicketsController::class, 'getTicketReplies'])->name('ticket.replies.get')
    ->middleware('ApiPermission:tickets-view');
Route::put('ticket_reply/{uuid}', [AdminTicketsController::class, 'putTicketReply'])->name('ticket_reply.put')
    ->middleware('ApiPermission:tickets-edit');
Route::post('ticket_reply/attachment', [AdminTicketsController::class, 'postTicketReplyAttachment'])->name('ticket_reply.attachment.post')
    ->middleware('ApiPermission:tickets-edit');
Route::delete('ticket_reply/attachment/{uuid}', [AdminTicketsController::class, 'deleteTicketReplyAttachment'])->name('ticket_reply.attachment.delete')
    ->middleware('ApiPermission:tickets-edit');
Route::post('ticket/{uuid}/publish', [AdminTicketsController::class, 'publishTicket'])->name('ticket.publish.post')
    ->middleware('ApiPermission:tickets-create');
Route::post('ticket/attachment', [AdminTicketsController::class, 'postTicketAttachment'])->name('ticket.attachment.post')
    ->middleware('ApiPermission:tickets-edit');
Route::delete('ticket/attachment/{uuid}', [AdminTicketsController::class, 'deleteTicketAttachment'])->name('ticket.attachment.delete')
    ->middleware('ApiPermission:tickets-edit');
Route::get('ticket/attachment/{uuid}', [AdminTicketsController::class, 'getTicketReplyAttachmentDownload'])->name('ticket.attachment.get')
    ->middleware('ApiPermission:tickets-view');
Route::get('ticket/{uuid}/activity', [AdminTicketsController::class, 'getTicketActivity'])->name('ticket.activity.get')
    ->middleware('ApiPermission:tickets-view');
Route::post('tickets/bulk_action', [AdminTicketsController::class, 'postBulkAction'])->name('tickets.bulk_action.post')
    ->middleware('ApiPermission:tickets-edit');

// ticket canned responses
Route::get('ticket_canned_responses', [\App\Http\Controllers\Admin\AdminTicketCannedResponsesController::class, 'getTicketCannedResponses'])->name('ticket_canned_responses.get')
    ->middleware('ApiPermission:tickets-edit');
Route::post('ticket_canned_response', [\App\Http\Controllers\Admin\AdminTicketCannedResponsesController::class, 'postTicketCannedResponse'])->name('ticket_canned_response.post')
    ->middleware('ApiPermission:tickets-edit');
Route::get('ticket_canned_response/{uuid}', [\App\Http\Controllers\Admin\AdminTicketCannedResponsesController::class, 'getTicketCannedResponse'])->name('ticket_canned_response.get')
    ->middleware('ApiPermission:tickets-edit');
Route::put('ticket_canned_response/{uuid}', [\App\Http\Controllers\Admin\AdminTicketCannedResponsesController::class, 'putTicketCannedResponse'])->name('ticket_canned_response.put')
    ->middleware('ApiPermission:tickets-edit');
Route::delete('ticket_canned_response/{uuid}', [\App\Http\Controllers\Admin\AdminTicketCannedResponsesController::class, 'deleteTicketCannedResponse'])->name('ticket_canned_response.delete')
    ->middleware('ApiPermission:tickets-edit');
Route::get('ticket_canned_responses/select', [\App\Http\Controllers\Admin\AdminTicketCannedResponsesController::class, 'getTicketCannedResponsesSelect'])->name('ticket_canned_responses.select.get');

// ticket email blacklist
Route::get('ticket_email_blacklist', [\App\Http\Controllers\Admin\AdminTicketEmailBlacklistController::class, 'getTicketEmailBlacklist'])->name('ticket_email_blacklist.get')
    ->middleware('ApiPermission:tickets-edit');
Route::post('ticket_email_blacklist', [\App\Http\Controllers\Admin\AdminTicketEmailBlacklistController::class, 'postTicketEmailBlacklist'])->name('ticket_email_blacklist.post')
    ->middleware('ApiPermission:tickets-edit');
Route::delete('ticket_email_blacklist/{uuid}', [\App\Http\Controllers\Admin\AdminTicketEmailBlacklistController::class, 'deleteTicketEmailBlacklist'])->name('ticket_email_blacklist.delete')
    ->middleware('ApiPermission:tickets-edit');

// client tickets (admin viewing client's tickets)
Route::get('client/{uuid}/tickets', [AdminTicketsController::class, 'getClientTickets'])->name('client.tickets.get')
    ->middleware('ApiPermission:tickets-view');

// certificate_authorities --------------------------------------------------------------------------------------------------------
Route::get('certificate_authorities', [AdminSslManagerController::class, 'getCertificateAuthorities'])->name('certificate_authorities.get')
    ->middleware('ApiPermission:ssl-manager-certificate-authorities');
Route::post('certificate_authority', [AdminSslManagerController::class, 'postCertificateAuthority'])->name('certificate_authority.post')
    ->middleware('ApiPermission:ssl-manager-certificate-authorities');
Route::get('certificate_authority/{uuid}', [AdminSslManagerController::class, 'getCertificateAuthority'])->name('certificate_authority.get')
    ->middleware('ApiPermission:ssl-manager-certificate-authorities');
Route::put('certificate_authority/{uuid}', [AdminSslManagerController::class, 'putCertificateAuthority'])->name('certificate_authority.put')
    ->middleware('ApiPermission:ssl-manager-certificate-authorities');
Route::delete('certificate_authority/{uuid}', [AdminSslManagerController::class, 'deleteCertificateAuthority'])->name('certificate_authority.delete')
    ->middleware('ApiPermission:ssl-manager-certificate-authorities');

Route::get('certificate_authority/{uuid}/test_connection',
    [AdminSslManagerController::class, 'getCertificateAuthorityTestConnection'])->name('certificate_authority.test_connection.get')
    ->middleware('ApiPermission:ssl-manager-certificate-authorities');

// ssl_certificates --------------------------------------------------------------------------------------------------------------
Route::get('ssl_certificates', [AdminSslManagerController::class, 'getSslCertificates'])->name('ssl_certificates.get')
    ->middleware('ApiPermission:ssl-manager-ssl-certificates');
Route::post('ssl_certificate', [AdminSslManagerController::class, 'postSslCertificate'])->name('ssl_certificate.post')
    ->middleware('ApiPermission:ssl-manager-ssl-certificates');
Route::get('ssl_certificate/{uuid}', [AdminSslManagerController::class, 'getSslCertificate'])->name('ssl_certificate.get')
    ->middleware('ApiPermission:ssl-manager-ssl-certificates');
Route::put('ssl_certificate/{uuid}', [AdminSslManagerController::class, 'putSslCertificate'])->name('ssl_certificate.put')
    ->middleware('ApiPermission:ssl-manager-ssl-certificates');
Route::delete('ssl_certificate/{uuid}', [AdminSslManagerController::class, 'deleteSslCertificate'])->name('ssl_certificate.delete')
    ->middleware('ApiPermission:ssl-manager-ssl-certificates');

Route::get('ssl_certificate/{uuid}/generate_csr', [AdminSslManagerController::class, 'getSslCertificateGenerateCsr'])->name('ssl_certificate.generate_csr.get')
    ->middleware('ApiPermission:ssl-manager-ssl-certificates');

Route::put('ssl_certificate/{uuid}/status', [AdminSslManagerController::class, 'putSslCertificateStatus'])->name('ssl_certificate.status.put')
    ->middleware('ApiPermission:ssl-manager-ssl-certificates');

Route::delete('ssl_certificates/delete_expired', [AdminSslManagerController::class, 'deleteSslCertificatesExpired'])->name('ssl_certificates.delete_expired.delete')
    ->middleware('ApiPermission:ssl-manager-ssl-certificates');
Route::delete('ssl_certificates/delete_draft', [AdminSslManagerController::class, 'deleteSslCertificatesDraft'])->name('ssl_certificates.delete_draft.delete')
    ->middleware('ApiPermission:ssl-manager-ssl-certificates');

// SSL Manager
Route::get('certificate_authority_modules/select',
    [AdminSslManagerController::class, 'getCertificateAuthorityModulesSelect'])->name('certificate_authority_modules.select.get')
    ->middleware('ApiPermission:ssl-manager-certificate-authorities');

Route::get('certificate_authorities/select',
    [AdminSslManagerController::class, 'getCertificateAuthoritiesSelect'])->name('certificate_authorities.select.get')
    ->middleware('ApiPermission:ssl-manager-ssl-certificates');

// general_settings ----------------------------------------------------------------------------------------------------
Route::get('general_settings', [AdminSettingsController::class, 'getGeneralSettings'])->name('general_settings.get')
    ->middleware('WebPermission:general-settings-management');
Route::put('general_settings', [AdminSettingsController::class, 'putGeneralSettings'])->name('general_settings.put')
    ->middleware('WebPermission:general-settings-management');

Route::get('countries', [AdminSettingsController::class, 'getCountries'])->name('countries.get')
    ->middleware('WebPermission:general-settings-management');
Route::get('country/{uuid}/regions', [AdminSettingsController::class, 'getCountryRegions'])->name('country_regions.get')
    ->middleware('WebPermission:general-settings-management');

Route::get('currencies', [AdminSettingsController::class, 'getCurrencies'])->name('currencies.get')
    ->middleware('WebPermission:currencies-management');
Route::post('currency', [AdminSettingsController::class, 'postCurrency'])->name('currency.post')
    ->middleware('WebPermission:currencies-management');
Route::get('currency/{uuid}', [AdminSettingsController::class, 'getCurrency'])->name('currency.get')
    ->middleware('WebPermission:currencies-management');
Route::put('currency/{uuid}', [AdminSettingsController::class, 'putCurrency'])->name('currency.put')
    ->middleware('WebPermission:currencies-management');
Route::delete('currency/{uuid}', [AdminSettingsController::class, 'deleteCurrency'])->name('currency.delete')
    ->middleware('WebPermission:currencies-management');

// clients -------------------------------------------------------------------------------------------------------------
Route::get('clients', [AdminClientsController::class, 'getClients'])->name('clients.get')
    ->middleware('WebPermission:clients-view');
Route::get('client/{uuid}', [AdminClientsController::class, 'getClient'])->name('client.get')
    ->middleware('WebPermission:clients-view');
Route::post('client', [AdminClientsController::class, 'postClient'])->name('client.post')
    ->middleware('WebPermission:clients-create');
Route::put('client/{uuid}', [AdminClientsController::class, 'putClient'])->name('client.put')
    ->middleware('WebPermission:clients-edit');
// Route::delete('client/{uuid}', [AdminClientsController::class, 'deleteClient'])->name('client.delete')
//    ->middleware('WebPermission:clients-delete');

Route::get('client/{uuid}/addresses',
    [AdminClientAddressesController::class, 'getClientAddresses'])->name('client.addresses.get')
    ->middleware('WebPermission:clients-view');

Route::post('client/{client_uuid}/address',
    [AdminClientAddressesController::class, 'postClientAddress'])->name('client.address.post')
    ->middleware('WebPermission:clients-edit');
Route::put('client/{client_uuid}/address/{address_uuid}',
    [AdminClientAddressesController::class, 'putClientAddress'])->name('client.address.put')
    ->middleware('WebPermission:clients-edit');
Route::get('client/{client_uuid}/address/{address_uuid}',
    [AdminClientAddressesController::class, 'getClientAddress'])->name('client.address.get')
    ->middleware('WebPermission:clients-edit');
Route::delete('client/{client_uuid}/address/{address_uuid}',
    [AdminClientAddressesController::class, 'deleteClientAddress'])->name('client.address.delete')
    ->middleware('WebPermission:clients-edit');

Route::get('client/{uuid}/users', [AdminClientUsersController::class, 'getClientUsers'])->name('client.users.get')
    ->middleware('WebPermission:clients-view');
Route::post('client/{uuid}/user/associate',
    [AdminClientUsersController::class, 'postClientUserAssociate'])->name('client.user.associate.post')
    ->middleware('WebPermission:clients-edit');
Route::get('client/{client_uuid}/user/{user_uuid}',
    [AdminClientUsersController::class, 'getClientUser'])->name('client.user.get')
    ->middleware('WebPermission:clients-edit');
Route::put('client/{client_uuid}/user/{user_uuid}',
    [AdminClientUsersController::class, 'putClientUser'])->name('client.user.put')
    ->middleware('WebPermission:clients-edit');
Route::delete('client/{client_uuid}/user/{user_uuid}',
    [AdminClientUsersController::class, 'deleteClientUserAssociate'])->name('client.user.delete')
    ->middleware('WebPermission:clients-edit');

Route::get('client/{uuid}/services', [AdminClientFinanceController::class, 'getClientServices'])->name('client.services.get')
    ->middleware('WebPermission:clients-view');

Route::get('client/{uuid}/services/select',
    [AdminClientFinanceController::class, 'getServicesSelect'])->name('client.services.select.get')
    ->middleware('WebPermission:clients-view');

Route::get('client/{uuid}/transactions',
    [AdminClientFinanceController::class, 'getClientTransactions'])->name('client.transactions.get')
    ->middleware('WebPermission:finance-view');
Route::post('client/{uuid}/transaction',
    [AdminClientFinanceController::class, 'postClientTransaction'])->name('client.transaction.post')
    ->middleware('WebPermission:finance-edit');

Route::get('client/{uuid}/invoices', [AdminClientFinanceController::class, 'getClientInvoices'])->name('client.invoices.get')
    ->middleware('WebPermission:finance-view');

Route::get('client/{uuid}/notifications', [AdminClientActivityController::class, 'getClientNotifications'])->name('client.notifications.get')
    ->middleware('WebPermission:notification-history-view');

Route::get('client/{uuid}/activity_logs', [AdminClientActivityController::class, 'getClientActivityLogs'])->name('client.activity_logs.get')
    ->middleware('WebPermission:activity-log-view');

Route::post('client/{uuid}/invoice/proforma/add_funds', [
    AdminClientFinanceController::class, 'postClientInvoiceProformaAddFunds',
])->name('client.invoice.proforma.add_funds.post')
    ->middleware('WebPermission:finance-edit');

Route::post('client/{uuid}/invoice/standalone', [
    AdminClientFinanceController::class, 'postClientInvoiceStandalone',
])->name('client.invoice.standalone.post')
    ->middleware('WebPermission:finance-create');

Route::get('client/{uuid}/summary/widgets',
    [AdminWidgetsController::class, 'getClientSummaryWidgets'])->name('client.summary.widgets.get');
Route::get('client/{uuid}/summary/widget',
    [AdminWidgetsController::class, 'getClientSummaryWidget'])->name('client.summary.widget.get');

Route::get('client/{uuid}/summary/dashboard',
    [AdminWidgetsController::class, 'getClientSummaryDashboard'])->name('client.summary.dashboard.get');
Route::put('client/{uuid}/summary/dashboard',
    [AdminWidgetsController::class, 'putClientSummaryDashboard'])->name('client.summary.dashboard.put');

// users ---------------------------------------------------------------------------------------------------------------
Route::get('users', [AdminUsersController::class, 'getUsers'])->name('users.get')
    ->middleware('WebPermission:users-view');
Route::get('user/{uuid}', [AdminUsersController::class, 'getUser'])->name('user.get')
    ->middleware('WebPermission:users-view');
Route::get('user/{uuid}/clients', [AdminUsersController::class, 'getUserClients'])->name('user.clients.get')
    ->middleware('WebPermission:users-view');
Route::post('user', [AdminUsersController::class, 'postUser'])->name('user.post')
    ->middleware('WebPermission:users-create');
Route::put('user/{uuid}', [AdminUsersController::class, 'putUser'])->name('user.put')
    ->middleware('WebPermission:users-edit');
Route::delete('user/{uuid}', [AdminUsersController::class, 'deleteUser'])->name('user.delete')
    ->middleware('WebPermission:users-delete');

// Add-ons -------------------------------------------------------------------------------------------------------------
Route::get('add_ons/modules', [AdminAddOnsController::class, 'getModules'])->name('add_ons.modules.get')
    ->middleware('WebPermission:add-ons-modules-management');
Route::post('add_ons/module/{uuid}/activate',
    [AdminAddOnsController::class, 'postModuleActivate'])->name('add_ons.module.activate.post')
    ->middleware('WebPermission:add-ons-modules-management');
Route::post('add_ons/module/{uuid}/deactivate',
    [AdminAddOnsController::class, 'postModuleDeactivate'])->name('add_ons.module.deactivate.post')
    ->middleware('WebPermission:add-ons-modules-management');
Route::post('add_ons/module/{uuid}/update',
    [AdminAddOnsController::class, 'postModuleUpdate'])->name('add_ons.module.update.post')
    ->middleware('WebPermission:add-ons-modules-management');
Route::delete('add_ons/module/{uuid}/delete',
    [AdminAddOnsController::class, 'postModuleDelete'])->name('add_ons.module.delete.delete')
    ->middleware('WebPermission:add-ons-modules-management');

// API Documentation ---------------------------------------------------------------------------------------------------

Route::get('api_documentations/admin/', [AdminApiDocumentationController::class, 'getAdminApiDocumentations'])->name('api_documentations.admin.get')
    ->middleware('WebPermission:api-documentation-admin');

Route::get('api_documentation/admin/{module}/{name}', [AdminApiDocumentationController::class, 'getAdminApiDocumentation'])->name('api_documentation.admin.swagger.get')
    ->middleware('WebPermission:api-documentation-admin');

Route::get('api_documentations/client/', [AdminApiDocumentationController::class, 'getClientApiDocumentations'])->name('api_documentations.client.get')
    ->middleware('WebPermission:api-documentation-client');

Route::get('api_documentation/client/{module}/{name}', [AdminApiDocumentationController::class, 'getClientApiDocumentation'])->name('api_documentation.client.swagger.get')
    ->middleware('WebPermission:api-documentation-client');

// Products ------------------------------------------------------------------------------------------------------------
Route::get('products', [AdminProductsController::class, 'getProducts'])->name('products.get')
    ->middleware('WebPermission:products-management');
Route::post('product', [AdminProductsController::class, 'postProduct'])->name('product.post')
    ->middleware('WebPermission:products-management');
Route::get('product/{uuid}', [AdminProductsController::class, 'getProduct'])->name('product.get')
    ->middleware('WebPermission:products-management');
Route::put('product/{uuid}', [AdminProductsController::class, 'putProduct'])->name('product.put')
    ->middleware('WebPermission:products-management');
Route::delete('product/{uuid}', [AdminProductsController::class, 'deleteProduct'])->name('product.delete')
    ->middleware('WebPermission:products-management');

Route::put('product/{uuid}/module', [AdminProductsController::class, 'putProductModule'])->name('product.module.put')
    ->middleware('WebPermission:products-management');
Route::get('product/{uuid}/module', [AdminProductsController::class, 'getProductModule'])->name('product.module.get')
    ->middleware('WebPermission:products-management');

Route::get('product/{uuid}/prices', [AdminProductsController::class, 'getProductPrices'])->name('product.prices.get')
    ->middleware('WebPermission:products-management');
Route::get('product/{uuid}/price/{p_uuid}',
    [AdminProductsController::class, 'getProductPrice'])->name('product.price.get')
    ->middleware('WebPermission:products-management');
Route::put('product/{uuid}/price', [AdminProductsController::class, 'putProductPrice'])->name('product.price.put')
    ->middleware('WebPermission:products-management');
Route::post('product/{uuid}/price', [AdminProductsController::class, 'postProductPrice'])->name('product.price.post')
    ->middleware('WebPermission:products-management');
Route::delete('product/{uuid}/price/{p_uuid}',
    [AdminProductsController::class, 'deleteProductPrice'])->name('product.price.delete')
    ->middleware('WebPermission:products-management');

Route::get('product/{uuid}/product_attributes',
    [AdminProductsController::class, 'getProductProductAttributes'])->name('product.product_attributes.get')
    ->middleware('WebPermission:products-management');
Route::post('product/{uuid}/product_attribute',
    [AdminProductsController::class, 'postProductProductAttribute'])->name('product.product_attribute.post')
    ->middleware('WebPermission:products-management');
Route::get('product/{uuid}/product_attributes/select', [
    AdminProductsController::class, 'getProductProductAttributesSelect',
])->name('product.product_attributes.select.get')
    ->middleware('WebPermission:products-management');
Route::delete('product/{uuid}/product_attribute/{pa_uuid}',
    [AdminProductsController::class, 'deleteProductProductAttribute'])->name('product.product_attribute.delete')
    ->middleware('WebPermission:products-management');

Route::get('product/{uuid}/product_option_groups',
    [AdminProductsController::class, 'getProductProductOptionGroups'])->name('product.product_option_groups.get')
    ->middleware('WebPermission:products-management');
Route::post('product/{uuid}/product_option_group',
    [AdminProductsController::class, 'postProductProductOptionGroup'])->name('product.product_option_group.post')
    ->middleware('WebPermission:products-management');
Route::get('product/{uuid}/product_option_groups/select', [
    AdminProductsController::class, 'getProductProductOptionsGroupsSelect',
])->name('product.product_option_groups.select.get')
    ->middleware('WebPermission:products-management');
Route::delete('product/{uuid}/product_option_group/{pog_uuid}',
    [AdminProductsController::class, 'deleteProductProductOptionGroups'])->name('product.product_option_group.delete')
    ->middleware('WebPermission:products-management');

Route::get('product/{uuid}/modules/select',
    [AdminProductsController::class, 'getProductModulesSelect'])->name('product.module.select.get')
    ->middleware('WebPermission:products-management');

// Product groups ------------------------------------------------------------------------------------------------------
Route::get('product_groups', [AdminProductGroupsController::class, 'getProductGroups'])->name('product_groups.get')
    ->middleware('WebPermission:product-groups-management');
Route::post('product_groups/update_order',
    [AdminProductGroupsController::class, 'postProductGroupsUpdateOrder'])->name('product_groups.update_order.post')
    ->middleware('WebPermission:product-groups-management');
Route::post('product_group', [AdminProductGroupsController::class, 'postProductGroup'])->name('product_group.post')
    ->middleware('WebPermission:product-groups-management');
Route::get('product_group/{uuid}', [AdminProductGroupsController::class, 'getProductGroup'])->name('product_group.get')
    ->middleware('WebPermission:product-groups-management');
Route::put('product_group/{uuid}', [AdminProductGroupsController::class, 'putProductGroup'])->name('product_group.put')
    ->middleware('WebPermission:product-groups-management');
Route::delete('product_group/{uuid}',
    [AdminProductGroupsController::class, 'deleteProductGroup'])->name('product_group.delete')
    ->middleware('WebPermission:product-groups-management');
Route::get('product_group/{uuid}/products',
    [AdminProductGroupsController::class, 'getProductGroupProducts'])->name('product_group_products.get')
    ->middleware('WebPermission:product-groups-management');
Route::get('product_group/{uuid}/products/select',
    [AdminProductGroupsController::class, 'getProductGroupProductsSelect'])->name('product_group_products.select.get')
    ->middleware('WebPermission:product-groups-management');
Route::post('product_group/{uuid}/product',
    [AdminProductGroupsController::class, 'postProductGroupProduct'])->name('product_group_product.post')
    ->middleware('WebPermission:product-groups-management');
Route::post('product_group/{uuid}/products/update_order', [
    AdminProductGroupsController::class, 'postProductGroupProductsUpdateOrder',
])->name('product_group_products.update_order.post')
    ->middleware('WebPermission:product-groups-management');
Route::delete('product_group/{uuid}/product/{p_uuid}',
    [AdminProductGroupsController::class, 'deleteProductGroupProduct'])->name('product_group_product.delete')
    ->middleware('WebPermission:product-groups-management');

Route::post('product_group/{uuid}/product_option_group/update_order', [
    AdminProductsController::class, 'postProductProductOptionGroupsUpdateOrder',
])->name('product.product_option_group.update_order.post')
    ->middleware('WebPermission:products-management');

Route::get('product_group/list_templates/select',
    [AdminProductGroupsController::class, 'getListTemplatesSelect'])->name('product_group.list_templates.select.get')
    ->middleware('WebPermission:products-management');
Route::get('product_group/order_templates/select',
    [AdminProductGroupsController::class, 'getOrderTemplatesSelect'])->name('product_group.order_templates.select.get')
    ->middleware('WebPermission:products-management');
Route::get('product_group/manage_templates/select',
    [AdminProductGroupsController::class, 'getManageTemplatesSelect'])->name('product_group.manage_templates.select.get')
    ->middleware('WebPermission:products-management');
Route::get('product_group/upgrade_templates/select',
    [AdminProductGroupsController::class, 'getUpgradeTemplatesSelect'])->name('product_group.upgrade_templates.select.get')
    ->middleware('WebPermission:products-management');
Route::get('product_group/proration_templates/select',
    [AdminProductGroupsController::class, 'getProrationTemplatesSelect'])->name('product_group.proration_templates.select.get')
    ->middleware('WebPermission:products-management');

// Product attribute group ---------------------------------------------------------------------------------------------
Route::get('product_attribute_groups',
    [AdminProductAttributesController::class, 'getProductAttributeGroups'])->name('product_attribute_groups.get')
    ->middleware('WebPermission:product-attributes-management');
Route::post('product_attribute_group',
    [AdminProductAttributesController::class, 'postProductAttributeGroup'])->name('product_attribute_group.post')
    ->middleware('WebPermission:product-groups-management');
Route::get('product_attribute_group/{uuid}',
    [AdminProductAttributesController::class, 'getProductAttributeGroup'])->name('product_attribute_group.get')
    ->middleware('WebPermission:product-attributes-management');
Route::put('product_attribute_group/{uuid}',
    [AdminProductAttributesController::class, 'putProductAttributeGroup'])->name('product_attribute_group.put')
    ->middleware('WebPermission:product-attributes-management');
Route::delete('product_attribute_group/{uuid}',
    [AdminProductAttributesController::class, 'deleteProductAttributeGroup'])->name('product_attribute_group.delete')
    ->middleware('WebPermission:product-attributes-management');

// Product attribute ---------------------------------------------------------------------------------------------------
Route::get('product_attribute_group/{uuid}/product_attributes', [
    AdminProductAttributesController::class, 'getProductAttributeGroupProductAttributes',
])->name('product_attribute_group.product_attributes.get')
    ->middleware('WebPermission:product-attributes-management');
Route::post('product_attribute_group/{uuid}/product_attribute', [
    AdminProductAttributesController::class, 'postProductAttributeGroupProductAttribute',
])->name('product_attribute_group.product_attribute.post')
    ->middleware('WebPermission:product-groups-management');
Route::get('product_attribute/{uuid}',
    [AdminProductAttributesController::class, 'getProductAttribute'])->name('product_attribute.get')
    ->middleware('WebPermission:product-attributes-management');
Route::put('product_attribute/{uuid}',
    [AdminProductAttributesController::class, 'putProductAttribute'])->name('product_attribute.put')
    ->middleware('WebPermission:product-attributes-management');
Route::delete('product_attribute/{uuid}',
    [AdminProductAttributesController::class, 'deleteProductAttribute'])->name('product_attribute.delete')
    ->middleware('WebPermission:product-attributes-management');

// Product option group ---------------------------------------------------------------------------------------------
Route::get('product_option_groups',
    [AdminProductOptionsController::class, 'getProductOptionGroups'])->name('product_option_groups.get')
    ->middleware('WebPermission:product-options-management');
Route::post('product_option_group',
    [AdminProductOptionsController::class, 'postProductOptionGroup'])->name('product_option_group.post')
    ->middleware('WebPermission:product-groups-management');
Route::get('product_option_group/{uuid}',
    [AdminProductOptionsController::class, 'getProductOptionGroup'])->name('product_option_group.get')
    ->middleware('WebPermission:product-options-management');
Route::put('product_option_group/{uuid}',
    [AdminProductOptionsController::class, 'putProductOptionGroup'])->name('product_option_group.put')
    ->middleware('WebPermission:product-options-management');
Route::delete('product_option_group/{uuid}',
    [AdminProductOptionsController::class, 'deleteProductOptionGroup'])->name('product_option_group.delete')
    ->middleware('WebPermission:product-options-management');

// Product option ---------------------------------------------------------------------------------------------------
Route::get('product_option_group/{uuid}/product_options', [
    AdminProductOptionsController::class, 'getProductOptionGroupProductOptions',
])->name('product_option_group.product_options.get')
    ->middleware('WebPermission:product-options-management');
Route::post('product_option_group/{uuid}/product_option', [
    AdminProductOptionsController::class, 'postProductOptionGroupProductOption',
])->name('product_option_group.product_option.post')
    ->middleware('WebPermission:product-groups-management');
Route::get('product_option/{uuid}', [AdminProductOptionsController::class, 'getProductOption'])->name('product_option.get')
    ->middleware('WebPermission:product-options-management');
Route::put('product_option/{uuid}', [AdminProductOptionsController::class, 'putProductOption'])->name('product_option.put')
    ->middleware('WebPermission:product-options-management');
Route::delete('product_option/{uuid}',
    [AdminProductOptionsController::class, 'deleteProductOption'])->name('product_option.delete')
    ->middleware('WebPermission:product-options-management');

Route::get('product_option/{uuid}/price',
    [AdminProductOptionsController::class, 'getProductOptionPrices'])->name('product_option.prices.get')
    ->middleware('WebPermission:product-options-management');
Route::get('product_option/{uuid}/price/{p_uuid}',
    [AdminProductOptionsController::class, 'getProductOptionPrice'])->name('product_option.price.get')
    ->middleware('WebPermission:product-options-management');
Route::post('product_option/{uuid}/price',
    [AdminProductOptionsController::class, 'postProductOptionPrice'])->name('product_option.price.post')
    ->middleware('WebPermission:product-options-management');
Route::put('product_option/{uuid}/price',
    [AdminProductOptionsController::class, 'putProductOptionPrice'])->name('product_option.price.put')
    ->middleware('WebPermission:product-options-management');
Route::delete('product_option/{uuid}/price/{p_uuid}',
    [AdminProductOptionsController::class, 'deleteProductOptionPrice'])->name('product_option.price.delete')
    ->middleware('WebPermission:product-options-management');

Route::post('product_options/update_order',
    [AdminProductOptionsController::class, 'postProductOptionsUpdateOrder'])->name('product_options.update_order.post')
    ->middleware('WebPermission:product-options-management');

// Service -------------------------------------------------------------------------------------------------------------
Route::get('services', [AdminServicesController::class, 'getServices'])->name('services.get')
    ->middleware('WebPermission:clients-edit');
Route::get('service/{uuid}', [AdminServicesController::class, 'getService'])->name('service.get')
    ->middleware('WebPermission:clients-edit');
Route::post('service', [AdminServicesController::class, 'postService'])->name('service.post')
    ->middleware('WebPermission:clients-edit');
Route::put('service/{uuid}', [AdminServicesController::class, 'putService'])->name('service.put')
    ->middleware('WebPermission:clients-edit');
Route::post('service/{uuid}/action', [AdminServicesController::class, 'postServiceAction'])->name('service.action.post')
    ->middleware('WebPermission:clients-edit');
Route::put('service/{uuid}/status', [AdminServicesController::class, 'putServiceStatus'])->name('service.status.put')
    ->middleware('WebPermission:clients-edit');
Route::put('service/{uuid}/billing_mode', [AdminServicesController::class, 'putServiceBillingMode'])->name('service.billing_mode.put')
    ->middleware('WebPermission:clients-edit');
Route::get('service/{uuid}/module', [AdminServicesController::class, 'getServiceModule'])->name('service.module.get')
    ->middleware('WebPermission:clients-edit');

Route::get('product_option_groups/by_product',
    [AdminProductsController::class, 'getProductOptionGroupsByProduct'])->name('product_option_groups.by_product.get')
    ->middleware('WebPermission:clients-edit');

// Finance -------------------------------------------------------------------------------------------------------------
// Transactions
Route::get('transactions', [AdminTransactionsController::class, 'getTransactions'])->name('transactions.get')
    ->middleware('WebPermission:finance-view');

// Home Companies
Route::get('home_companies', [AdminHomeCompaniesController::class, 'getHomeCompanies'])->name('home_companies.get')
    ->middleware('WebPermission:finance-view');
Route::get('home_company/{uuid}', [AdminHomeCompaniesController::class, 'getHomeCompany'])->name('home_company.get')
    ->middleware('WebPermission:finance-view');
Route::post('home_company', [AdminHomeCompaniesController::class, 'postHomeCompany'])->name('home_company.post')
    ->middleware('WebPermission:finance-create');
Route::put('home_company/{uuid}', [AdminHomeCompaniesController::class, 'putHomeCompany'])->name('home_company.put')
    ->middleware('WebPermission:finance-edit');
Route::delete('home_company/{uuid}', [AdminHomeCompaniesController::class, 'deleteHomeCompany'])->name('home_company.delete')
    ->middleware('WebPermission:finance-delete');

// Payment Gateways
Route::get('payment_modules/select',
    [AdminPaymentGatewaysController::class, 'getPaymentModulesSelect'])->name('payment_modules.select.get')
    ->middleware('WebPermission:finance-edit');
Route::get('home_company/{uuid}/payment_gateways',
    [AdminPaymentGatewaysController::class, 'getHomeCompanyPaymentGateways'])->name('home_company.payment_gateways.get')
    ->middleware('WebPermission:finance-view');
Route::post('home_company/{uuid}/payment_gateway',
    [AdminPaymentGatewaysController::class, 'postHomeCompanyPaymentGateway'])->name('home_company.payment_gateway.post')
    ->middleware('WebPermission:finance-create');

// Invoice Templates
Route::get('home_company/templates/{type}',
    [AdminHomeCompaniesController::class, 'getHomeCompanyInvoiceTemplates'])->name('home_company.invoice_templates.get')
    ->middleware('WebPermission:finance-view');
Route::get('home_company/template/{type}',
    [AdminHomeCompaniesController::class, 'getHomeCompanyInvoiceTemplateContent'])->name('home_company.invoice_template.get')
    ->middleware('WebPermission:finance-create');

// Tax Rules
Route::get('tax_rules', [AdminTaxRulesController::class, 'getTaxRules'])->name('tax_rules.get')
    ->middleware('WebPermission:finance-view');
Route::get('tax_rule/{uuid}', [AdminTaxRulesController::class, 'getTaxRule'])->name('tax_rule.get')
    ->middleware('WebPermission:finance-view');
Route::post('tax_rule', [AdminTaxRulesController::class, 'postTaxRule'])->name('tax_rule.post')
    ->middleware('WebPermission:finance-create');
Route::put('tax_rule/{uuid}', [AdminTaxRulesController::class, 'putTaxRule'])->name('tax_rule.put')
    ->middleware('WebPermission:finance-edit');
Route::delete('tax_rule/{uuid}', [AdminTaxRulesController::class, 'deleteTaxRule'])->name('tax_rule.delete')
    ->middleware('WebPermission:finance-delete');

Route::post('tax_rules/update_order',
    [AdminTaxRulesController::class, 'postTaxRulesUpdateOrder'])->name('tax_rules.update_order.post')
    ->middleware('WebPermission:finance-edit');
Route::post('tax_rule/eu', [AdminTaxRulesController::class, 'postTaxEuRules'])->name('tax_rule.eu.post')
    ->middleware('WebPermission:finance-create');
Route::post('tax_rule/canadian',
    [AdminTaxRulesController::class, 'postTaxCanadianRules'])->name('tax_rule.canadian.post')
    ->middleware('WebPermission:finance-create');

// Invoices
Route::get('invoice/{uuid}', [AdminInvoicesController::class, 'getInvoice'])->name('invoice.get')
    ->middleware('WebPermission:finance-view');
Route::delete('invoice/{uuid}', [AdminInvoicesController::class, 'deleteInvoice'])->name('invoice.delete')
    ->middleware('WebPermission:finance-edit');
Route::get('invoice/{uuid}/items', [AdminInvoicesController::class, 'getInvoiceItems'])->name('invoice.items.get')
    ->middleware('WebPermission:finance-view');
Route::get('invoice/{uuid}/transactions',
    [AdminInvoicesController::class, 'getInvoiceTransactions'])->name('invoice.transactions.get')
    ->middleware('WebPermission:finance-view');
Route::put('invoice/{uuid}/publish', [AdminInvoicesController::class, 'putInvoicePublish'])->name('invoice.publish.put')
    ->middleware('WebPermission:finance-edit');
Route::put('invoice/{uuid}/cancel', [AdminInvoicesController::class, 'putInvoiceCancel'])->name('invoice.cancel.put')
    ->middleware('WebPermission:finance-edit');
Route::put('invoice/{uuid}/to_draft', [AdminInvoicesController::class, 'putInvoiceToDraft'])->name('invoice.to_draft.put')
    ->middleware('WebPermission:finance-edit');
Route::put('invoice/{uuid}/due_date', [AdminInvoicesController::class, 'putInvoiceDueDate'])->name('invoice.due_date.put')
    ->middleware('WebPermission:finance-edit');
Route::post('invoice/{uuid}/resend_notification', [AdminInvoicesController::class, 'postInvoiceResendNotification'])->name('invoice.resend_notification.post')
    ->middleware('WebPermission:finance-edit');

Route::post('invoice/{uuid}/item', [AdminInvoicesController::class, 'postInvoiceItem'])->name('invoice.item.post')
    ->middleware('WebPermission:finance-edit');
Route::put('invoice/{uuid}/item/{item_uuid}', [AdminInvoicesController::class, 'putInvoiceItem'])->name('invoice.item.put')
    ->middleware('WebPermission:finance-edit');
Route::delete('invoice/{uuid}/item/{item_uuid}', [AdminInvoicesController::class, 'deleteInvoiceItem'])->name('invoice.item.delete')
    ->middleware('WebPermission:finance-edit');
Route::post('invoice/{uuid}/add_payment',
    [AdminInvoicesController::class, 'postInvoiceAddPayment'])->name('invoice.add_payment.post')
    ->middleware('WebPermission:finance-edit');
Route::post('invoice/{uuid}/make_refund',
    [AdminInvoicesController::class, 'postInvoiceMakeRefund'])->name('invoice.make_refund.post')
    ->middleware('WebPermission:finance-edit');

Route::get('invoice/{uuid}/pdf', [AdminInvoicesController::class, 'getInvoicePdf'])->name('invoice.pdf.get')
    ->middleware('WebPermission:finance-view');

Route::get('invoice/{uuid}/payment_gateways/select',
    [AdminInvoicesController::class, 'getInvoicePaymentGatewaysSelect'])->name('invoice.payment_gateways.select.get')
    ->middleware('WebPermission:finance-view');

// Payment Gateway CRUD
Route::get('payment_gateway/{uuid}', [AdminPaymentGatewaysController::class, 'getPaymentGateway'])->name('payment_gateway.get')
    ->middleware('WebPermission:finance-view');
Route::put('payment_gateway/{uuid}', [AdminPaymentGatewaysController::class, 'putPaymentGateway'])->name('payment_gateway.put')
    ->middleware('WebPermission:finance-edit');
Route::delete('payment_gateway/{uuid}',
    [AdminPaymentGatewaysController::class, 'deletePaymentGateway'])->name('payment_gateway.delete')
    ->middleware('WebPermission:finance-edit');

Route::post('payment_gateway/update_order',
    [AdminPaymentGatewaysController::class, 'postPaymentGatewayUpdateOrder'])->name('payment_gateway.update_order.post')
    ->middleware('WebPermission:finance-edit');

// **********************************************************************************************************************
Route::get('notification_categories/select', [
    AdminNotificationsController::class, 'getNotificationCategoriesSelect',
])->name('notification_categories.select.get');
Route::get('notification_category/notifications/select', [
    AdminNotificationsController::class, 'getNotificationCategoryNotificationsSelect',
])->name('notification_category_notifications.select.get');
Route::get('notification_senders/select',
    [AdminNotificationsController::class, 'getNotificationSendersSelect'])->name('notification_senders.select.get');
Route::get('notification_layouts/select',
    [AdminNotificationsController::class, 'getNotificationLayoutsSelect'])->name('notification_layouts.select.get');
Route::get('notification_templates/select',
    [AdminNotificationsController::class, 'getNotificationTemplatesSelect'])->name('notification_templates.select.get');

Route::get('currencies/select', [AdminSettingsController::class, 'getCurrenciesSelect'])->name('currencies.select.get');

Route::get('countries/select', [AdminSettingsController::class, 'getCountriesSelect'])->name('countries.select.get');
Route::get('regions/select', [AdminSettingsController::class, 'getRegionsSelect'])->name('regions.select.get');

Route::get('clients/select', [AdminClientsController::class, 'getClientsSelect'])->name('clients.select.get');
Route::get('users/select', [AdminUsersController::class, 'getUsersSelect'])->name('users.select.get');

Route::get('user/permissions/select',
    [AdminUsersController::class, 'getUserPermissionsSelect'])->name('user.permissions.select.get');

Route::get('price/periods/select',
    [AdminProductsController::class, 'getPricePeriods'])->name('price.periods.select.get');

Route::get('products/select', [AdminProductsController::class, 'getProductsSelect'])->name('products.select.get');
Route::get('product/prices/select',
    [AdminProductsController::class, 'getProductPricesSelect'])->name('product.prices.select.get');

Route::get('home_companies/select',
    [AdminHomeCompaniesController::class, 'getHomeCompaniesSelect'])->name('home_companies.select.get');

Route::post('file/image/upload', [FileController::class, 'uploadImages'])->name('file.image.upload');
Route::post('file/image/delete', [FileController::class, 'deleteImages'])->name('file.image.delete');

// Load Admin Template Dynamic Routes
$templateRoutes = config('template.admin.base_path').'/Routes/admin_zone_api.php';
try {
    if (file_exists($templateRoutes)) {
        Route::prefix('admin_template/')
            ->name('admin_template.')
            ->group($templateRoutes);
    }
} catch (\Exception $e) {
    Log::error('Error connecting admin template routes file: '.$e->getMessage());
}
// Load Client Template Dynamic Routes
$templateRoutes = config('template.client.base_path').'/Routes/admin_zone_api.php';
try {
    if (file_exists($templateRoutes)) {
        Route::prefix('client_template/')
            ->name('client_template.')
            ->group($templateRoutes);
    }
} catch (\Exception $e) {
    Log::error('Error connecting client template routes file: '.$e->getMessage());
}
// Load Modules Dynamic Routes
loadApiModulesDynamicRoutes();

