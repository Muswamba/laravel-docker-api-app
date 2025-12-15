@component('mail::message')
# <span style="color: #1a73e8;">Password Successfully Updated</span>

<div style="font-size: 16px; line-height: 1.5;">
    This email serves as a **security confirmation** that the password associated with your account has been
    successfully reset and changed.
</div>

@component('mail::panel')
If **you** performed this action, no further steps are required. You can safely ignore the rest of this email.
@endcomponent

---

<h2 style="color: #d93025; border-bottom: 2px solid #fce8e6; padding-bottom: 5px;">Security Alert: Was This You?</h2>

<div
    style="font-size: 16px; line-height: 1.5; background-color: #fef7e0; padding: 15px; border-radius: 6px; border-left: 5px solid #fbbc04;">
    If **you did NOT** initiate this password change, this is a serious security incident. This means someone
    unauthorized may have gained access to your account.
</div>

**Please take immediate action:**

1. **Click the button below immediately** to secure your account and initiate a lockout/reversal process.
2. If you still cannot access your account, please contact our dedicated support team immediately.

@component('mail::button', ['url' => url('/support/security-breach'), 'color' => 'red'])
Secure My Account Now (URGENT)
@endcomponent

### Account Protection Tips

<div style="font-size: 14px; color: #5f6368; padding: 10px; border: 1px dashed #e8eaed; border-radius: 4px;">
    To help keep your account safe in the future, we highly recommend:

    * Using a strong, unique password that combines uppercase, lowercase, numbers, and symbols.
    * Enabling **Two-Factor Authentication (2FA)** in your account settings for an extra layer of protection.
</div>

<div style="text-align: center; margin-top: 20px; font-style: italic; color: #7f878a;">
    Thank you for your attention to security.
</div>

@endcomponent
