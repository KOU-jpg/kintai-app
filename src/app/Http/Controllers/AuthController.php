<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;

class AuthController extends Controller
{
  //ログインページ表示
  public function loginView()  {  return view('auth/login');  }

  //会員登録処理
  public function register(RegisterRequest $request)  {
    $form = $request->all();
    $form['password'] = Hash::make($form['password']);
    $user = User::create($form);
    
    //認証メール送信
    event(new Registered($user));
    auth()->login($user);
    return redirect()->route('verification.notice');}

  //ログイン処理
  public function login(LoginRequest $request){
  $credentials = $request->only('email', 'password');
  // 認証成功：セッション再生成（セキュリティ対策）
    if (Auth::attempt($credentials)) {
        $request->session()->regenerate();

        // ユーザーのロールが user かどうかチェック
        if (Auth::user()->role === 'user') {
            return redirect('/attendance');
        } else {
            // 適切でない場合はログアウトし、エラーを返す
            Auth::logout();
            return back()->withErrors([
                'auth.failed' => 'このアカウントではログインできません。',
            ])->withInput();
        }
    }
  return back()->withErrors([
      'auth.failed' => 'ログイン情報が登録されていません',
  ])->withInput();}


  //ログアウト処理
  public function logout(Request $request){
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect('/login');
  }


//管理者ログイン画面表示
public function loginViewAdmin()
{
    return view('auth.loginAdmin');
}
//管理者ログイン処理
public function loginAdmin(LoginRequest $request)
{
    $credentials = $request->only('email', 'password');

    if (Auth::attempt($credentials)) {
        $user = Auth::user();
        if ($user->role === 'admin') {
            $request->session()->regenerate();
            return redirect()->route('admin.attendance.list');
        } else {
            Auth::logout();
            return back()->withErrors([
                'email' => '管理者権限がありません。',
            ])->withInput();
        }
    }

    return back()->withErrors([
        'email' => '認証情報が正しくありません。',
    ])->withInput();
}

  // 認証案内ページ
    public function verifyEmailNotice() {
    return view('auth.verify-email');
  }

  // 認証リンク処理
  public function verifyEmail(EmailVerificationRequest $request) {
    if ($request->user()->hasVerifiedEmail()) {
        return redirect('/attendance');
    }
    $request->fulfill(); // これでemail_verified_atがセットされる
    event(new Verified($request->user()));
    return redirect('/attendance');
  }

  // 認証メール再送信
  public function resendVerificationEmail(Request $request) {
  if ($request->user()->hasVerifiedEmail()) {
      return redirect('/attendance');
  }
  $request->user()->sendEmailVerificationNotification();
  return back()->with('message', 'メールを再送信しました');
  }
}
