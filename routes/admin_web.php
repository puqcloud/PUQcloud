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
use App\Http\Controllers\Admin\AdminAutomationController;
use App\Http\Controllers\Admin\AdminClientsController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AdminDnsManagerController;
use App\Http\Controllers\Admin\AdminHomeCompaniesController;
use App\Http\Controllers\Admin\AdminTaxRulesController;
use App\Http\Controllers\Admin\AdminTicketDepartmentsController;
use App\Http\Controllers\Admin\AdminTicketsController;
use App\Http\Controllers\Admin\AdminTicketEmailBlacklistController;
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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

Route::get('', [AdminWidgetsController::class, 'dashboard'])->name('dashboard');

Route::get('my_account', [AdminMyAccountController::class, 'myAccount'])->name('my_account');

Route::get('my_account/notifications', [AdminMyAccountController::class, 'myNotifications'])->name('my_account.notifications');

Route::get('admins', [AdminAdminsController::class, 'admins'])->name('admins')
    ->middleware('WebPermission:admins-view');

Route::get('admin/{uuid}', [AdminAdminsController::class, 'admin'])->name('admin')
    ->middleware('WebPermission:admins-view');

Route::get('admin_session_logs', [AdminLogController::class, 'adminSessions'])->name('admin_session_logs')
    ->middleware('WebPermission:admin-session-log-view');

Route::get('client_session_logs', [AdminLogController::class, 'clientSessions'])->name('client_session_logs')
    ->middleware('WebPermission:client-session-log-view');

Route::get('task_queue', [AdminTaskController::class, 'taskQueue'])->name('task_queue')
    ->middleware('WebPermission:task-queue-view');

Route::get('activity_logs', [AdminLogController::class, 'activityLogs'])->name('activity_logs')
    ->middleware('WebPermission:activity-log-view');

Route::get('module_logs', [AdminLogController::class, 'moduleLogs'])->name('module_logs')
    ->middleware('WebPermission:module-log-view');

Route::get('admin_api_keys', [AdminAdminApiKeysController::class, 'adminApiKeys'])->name('admin_api_keys')
    ->middleware('WebPermission:admin-api-keys-view');

Route::get('groups', [AdminGroupsController::class, 'groups'])->name('groups')
    ->middleware('WebPermission:groups-view');

Route::get('group/{uuid}', [AdminGroupsController::class, 'group'])->name('group')
    ->middleware('WebPermission:groups-view');

Route::get('scheduler', [AdminAutomationController::class, 'scheduler'])->name('scheduler')
    ->middleware('WebPermission:automation-scheduler-view');

Route::get('automation/horizon', function () {
    return redirect('horizon/dashboard');
})->name('automation.horizon')
    ->middleware('WebPermission:automation-horizon-management');

Route::get('notification_senders', [AdminNotificationsController::class, 'notificationSenders'])->name('notification_senders')
    ->middleware('WebPermission:notification-senders-management');

Route::get('notification_sender/{uuid}', [AdminNotificationsController::class, 'notificationSender'])->name('notification_sender')
    ->middleware('WebPermission:notification-senders-management');

Route::get('notification_layouts', [AdminNotificationsController::class, 'notificationLayouts'])->name('notification_layouts')
    ->middleware('WebPermission:notification-layouts-management');

Route::get('notification_layout/{uuid}', [AdminNotificationsController::class, 'notificationLayout'])->name('notification_layout')
    ->middleware('WebPermission:notification-layouts-management');

Route::get('notification_templates', [AdminNotificationsController::class, 'notificationTemplates'])->name('notification_templates')
    ->middleware('WebPermission:notification-templates-management');

Route::get('notification_template/{uuid}', [AdminNotificationsController::class, 'notificationTemplate'])->name('notification_template')
    ->middleware('WebPermission:notification-templates-management');

Route::get('notification_histories', [AdminNotificationsController::class, 'notificationHistories'])->name('notification_histories')
    ->middleware('WebPermission:notification-history-view');


Route::get('dns_server_groups', [AdminDnsManagerController::class, 'dnsServerGroups'])->name('dns_server_groups')
    ->middleware('WebPermission:dns-manager-dns-server-groups');

Route::get('dns_server_group/{uuid}', [AdminDnsManagerController::class, 'dnsServerGroup'])->name('dns_server_group')
    ->middleware('WebPermission:dns-manager-dns-server-groups');

Route::get('dns_servers', [AdminDnsManagerController::class, 'dnsServers'])->name('dns_servers')
    ->middleware('WebPermission:dns-manager-dns-servers');

