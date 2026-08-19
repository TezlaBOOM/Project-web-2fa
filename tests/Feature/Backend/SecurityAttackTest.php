<?php

namespace Tests\Feature\Backend;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Departament;
use App\Models\PModul;
use App\Models\POperacje;

class SecurityAttackTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin()
    {
        return User::factory()->create(['role' => 'admin', 'is_active' => true]);
    }

    private function createMod()
    {
        return User::factory()->create(['role' => 'mod', 'is_active' => true]);
    }

    private function createUser()
    {
        return User::factory()->create(['role' => 'user', 'is_active' => true]);
    }

    /**
     * Test XSS injection protection in department names.
     */
    public function test_xss_protection_in_views()
    {
        $admin = $this->createAdmin();

        // Create department with malicious script tag
        $maliciousName = "<script>alert('xss-dept')</script>";
        Departament::create([
            'Nazwa' => $maliciousName,
            'Description' => 'Test XSS'
        ]);

        $response = $this->actingAs($admin)->get(route('departments.index'));
        $response->assertStatus(200);
        
        $html = $response->getContent();
        // Assert that the raw script tag is NOT present
        $this->assertStringNotContainsString("<script>alert('xss-dept')</script>", $html);
        // Assert that it is properly HTML entities encoded
        $this->assertStringContainsString("&lt;script&gt;alert(&#039;xss-dept&#039;)&lt;/script&gt;", $html);
    }

    /**
     * Test SQL Injection protection in search parameters.
     */
    public function test_sql_injection_protection_in_search()
    {
        $admin = $this->createAdmin();
        
        // Create some users
        User::factory()->create(['name' => 'John Doe', 'email' => 'john@test.com']);
        User::factory()->create(['name' => 'Jane Smith', 'email' => 'jane@test.com']);

        // Try SQL injection payload in search query
        $sqliPayload = "' OR '1'='1";
        $response = $this->actingAs($admin)->get(route('users.index', ['search' => $sqliPayload]));
        $response->assertStatus(200);
        
        // It should search literally for the payload, so it should not find the users
        $response->assertDontSee('John Doe');
        $response->assertDontSee('Jane Smith');
    }

    /**
     * Test unauthorized access / Privilege Escalation.
     */
    public function test_privilege_escalation_by_regular_user()
    {
        $user = $this->createUser();

        // Regular user cannot view user list
        $response = $this->actingAs($user)->get(route('users.index'));
        $response->assertStatus(403);

        // Regular user cannot view departments
        $response = $this->actingAs($user)->get(route('departments.index'));
        $response->assertStatus(403);

        // Regular user cannot view modules
        $response = $this->actingAs($user)->get(route('modules.index'));
        $response->assertStatus(403);

        // Regular user cannot view operations
        $response = $this->actingAs($user)->get(route('operations.index'));
        $response->assertStatus(403);
    }

    /**
     * Test unauthorized actions by moderator (e.g. creating/deleting users/departments).
     */
    public function test_privilege_escalation_by_moderator()
    {
        $mod = $this->createMod();
        $targetUser = $this->createUser();
        $dept = Departament::create(['Nazwa' => 'Mod Test Dept']);

        // Mod cannot create users
        $response = $this->actingAs($mod)->post(route('users.store'), [
            'name' => 'New User',
            'email' => 'newuser@test.com',
            'password' => 'secret123',
            'role' => 'user',
            'is_active' => true
        ]);
        $response->assertStatus(403);

        // Mod cannot delete users
        $response = $this->actingAs($mod)->delete(route('users.destroy', $targetUser));
        $response->assertStatus(403);

        // Mod cannot create departments
        $response = $this->actingAs($mod)->post(route('departments.store'), [
            'Nazwa' => 'Invalid Dept'
        ]);
        $response->assertStatus(403);

        // Mod cannot delete departments
        $response = $this->actingAs($mod)->delete(route('departments.destroy', $dept));
        $response->assertStatus(403);
    }

    /**
     * Test brute force protection rate limiting (throttle) on login.
     */
    public function test_login_brute_force_throttling()
    {
        // Make 10 failed login attempts
        // The limit is throttle:10,1 (10 attempts per minute)
        for ($i = 0; $i < 10; $i++) {
            $response = $this->post(route('login.post'), [
                'email' => 'nonexistent@test.com',
                'password' => 'wrongpass'
            ]);
            // Redirects back with validation errors
            $response->assertStatus(302);
        }

        // The 11th attempt should trigger 429 Too Many Requests
        $response = $this->post(route('login.post'), [
            'email' => 'nonexistent@test.com',
            'password' => 'wrongpass'
        ]);
        $response->assertStatus(429);
    }
}
