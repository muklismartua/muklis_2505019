<?php

return [
    'name' => env('STORE_NAME', env('APP_NAME', 'Tokoku')),
    'address' => env('STORE_ADDRESS', ''),
    'phone' => env('STORE_PHONE', ''),
    'logo' => env('STORE_LOGO') ? public_path(env('STORE_LOGO')) : null,
    'prefix_pembelian' => env('PURCHASE_NUMBER_PREFIX', 'PB'),
    'prefix_penjualan' => env('SALE_NUMBER_PREFIX', 'PJ'),
    'default_tax_rate' => env('STORE_DEFAULT_TAX_RATE', '0'),
];
