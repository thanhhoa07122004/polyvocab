<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Thành viên (member) truy cập trang quản trị bị từ chối với HTTP 403 Forbidden.
     */
    public function test_member_cannot_access_admin_and_receives_403(): void
    {
        $member = User::factory()->create([
            'role' => UserRole::MEMBER,
        ]);

        $response = $this->actingAs($member)->get('/admin');

        $response->assertStatus(403);
    }

    /**
     * Quản trị viên (admin) truy cập được trang quản trị.
     */
    public function test_admin_can_access_admin_dashboard(): void
    {
        $admin = User::factory()->admin()->create([
            'name' => 'Admin Boss',
        ]);

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertStatus(200);
        $response->assertSee('Bảng điều khiển Quản trị viên');
        $response->assertSee('Admin Boss');
    }

    /**
     * Chỉ hiển thị liên kết quản trị cho người có quyền admin.
     */
    public function test_admin_link_visibility(): void
    {
        $member = User::factory()->create([
            'role' => UserRole::MEMBER,
        ]);

        $memberResponse = $this->actingAs($member)->get('/dashboard');
        $memberResponse->assertDontSee('Trang quản trị');

        $admin = User::factory()->admin()->create();
        $adminResponse = $this->actingAs($admin)->get('/dashboard');
        $adminResponse->assertSee('Quản trị');
    }

    /**
     * Command cấp quyền admin thành công khi người dùng xác nhận.
     */
    public function test_promote_admin_command_succeeds_when_confirmed(): void
    {
        $user = User::factory()->create([
            'email' => 'target@example.com',
            'role' => UserRole::MEMBER,
        ]);

        $this->artisan('admin:promote target@example.com')
            ->expectsConfirmation('Bạn có chắc chắn muốn cấp quyền Quản trị viên (admin) cho tài khoản [target@example.com] không?', 'yes')
            ->assertExitCode(0);

        $this->assertSame(UserRole::ADMIN, $user->fresh()->role);
        $this->assertTrue($user->fresh()->isAdmin());
    }

    /**
     * Command cấp quyền admin bị hủy khi người dùng từ chối xác nhận.
     */
    public function test_promote_admin_command_cancels_when_declined(): void
    {
        $user = User::factory()->create([
            'email' => 'declined@example.com',
            'role' => UserRole::MEMBER,
        ]);

        $this->artisan('admin:promote declined@example.com')
            ->expectsConfirmation('Bạn có chắc chắn muốn cấp quyền Quản trị viên (admin) cho tài khoản [declined@example.com] không?', 'no')
            ->assertExitCode(0);

        $this->assertSame(UserRole::MEMBER, $user->fresh()->role);
    }

    /**
     * Command cấp quyền báo lỗi khi email không tồn tại và không tự ý tạo tài khoản.
     */
    public function test_promote_admin_command_fails_when_email_does_not_exist(): void
    {
        $initialUserCount = User::count();

        $this->artisan('admin:promote nonexistent@example.com')
            ->assertExitCode(1);

        $this->assertSame($initialUserCount, User::count());
    }
}
