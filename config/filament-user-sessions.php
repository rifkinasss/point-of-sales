<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Sessions table
    |--------------------------------------------------------------------------
    |
    | This plugin only works when SESSION_DRIVER=database, since that is
    | the only driver that stores sessions in a queryable table.
    |
    */
    'sessions_table' => 'sessions',

    /*
    |--------------------------------------------------------------------------
    | Online threshold (minutes)
    |--------------------------------------------------------------------------
    |
    | A session is considered "online now" if its last activity happened
    | within this many minutes.
    |
    */
    'online_threshold_minutes' => 5,

    /*
    |--------------------------------------------------------------------------
    | Known devices table
    |--------------------------------------------------------------------------
    |
    | Unlike the sessions table, this one persists across logout/expiry, so
    | we can tell whether a device has been seen for a user before. Used to
    | flag sessions from a device seen only recently (see below).
    |
    */
    'known_devices_table' => 'session_known_devices',

    /*
    |--------------------------------------------------------------------------
    | New device window (hours)
    |--------------------------------------------------------------------------
    |
    | A session is flagged as a "new device" if its browser/platform
    | combination was first seen for that user within this many hours.
    |
    */
    'new_device_window_hours' => 24,

    /*
    |--------------------------------------------------------------------------
    | Notify on new device
    |--------------------------------------------------------------------------
    |
    | When enabled, the recipients below are notified the first time a
    | device/browser is ever seen for a user's account — not on every login
    | while it's still within the "new" window above.
    |
    */
    'notify_on_new_device' => true,

    /*
    |--------------------------------------------------------------------------
    | New device notification recipients
    |--------------------------------------------------------------------------
    |
    | The package has no way to know who your admins/security team are, so
    | you must provide a class implementing
    | Smony\FilamentUserSessions\Contracts\ResolvesNewDeviceRecipients to
    | decide who gets notified. Leave null to disable (default — nothing is
    | sent until you configure this).
    |
    | Example:
    |
    | 'new_device_notification_recipients' => \App\Notifications\SecurityTeamRecipients::class,
    |
    */
    'new_device_notification_recipients' => null,

    /*
    |--------------------------------------------------------------------------
    | New device notification channels
    |--------------------------------------------------------------------------
    |
    | Any channel supported by Laravel notifications, e.g. ['mail'],
    | ['database'], or both. 'database' requires the notifications table
    | (php artisan notifications:table) and the recipient models to be
    | Notifiable.
    |
    */
    'new_device_notification_channels' => ['mail'],
];
