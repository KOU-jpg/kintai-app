<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    public function rules(){
    return [
        'email' => 'required|email',
        'password' => 'required|min:8',
    ];}
    public function messages(){
    return [
        'email.required' => 'メールアドレスを入力してください',
        'email.email' => 'メールアドレス形式で入力してください',
        'password.required' => 'パスワードを入力してください',
        'password.min' => ' パスワードは8文字以上で入力してください',
    ];}
}
