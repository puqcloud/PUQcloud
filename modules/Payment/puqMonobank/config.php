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

return [
    'name' => 'PUQ Monobank',
    'description' => 'Monobank Payment Gateway Module for Ukrainian market with support for cards, Apple Pay, Google Pay, and QR codes',
    'version' => '1.0.0',
    'author' => 'PUQ sp. z o.o.',
    'email' => 'support@puqcloud.com',
    'website' => 'https://puqcloud.com/',
    'logo' => __DIR__.'/views/assets/img/logo.png',
    'icon' => 'fas fa-credit-card',
    'supported_currencies' => ['UAH'],
    'api_version' => '1.0',
    'requires_ssl' => true,
    'supports_webhook' => true,
    'supports_iframe' => true,
    'supports_refunds' => true,
    'supports_holds' => true,
]; 