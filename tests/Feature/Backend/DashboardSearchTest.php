<?php

namespace Tests\Feature\Backend;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\UserActivity;

class DashboardSearchTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin()
    {
        return User::factory()->create(['role' => 'admin', 'is_active' => true]);
    }

    public function test_admin_can_search_dashboard_logs()
    {
        $admin = $this->createAdmin();
        $user1 = User::factory()->create(['name' => 'Jan Kowalski', 'email' => 'jan@kowalski.pl']);
        $user2 = User::factory()->create(['name' => 'Anna Nowak', 'email' => 'anna@nowak.pl']);

        UserActivity::create([
            'user_id' => $user1->id,
            'action' => 'login_success',
            'description' => 'Zalogowano pomyślnie z IP 1.2.3.4',
            'ip_address' => '1.2.3.4',
        ]);

        UserActivity::create([
            'user_id' => $user2->id,
            'action' => 'update_profile',
            'description' => 'Zaktualizowano profil użytkownika',
            'ip_address' => '5.6.7.8',
        ]);

        // 1. Search by action
        $response = $this->actingAs($admin)->get(route('dashboard', ['search' => 'login_success']));
        $response->assertStatus(200);
        $response->assertSee('login_success');
        $response->assertDontSee('update_profile');

        // 2. Search by description
        $response = $this->actingAs($admin)->get(route('dashboard', ['search' => 'Zalogowano']));
        $response->assertStatus(200);
        $response->assertSee('login_success');
        $response->assertDontSee('update_profile');

        // 3. Search by IP
        $response = $this->actingAs($admin)->get(route('dashboard', ['search' => '5.6.7.8']));
        $response->assertStatus(200);
        $response->assertSee('update_profile');
        $response->assertDontSee('login_success');

        // 4. Search by User Name
        $response = $this->actingAs($admin)->get(route('dashboard', ['search' => 'Jan Kowalski']));
        $response->assertStatus(200);
        $response->assertSee('login_success');
        $response->assertDontSee('update_profile');
    }
}
