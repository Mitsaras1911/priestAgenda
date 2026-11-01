<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head>
<body style="margin:0;background:#f5f1e8;color:#28394f;font-family:Arial,Helvetica,sans-serif;padding:32px 12px">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:600px;margin:0 auto;background:#fffdf8;border:1px solid #e5dece;border-radius:10px;overflow:hidden">
<tr><td style="background:#344c70;border-bottom:4px solid #bb9653;padding:22px 28px;color:#fff;font-family:Georgia,'Times New Roman',serif;font-size:22px;letter-spacing:.04em">☦ PriestAgenda</td></tr>
<tr><td style="padding:30px 28px"><h1 style="margin:0 0 14px;color:#344c70;font-family:Georgia,'Times New Roman',serif;font-size:25px">{{ __('ui.otp_email_heading') }}</h1><p style="font-size:16px;line-height:1.6">{{ __('ui.otp_email_intro') }}</p><div style="margin:25px 0;padding:19px;text-align:center;background:#f5f1e8;border:1px solid #e5dece;border-radius:8px;color:#344c70;font-size:34px;font-weight:700;letter-spacing:.28em">{{ $code }}</div><p style="font-size:14px;line-height:1.6;color:#707987">{{ __('ui.otp_email_expiry') }}</p></td></tr>
<tr><td style="padding:16px 28px;background:#f8f5ef;border-top:1px solid #e5dece;color:#707987;font-size:12px">{{ config('app.name', 'PriestAgenda') }}</td></tr>
</table>
</body></html>
