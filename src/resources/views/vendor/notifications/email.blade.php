@component('mail::message')
#メールアドレス認証のお願い


下のボタンをクリックして、メールアドレスの認証を完了してください。  
認証が完了すると、MyAppのすべての機能をご利用いただけます。

@component('mail::button', ['url' => $actionUrl])
メールアドレスを認証する
@endcomponent

もしこのメールに心当たりがない場合は、こちらのメールを破棄してください。  
ご不明な点があれば、お気軽にサポートまでご連絡ください。

---

@endcomponent