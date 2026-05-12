<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            ['key' => 'min_password_length', 'value' => '8'],
            ['key' => 'max_password_length', 'value' => '32'],
            ['key' => 'require_special_character', 'value' => '0'],
            ['key' => 'enable_2fa', 'value' => '0'],
            ['key' => 'smtp_host', 'value' => 'smtp.mailtrap.io'],
            ['key' => 'smtp_port', 'value' => '2525'],
            ['key' => 'smtp_username', 'value' => ''],
            ['key' => 'smtp_password', 'value' => ''],
            ['key' => 'smtp_encryption', 'value' => 'tls'],
            ['key' => 'smtp_from_address', 'value' => 'no-reply@example.com'],
            ['key' => 'smtp_from_name', 'value' => 'System Uwierzytelniania'],
        ];

        foreach ($settings as $setting) {
            \App\Models\Setting::updateOrCreate(
                ['key' => $setting['key']],
                ['value' => $setting['value']]
            );
        }
    }
}
