<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;

class PromoteAdminCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:promote {email? : Địa chỉ email của tài khoản cần cấp quyền quản trị}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cấp quyền quản trị viên (admin) cho tài khoản người dùng đã tồn tại';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->argument('email');

        if (! $email) {
            $email = $this->ask('Vui lòng nhập địa chỉ email của tài khoản cần cấp quyền quản trị viên:');
        }

        if (empty($email)) {
            $this->error('Địa chỉ email không được để trống.');
            return self::FAILURE;
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("Không tìm thấy tài khoản với email [{$email}]. Vui lòng kiểm tra lại.");
            return self::FAILURE;
        }

        if ($user->role === UserRole::ADMIN) {
            $this->warn("Tài khoản [{$user->name}] ({$user->email}) đã có vai trò Quản trị viên (admin) từ trước.");
            return self::SUCCESS;
        }

        $this->info('--- Thông tin tài khoản đích ---');
        $this->table(
            ['ID', 'Họ và tên', 'Email', 'Vai trò hiện tại', 'Ngày đăng ký'],
            [[
                $user->id,
                $user->name,
                $user->email,
                $user->role->label(),
                $user->created_at ? $user->created_at->format('d/m/Y H:i') : 'N/A',
            ]]
        );

        if (! $this->confirm("Bạn có chắc chắn muốn cấp quyền Quản trị viên (admin) cho tài khoản [{$user->email}] không?", false)) {
            $this->info('Đã hủy thao tác cấp quyền.');
            return self::SUCCESS;
        }

        $user->role = UserRole::ADMIN;
        $user->save();

        $this->info("Đã cấp quyền Quản trị viên (admin) thành công cho tài khoản [{$user->name}] ({$user->email})!");

        return self::SUCCESS;
    }
}
