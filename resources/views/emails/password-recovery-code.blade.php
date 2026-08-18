<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>DAR-LTCMS Password Recovery Code</title>
</head>
<body style="margin:0;padding:0;background:#f4f7f5;font-family:Arial,Helvetica,sans-serif;color:#17211b;">
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;">
        Your DAR-LTCMS password recovery verification code is {{ $code }} and expires in 10 minutes.
    </div>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%;background:#f4f7f5;margin:0;padding:0;">
        <tr>
            <td align="center" style="padding:32px 16px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%;max-width:600px;background:#ffffff;border:1px solid #dce5df;border-radius:18px;overflow:hidden;box-shadow:0 12px 32px rgba(15,23,42,.08);">
                    <tr>
                        <td style="padding:26px 30px 22px;border-bottom:1px solid #e7ece9;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td width="64" valign="middle" style="width:64px;">
                                        <img src="{{ $logoUrl }}" width="54" height="54" alt="DAR-LTCMS" style="display:block;width:54px;height:54px;object-fit:contain;border:0;">
                                    </td>
                                    <td valign="middle" style="padding-left:12px;">
                                        <div style="font-size:18px;line-height:24px;font-weight:700;color:#075c2c;">DAR-LTCMS</div>
                                        <div style="margin-top:3px;font-size:12px;line-height:18px;color:#64748b;">DAR Negros Oriental Provincial Office</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:30px;">
                            <div style="font-size:12px;line-height:18px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#16834b;">Password Recovery</div>
                            <h1 style="margin:8px 0 10px;font-size:25px;line-height:32px;color:#111827;font-weight:700;">Verify your account</h1>

                            <p style="margin:0 0 18px;font-size:15px;line-height:24px;color:#475569;">
                                @if (!empty($name))
                                    Hello {{ $name }},
                                @else
                                    Hello,
                                @endif
                                a password recovery request was made for your DAR-LTCMS account.
                            </p>

                            @if (!empty($username))
                                <div style="margin:0 0 20px;padding:12px 14px;border:1px solid #e2e8f0;border-radius:10px;background:#f8fafc;font-size:13px;line-height:20px;color:#475569;">
                                    Account username: <strong style="color:#0f172a;">{{ $username }}</strong>
                                </div>
                            @endif

                            <div style="padding:22px 18px;border:1px solid #b7e4c7;border-radius:14px;background:#f0fdf4;text-align:center;">
                                <div style="font-size:12px;line-height:18px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#166534;">Your verification code</div>
                                <div style="margin-top:8px;font-size:34px;line-height:42px;font-weight:800;letter-spacing:8px;color:#064e2a;">{{ $code }}</div>
                                <div style="margin-top:8px;font-size:12px;line-height:18px;color:#4b6b58;">Expires in 10 minutes · One-time use</div>
                            </div>

                            <p style="margin:22px 0 0;font-size:14px;line-height:22px;color:#475569;">
                                Enter this code on the DAR-LTCMS password recovery page. Do not share it with anyone, including DAR staff.
                            </p>

                            <div style="margin-top:22px;padding:14px 16px;border-left:4px solid #d97706;background:#fffbeb;font-size:13px;line-height:21px;color:#7c4a03;">
                                <strong>Did not request this?</strong><br>
                                You may ignore this email. Your existing password will remain unchanged unless the verification code is successfully completed.
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:20px 30px;background:#f8faf9;border-top:1px solid #e7ece9;text-align:center;">
                            <div style="font-size:12px;line-height:19px;color:#64748b;">This is an automated security message from DAR-LTCMS.</div>
                            <div style="margin-top:3px;font-size:11px;line-height:18px;color:#94a3b8;">Department of Agrarian Reform · Negros Oriental Provincial Office</div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
