<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>DAR-LTCMS Registration Received</title>
</head>
<body style="margin:0;padding:0;background:#f4f7f5;font-family:Arial,Helvetica,sans-serif;color:#17211b;">
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;">
        Your landowner registration was received and is waiting for DAR staff review.
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
                            <div style="font-size:12px;line-height:18px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#16834b;">Landowner Registration</div>
                            <h1 style="margin:8px 0 10px;font-size:25px;line-height:32px;color:#111827;font-weight:700;">Registration received</h1>
                            <p style="margin:0 0 18px;font-size:15px;line-height:24px;color:#475569;">
                                @if (!empty($name))
                                    Hello {{ $name }},
                                @else
                                    Hello,
                                @endif
                                your landowner registration through Google was received.
                            </p>

                            <div style="padding:22px 18px;border:1px solid #b7e4c7;border-radius:14px;background:#f0fdf4;">
                                <div style="font-size:16px;line-height:24px;font-weight:700;color:#166534;">Pending DAR review</div>
                                <p style="margin:8px 0 0;font-size:14px;line-height:22px;color:#365b43;">
                                    Land records, parcel maps, and clearance outputs remain locked until staff verifies your identity and links the correct landowner record.
                                </p>
                            </div>

                            <div style="margin-top:22px;text-align:center;">
                                <a href="{{ $loginUrl }}" style="display:inline-block;padding:12px 20px;border-radius:9px;background:#0d6b38;color:#ffffff;text-decoration:none;font-size:14px;font-weight:700;">Sign in to DAR-LTCMS</a>
                            </div>
                            <p style="margin:18px 0 0;font-size:14px;line-height:22px;color:#475569;">
                                Use <strong>Continue with Google</strong> on the sign-in page. You do not need to register again.
                            </p>

                            <div style="margin-top:22px;padding:14px 16px;border-left:4px solid #d97706;background:#fffbeb;font-size:13px;line-height:21px;color:#7c4a03;">
                                <strong>Registration confirmation only</strong><br>
                                This email is not an approval of your account, land ownership, or clearance.
                            </div>
                            <p style="margin:20px 0 0;font-size:13px;line-height:21px;color:#64748b;">
                                For assistance, contact the DAR Negros Oriental Provincial Office.
                            </p>
                            <p style="margin:16px 0 0;font-size:12px;line-height:20px;color:#64748b;word-break:break-word;">
                                If the button does not work, open
                                <a href="{{ $loginUrl }}" style="color:#0d6b38;text-decoration:underline;word-break:break-all;">{{ $loginUrl }}</a>.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:20px 30px;background:#f8faf9;border-top:1px solid #e7ece9;text-align:center;">
                            <div style="font-size:12px;line-height:19px;color:#64748b;">This is an automated account message from DAR-LTCMS.</div>
                            <div style="margin-top:3px;font-size:11px;line-height:18px;color:#94a3b8;">Department of Agrarian Reform &middot; Negros Oriental Provincial Office</div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