Route::get('dns_server/{uuid}', [AdminDnsManagerController::class, 'dnsServer'])->name('dns_server')
    ->middleware('WebPermission:dns-manager-dns-servers');

Route::get('dns_server/{uuid}/import', [AdminDnsManagerController::class, 'dnsZoneImport'])->name('dns_server.import')
    ->middleware('WebPermission:dns-manager-dns-zones');

Route::get('dns_zones', [AdminDnsManagerController::class, 'dnsZones'])->name('dns_zones')
    ->middleware('WebPermission:dns-manager-dns-zones');

Route::get('dns_zone/{uuid}', [AdminDnsManagerController::class, 'dnsZone'])->name('dns_zone')
    ->middleware('WebPermission:dns-manager-dns-zones');

Route::get('dns_zone_templates', [AdminDnsManagerController::class, 'dnsZoneTemplates'])->name('dns_zone_templates')
    ->middleware('WebPermission:dns-manager-dns-zones');

// Support Tickets
Route::get('tickets', [AdminTicketsController::class, 'tickets'])->name('tickets')
    ->middleware('WebPermission:tickets-view');
Route::get('ticket_create', [AdminTicketsController::class, 'ticketCreate'])->name('ticket_create')
    ->middleware('WebPermission:tickets-create');
Route::get('ticket/attachment/{uuid}', [AdminTicketsController::class, 'getTicketReplyAttachmentDownload'])->name('ticket.attachment.download')
    ->middleware('WebPermission:tickets-view');
Route::get('ticket_canned_responses', [\App\Http\Controllers\Admin\AdminTicketCannedResponsesController::class, 'ticketCannedResponses'])->name('ticket_canned_responses')
    ->middleware('WebPermission:tickets-edit');
Route::get('ticket/{uuid}', [AdminTicketsController::class, 'ticket'])->name('ticket')
    ->middleware('WebPermission:tickets-view');
Route::get('ticket_departments', [AdminTicketDepartmentsController::class, 'ticketDepartments'])->name('ticket_departments')
    ->middleware('WebPermission:tickets-departments');
Route::get('ticket_department/{uuid}', [AdminTicketDepartmentsController::class, 'ticketDepartment'])->name('ticket_department')
    ->middleware('WebPermission:tickets-departments');
Route::get('ticket_statuses', [AdminTicketStatusesController::class, 'ticketStatuses'])->name('ticket_statuses')
    ->middleware('WebPermission:tickets-statuses');
Route::get('ticket_email_blacklist', [AdminTicketEmailBlacklistController::class, 'ticketEmailBlacklist'])->name('ticket_email_blacklist')
    ->middleware('WebPermission:tickets-edit');

Route::get('dns_zone_template/{uuid}', [AdminDnsManagerController::class, 'dnsZoneTemplate'])->name('dns_zone_template')
    ->middleware('WebPermission:dns-manager-dns-zones');

Route::get('certificate_authorities', [AdminSslManagerController::class, 'certificateAuthorities'])->name('certificate_authorities')
    ->middleware('WebPermission:ssl-manager-certificate-authorities');

Route::get('certificate_authority/{uuid}', [AdminSslManagerController::class, 'certificateAuthority'])->name('certificate_authority')
    ->middleware('WebPermission:ssl-manager-certificate-authorities');

Route::get('ssl_certificates', [AdminSslManagerController::class, 'sslCertificates'])->name('ssl_certificates')
    ->middleware('WebPermission:ssl-manager-ssl-certificates');

Route::get('ssl_certificate/{uuid}', [AdminSslManagerController::class, 'sslCertificate'])->name('ssl_certificate')
    ->middleware('WebPermission:ssl-manager-ssl-certificates');


Route::get('general_settings', [AdminSettingsController::class, 'generalSettings'])->name('general_settings')
    ->middleware('WebPermission:general-settings-management');

Route::get('countries', [AdminSettingsController::class, 'countries'])->name('countries')
    ->middleware('WebPermission:general-settings-management');

Route::get('currencies', [AdminSettingsController::class, 'currencies'])->name('currencies')
    ->middleware('WebPermission:currencies-management');

Route::get('clients', [AdminClientsController::class, 'clients'])->name('clients')
    ->middleware('WebPermission:clients-view');

Route::get('client/create', [AdminClientsController::class, 'clientCreate'])->name('client.create')
    ->middleware('WebPermission:clients-create');

Route::get('client/{uuid}/login_as_client_owner', [AdminClientsController::class, 'loginAsClientOwner'])->name('client.login_as_client_owner')
    ->middleware('WebPermission:clients-view');
