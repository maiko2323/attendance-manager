<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    //メールアドレスが未入力の場合、エラーメッセージが表示される
    public function test_email_is_required_for_admin_login()
    {
        $response = $this->post('/login', [
            'login_type' => 'admin',
            'email' => '',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);
    }

    //パスワードが未入力の場合、エラーメッセージが表示される
    public function test_password_is_required_for_admin_login()
    {
        $response = $this->post('/login', [
            'login_type' => 'admin',
            'email' => 'admin@example.com',
            'password' => '',
        ]);

        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);
    }

    //登録内容と一致しない場合、エラーメッセージが表示される
    public function test_admin_login_fails_with_invalid_credentials()
    {
        User::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);

        $response = $this->post('/login', [
            'login_type' => 'admin',
            'email' => 'admin@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません',
        ]);
    }
}