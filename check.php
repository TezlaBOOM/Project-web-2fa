<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$settings = \App\Models\Setting::pluck('value', 'key')->toArray();
$global2faEnabled = isset($settings['enable_2fa']) ? (bool) $settings['enable_2fa'] : false;

$user = \App\Models\User::first();
echo "Settings: " . json_encode($settings) . "\n";
echo "global2faEnabled: " . var_export($global2faEnabled, true) . "\n";
if ($user) {
    echo "User 2FA enabled: " . var_export($user->two_factor_enabled, true) . "\n";
} else {
    echo "No user found.\n";
}
