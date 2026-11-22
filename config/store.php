<?php

return [
    'name' => env('STORE_NAME', env('APP_NAME', 'Tokoku')),
    'address' => env('STORE_ADDRESS', ''),
    'logo' => env('STORE_LOGO') ? public_path(env('STORE_LOGO')) : null,
];
