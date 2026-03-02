<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\VerifyEmail;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'テスト太郎',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ], $overrides);
    }

    //名前が未入力の場合、エラーメッセージが表示される
    public function test_name_is_required()
    {
        $response = $this->post('/register', $this->validPayload(['name' => '']));

        $response->assertSessionHasErrors([
            'name' => 'お名前を入力してください',
        ]);
    }

    //メールアドレスが未入力の場合、エラーメッセージが表示される
    public function test_email_is_required()
    {
        $response = $this->post('/register', $this->validPayload(['email' => '']));

        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);
    }

    //パスワードが7文字未満の場合、エラーメッセージが表示される
    public function test_password_must_be_at_least_8_characters()
    {
        $response = $this->post('/register', $this->validPayload([
            'password' => '1234567',
            'password_confirmation' => '1234567',
        ]));

        $response->assertSessionHasErrors([
            'password' => 'パスワードは8文字以上で入力してください',
        ]);
    }

    //パスワードが一致しない場合、エラーメッセージが表示される
    public function test_password_confirmation_must_match()
    {
        $response = $this->post('/register', $this->validPayload([
            'password' => 'password123',
            'password_confirmation' => 'different123',
        ]));

        $response->assertSessionHasErrors([
            'password' => 'パスワードと一致しません',
        ]);
    }

    //パスワードが未入力の場合、エラーメッセージが表示される
    public function test_password_is_required()
    {
        $response = $this->post('/register', $this->validPayload([
            'password' => '',
            'password_confirmation' => '',
        ]));

        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);
    }

    //フォームに内容が入力されていた場合、データが正常に保存される
    public function test_user_is_created_when_valid_data_is_submitted()
    {
        $response = $this->post('/register', $this->validPayload());

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'name'  => 'テスト太郎',
        ]);

        $response->assertStatus(302);
    }
}