Route::get('client/{uuid}/return_to_admin', [AdminClientsController::class, 'returnToAdmin'])->name('client.return_to_admin')
    ->middleware('WebPermission:clients-view');

Route::get('client/{uuid}/{tab}', [AdminClientsController::class, 'clientTabs'])->name('client.tab')
    ->middleware('WebPermission:clients-view');

Route::get('users', [AdminUsersController::class, 'users'])->name('users')
    ->middleware('WebPermission:users-view');

Route::get('services', [AdminServicesController::class, 'services'])->name('services')
    ->middleware('WebPermission:clients-view');


Route::get('add_ons/marketplace', [AdminAddOnsController::class, 'marketplace'])->name('add_ons.marketplace')
    ->middleware('WebPermission:add-ons-marketplace-management');

Route::get('add_ons/modules', [AdminAddOnsController::class, 'modules'])->name('add_ons.modules')
    ->middleware('WebPermission:add-ons-modules-management');

Route::get('add_ons/reports', [AdminAddOnsController::class, 'reports'])->name('add_ons.reports')
    ->middleware('WebPermission:add-ons-reports-management');


Route::get('api_documentation/admin', [AdminApiDocumentationController::class, 'admin'])->name('api_documentation.admin')
    ->middleware('WebPermission:api-documentation-admin');

Route::get('api_documentation/admin/{module}/{name}', [AdminApiDocumentationController::class, 'adminApiDocumentation'])->name('api_documentation.admin_api_documentation')
    ->middleware('WebPermission:api-documentation-admin');


Route::get('api_documentation/client', [AdminApiDocumentationController::class, 'client'])->name('api_documentation.client')
    ->middleware('WebPermission:api-documentation-client');

Route::get('api_documentation/client/{module}/{name}', [AdminApiDocumentationController::class, 'clientApiDocumentation'])->name('api_documentation.client_api_documentation')
    ->middleware('WebPermission:api-documentation-client');



Route::get('products', [AdminProductsController::class, 'products'])->name('products')
    ->middleware('WebPermission:products-management');

Route::get('product/{uuid}/{tab}', [AdminProductsController::class, 'productTab'])->name('product.tab')
    ->middleware('WebPermission:products-management');

Route::get('product_groups', [AdminProductGroupsController::class, 'productGroups'])->name('product_groups')
    ->middleware('WebPermission:product-groups-management');

Route::get('product_group/{uuid}/{tab}', [AdminProductGroupsController::class, 'productGroupTab'])->name('product_group.tab')
    ->middleware('WebPermission:product-groups-management');

Route::get('product_attribute_groups', [AdminProductAttributesController::class, 'productAttributeGroups'])->name('product_attribute_groups')
    ->middleware('WebPermission:product-attributes-management');

Route::get('product_attribute_group/{uuid}/{tab}', [AdminProductAttributesController::class, 'productAttributeGroupTab'])->name('product_attribute_group.tab')
    ->middleware('WebPermission:product-attributes-management');

Route::get('product_option_groups', [AdminProductOptionsController::class, 'productOptionGroups'])->name('product_option_groups')
    ->middleware('WebPermission:product-options-management');

Route::get('product_option_group/{uuid}/{tab}', [AdminProductOptionsController::class, 'productOptionGroupTab'])->name('product_option_group.tab')
    ->middleware('WebPermission:product-options-management');

Route::get('service/create', [AdminServicesController::class, 'serviceCreate'])->name('service.create')
    ->middleware('WebPermission:clients-edit');

Route::get('transactions', [AdminTransactionsController::class, 'transactions'])->name('transactions')
    ->middleware('WebPermission:finance-view');

Route::get('home_companies', [AdminHomeCompaniesController::class, 'homeCompanies'])->name('home_companies')
    ->middleware('WebPermission:finance-view');

Route::get('home_company/{uuid}/{tab}', [AdminHomeCompaniesController::class, 'homeCompanyTab'])->name('home_company.tab')
    ->middleware('WebPermission:finance-view');

Route::get('tax_rules', [AdminTaxRulesController::class, 'taxRules'])->name('tax_rules')
    ->middleware('WebPermission:finance-view');

Route::get('/redirect/{label}:{uuid}', [AdminController::class, 'uuidRedirect'])
    ->where([
        'label' => '[A-Za-z]+',
        'uuid' => '[0-9a-fA-F\-]{36}',
    ])
    ->name('redirect');

// Load Admin Template Dynamic Routes
$templateRoutes = config('template.admin.base_path').'/Routes/admin_zone_web.php';

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
$templateRoutes = config('template.client.base_path').'/Routes/admin_zone_web.php';

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
loadAdminModulesDynamicRoutes();

