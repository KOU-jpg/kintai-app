<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules(){
    return [
        'name' => 'required',
        'email' => 'required|email|unique:users,email',
        'password' => 'required|min:8|confirmed',
        'password_confirmation' => 'required|same:password',
    ];}
    public function messages()  {
    return [
      'name.required' => 'お名前を入力してください',
      'email.required' => 'メールアドレスを入力してください',
      'email.email' => 'メールアドレス形式で入力してください',
      'email.unique' => 'このメールアドレスは既に登録されています',
      'password.required' => 'パスワードを入力してください',
      'password.min' => ' パスワードは8文字以上で入力してください',
      'password.confirmed' => '確認用パスワードと一致しません',
      'password_confirmation.required' => '',
      'password_confirmation.same' => '',
    ];  }
}