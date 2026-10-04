<?php

return [
    'manual_qris' => [
        'image_url' => env('MANUAL_QRIS_IMAGE_URL'),
        'merchant_name' => env('MANUAL_QRIS_MERCHANT_NAME', 'Merchant QRIS'),
    ],

    'manual_transfer' => [
        'bank_name' => env('MANUAL_TRANSFER_BANK_NAME'),
        'account_number' => env('MANUAL_TRANSFER_ACCOUNT_NUMBER'),
        'account_holder' => env('MANUAL_TRANSFER_ACCOUNT_HOLDER'),
    ],
];
