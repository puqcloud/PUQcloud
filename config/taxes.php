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
return [
    'Canada' => [
        'Ontario' => [
            ['name' => 'HST', 'rate' => 13.0],
        ],
        'British Columbia' => [
            ['name' => 'GST', 'rate' => 5.0],
            ['name' => 'PST', 'rate' => 7.0],
        ],
        'Quebec' => [
            ['name' => 'GST', 'rate' => 5.0],
            ['name' => 'QST', 'rate' => 9.975],
        ],
        'Alberta' => [
            ['name' => 'GST', 'rate' => 5.0],
        ],
        'Manitoba' => [
            ['name' => 'GST', 'rate' => 5.0],
            ['name' => 'PST', 'rate' => 7.0],
        ],
        'Saskatchewan' => [
            ['name' => 'GST', 'rate' => 5.0],
            ['name' => 'PST', 'rate' => 6.0],
        ],
        'Nova Scotia' => [
            ['name' => 'HST', 'rate' => 15.0],
        ],
        'New Brunswick' => [
            ['name' => 'HST', 'rate' => 15.0],
        ],
        'Prince Edward Island' => [
            ['name' => 'HST', 'rate' => 15.0],
        ],
        'Newfoundland and Labrador' => [
            ['name' => 'HST', 'rate' => 15.0],
        ],
    ],
    'EU' => [
        'AT' => [
            'rate' => 20,
            'name' => 'VAT',
        ],
        'BE' => [
            'rate' => 21,
            'name' => 'VAT',
        ],
        'BG' => [
            'rate' => 20,
            'name' => 'VAT',
        ],
        'HR' => [
            'rate' => 25,
            'name' => 'VAT',
        ],
        'CY' => [
            'rate' => 19,
            'name' => 'VAT',
        ],
        'CZ' => [
            'rate' => 21,
            'name' => 'VAT',
        ],
        'DK' => [
            'rate' => 25,
            'name' => 'VAT',
        ],
        'EE' => [
            'rate' => 20,
            'name' => 'VAT',
        ],
        'FI' => [
            'rate' => 24,
            'name' => 'VAT',
        ],
        'FR' => [
            'rate' => 20,
            'name' => 'TVA',
        ],
        'DE' => [
            'rate' => 19,
            'name' => 'MwSt.',
        ],
        'GR' => [
            'rate' => 24,
            'name' => 'ΦΠΑ',
        ],
        'HU' => [
            'rate' => 27,
            'name' => 'ÁFA',
        ],
        'IE' => [
            'rate' => 23,
            'name' => 'VAT',
        ],
        'IT' => [
            'rate' => 22,
            'name' => 'IVA',
        ],
        'LV' => [
            'rate' => 21,
            'name' => 'PVN',
        ],
        'LT' => [
            'rate' => 21,
            'name' => 'PVM',
        ],
        'LU' => [
            'rate' => 17,
            'name' => 'TVA',
        ],
        'MT' => [
            'rate' => 18,
            'name' => 'VAT',
        ],
        'NL' => [
            'rate' => 21,
            'name' => 'BTW',
        ],
        'PL' => [
            'rate' => 23,
            'name' => 'VAT',
        ],
        'PT' => [
            'rate' => 23,
            'name' => 'IVA',
        ],
        'RO' => [
            'rate' => 19,
            'name' => 'TVA',
        ],
        'SK' => [
            'rate' => 20,
            'name' => 'DPH',
        ],
        'SI' => [
            'rate' => 22,
            'name' => 'DDV',
        ],
        'ES' => [
            'rate' => 21,
            'name' => 'IVA',
        ],
        'SE' => [
            'rate' => 25,
            'name' => 'MOMS',
        ],
    ],
];
