<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Màn hình đăng ký có thể hiển thị thành công.
     */
    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
        $response->assertSee('Tạo tài khoản mới');
    }

    /**
     * Đăng ký hợp lệ thành công và role luôn luôn là member.
     */
    public function test_new_users_can_register_and_role_is_always_member(): void
    {
        $response = $this->post('/register', [
            'name' => 'Nguyen Van A',
            'email' => 'nguyenvana@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'name' => 'Nguyen Van A',
            'email' => 'nguyenvana@example.com',
            'role' => UserRole::MEMBER->value,
        ]);

        $user = User::where('email', 'nguyenvana@example.com')->first();
        $this->assertSame(UserRole::MEMBER, $user->role);
        $this->assertFalse($user->isAdmin());

        $response->assertRedirect(route('dashboard'));
    }

    /**
     * Xác nhận mật khẩu không khớp hoặc dữ liệu thiếu bị từ chối.
     */
    public function test_registration_validation_rules(): void
    {
        $response = $this->post('/register', [
            'name' => '',
            'email' => 'not-an-email',
            'password' => 'short',
            'password_confirmation' => 'mismatch',
        ]);

        $response->assertSessionHasErrors(['name', 'email', 'password']);
        $this->assertGuest();
    }

    /**
     * Không thể đăng ký với email đã trùng lặp.
     */
    public function test_registration_fails_with_duplicate_email(): void
    {
        User::factory()->create([
            'email' => 'existing@example.com',
        ]);

        $response = $this->post('/register', [
            'name' => 'Another User',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
    }

    /**
     * Người dùng tự gửi role=admin khi đăng ký không nâng được quyền.
     */
    public function test_registration_does_not_allow_role_elevation(): void
    {
        $response = $this->post('/register', [
            'name' => 'Attacker User',
            'email' => 'attacker@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'admin',
        ]);

        $this->assertAuthenticated();

        $user = User::where('email', 'attacker@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame(UserRole::MEMBER, $user->role);
        $this->assertFalse($user->isAdmin());
    }

    /**
     * Màn hình đăng nhập có thể hiển thị thành công.
     */
    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('Đăng nhập');
    }

    /**
     * Người dùng đăng nhập đúng thông tin thành công.
     */
    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create([
            'email' => 'testuser@example.com',
            'password' => Hash::make('secret123'),
        ]);

        $response = $this->post('/login', [
            'email' => 'testuser@example.com',
            'password' => 'secret123',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard'));
    }

    /**
     * Người dùng đăng nhập sai mật khẩu bị từ chối.
     */
    public function test_users_cannot_authenticate_with_invalid_password(): void
    {
        User::factory()->create([
            'email' => 'testuser@example.com',
            'password' => Hash::make('secret123'),
        ]);

        $response = $this->post('/login', [
            'email' => 'testuser@example.com',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    /**
     * Giới hạn tần suất đăng nhập (Rate limiting) sau 5 lần thử thất bại.
     */
    public function test_login_is_rate_limited_after_too_many_failed_attempts(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'email' => 'ratelimit@example.com',
                'password' => 'wrong-pass',
            ]);
        }

        $response = $this->post('/login', [
            'email' => 'ratelimit@example.com',
            'password' => 'wrong-pass',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    /**
     * Người dùng đã đăng nhập có thể đăng xuất thành công qua POST.
     */
    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect(route('home'));
    }

    /**
     * Khách không vào được dashboard và bị chuyển hướng về login.
     */
    public function test_unauthenticated_users_cannot_access_dashboard(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect(route('login'));
    }

    /**
     * Khách không vào được trang quản trị và bị chuyển hướng về login.
     */
    public function test_unauthenticated_users_cannot_access_admin(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect(route('login'));
    }

    /**
     * Người dùng đã đăng nhập có thể truy cập dashboard cá nhân.
     */
    public function test_authenticated_users_can_access_dashboard(): void
    {
        $user = User::factory()->create([
            'name' => 'Tran Thi B',
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Xin chào, Tran Thi B!');
    }
}
