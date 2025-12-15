@component('mail::message')
# Password Reset Request

You are receiving this email because we received a password reset request for your account.

Your 6-digit reset code is:

<div
    style="text-align: center; font-size: 32px; font-weight: bold; color: #0095f6; margin: 20px 0; padding: 15px; background-color: #f3f4f6; border-radius: 8px; letter-spacing: 5px;">
    {{ $code }}
</div>

This code is valid for **10 minutes**.

If you did not request a password reset, no further action is required.

@endcomponent