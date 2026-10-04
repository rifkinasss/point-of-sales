<?php

use Furic\FilamentRedeemCodes\Models\RedeemRewardType;

return [

    /*
    |--------------------------------------------------------------------------
    | Code generation
    |--------------------------------------------------------------------------
    |
    | length    Total length of generated codes including any prefix.
    | alphabet  Character pool. Default omits 0/1/O/I/L for OCR/print clarity.
    |
    */
    'code' => [
        'length' => 12,
        'alphabet' => '23456789ABCDEFGHJKLMNPQRSTUVWXYZ',
    ],

    /*
    |--------------------------------------------------------------------------
    | Reward type binding
    |--------------------------------------------------------------------------
    |
    | Bind your host app's reward type definition here. Two flavours work:
    |
    |  1. A backed enum implementing
    |     Furic\FilamentRedeemCodes\Contracts\RewardType — compile-time safe,
    |     code-version-controlled.  Example:
    |       'reward_type' => App\Enums\RewardType::class
    |
    |  2. An Eloquent model implementing the same contract — admins manage
    |     the list at runtime via a Filament Resource. Bind the bundled
    |     Furic\FilamentRedeemCodes\Models\RedeemRewardType, or your own
    |     model that implements RewardType + has `key` and `label` columns.
    |     Example:
    |       'reward_type' => Furic\FilamentRedeemCodes\Models\RedeemRewardType::class
    |
    | When null, types are stored/returned as raw strings (free-text).
    |
    */
    'reward_type' => RedeemRewardType::class,

    /*
    |--------------------------------------------------------------------------
    | Public redemption API
    |--------------------------------------------------------------------------
    */
    'api' => [
        'enabled' => false,
        'prefix' => 'api',
        'middleware' => ['api', 'throttle:redeem-codes'],

        // Format: "<attempts>,<minutes>" — applied per request IP.
        'rate_limit' => '10,1',
    ],

    /*
    |--------------------------------------------------------------------------
    | Filament panel
    |--------------------------------------------------------------------------
    */
    'filament' => [
        'navigation_group' => 'Promosi',
        'navigation_icon' => 'heroicon-o-ticket',
    ],

    /*
    |--------------------------------------------------------------------------
    | Table names
    |--------------------------------------------------------------------------
    */
    'table_names' => [
        'campaigns' => 'redeem_campaigns',
        'codes' => 'redeem_codes',
        'rewards' => 'redeem_code_rewards',
        'histories' => 'redeem_code_histories',
        'reward_types' => 'redeem_reward_types',
    ],
];
