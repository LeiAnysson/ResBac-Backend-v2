<h3>Hello {{ $user->name }},</h3>

<p>Click the button below to reset your password. This link expires in 30 minutes.</p>

<a href="{{ $url }}" style="display:inline-block; padding:10px 20px; background-color:#23408e; color:#fff; text-decoration:none; border-radius:5px;">
    Reset Password
</a>

<p style="margin-top:20px; font-size:0.9rem; color:#555;">
    If you didn’t request this, just ignore this email.
</p>
