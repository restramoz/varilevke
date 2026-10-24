<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_from_admin(): void
    {
        $this->get('/admin')->assertRedirect('/login');
    }

    public function test_non_admin_user_cannot_open_admin(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)->get('/admin')->assertForbidden();
    }

    public function test_admin_user_sees_dashboard(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->get('/admin')->assertOk()->assertSee('Dashboard');
    }

    public function test_login_rejects_wrong_password(): void
    {
        User::factory()->create([
            'email' => 'admin@varilevke.test',
            'password' => 'rahasia-panjang-123',
            'is_admin' => true,
        ]);

        $this->from('/login')->post('/login', [
            'email' => 'admin@varilevke.test',
            'password' => 'salah-total',
        ])->assertRedirect('/login')->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_login_rejects_accounts_without_admin_flag(): void
    {
        User::factory()->create([
            'email' => 'civis@varilevke.test',
            'password' => 'rahasia-panjang-123',
            'is_admin' => false,
        ]);

        $this->post('/login', [
            'email' => 'civis@varilevke.test',
            'password' => 'rahasia-panjang-123',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_admin_login_succeeds_and_lands_on_dashboard(): void
    {
        User::factory()->create([
            'email' => 'admin@varilevke.test',
            'password' => 'rahasia-panjang-123',
            'is_admin' => true,
        ]);

        $this->post('/login', [
            'email' => 'admin@varilevke.test',
            'password' => 'rahasia-panjang-123',
        ])->assertRedirect('/admin');

        $this->assertAuthenticated();
    }
}
