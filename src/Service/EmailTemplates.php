<?php
namespace App\Service;

class EmailTemplates
{
    private static function layout(string $body): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#0a0a0a;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#0a0a0a;padding:60px 20px;">
    <tr><td align="center">
      <table width="520" cellpadding="0" cellspacing="0"
             style="background:#111;border:1px solid rgba(255,255,255,0.1);padding:50px 40px;color:white;">
        <tr><td style="padding-bottom:24px;border-bottom:1px solid rgba(255,255,255,0.08);">
          <p style="margin:0;font-size:42px;font-weight:700;letter-spacing:2px;color:white;">Theatro</p>
          <p style="margin:4px 0 0;font-size:10px;letter-spacing:4px;color:rgba(255,255,255,0.4);text-transform:uppercase;">
            INSAT — Registration
          </p>
        </td></tr>
        {$body}
        <tr><td style="padding-top:28px;border-top:1px solid rgba(255,255,255,0.08);">
          <p style="font-size:11px;color:rgba(255,255,255,0.2);margin:0;">
            © 2026 Theatro INSAT. All Rights Reserved.
          </p>
        </td></tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;
    }

    public static function accept(string $firstname, string $lastname): string
    {
        $name = trim("$firstname $lastname") ?: 'there';
        $body = <<<HTML
        <tr><td style="padding:30px 0 20px;">
          <p style="font-size:15px;line-height:1.7;color:rgba(255,255,255,0.7);margin:0 0 10px;">Hi {$name},</p>
          <p style="font-size:14px;line-height:1.7;color:rgba(255,255,255,0.6);margin:0;">
            We're thrilled to inform you that your registration request for
            <strong style="color:white;">Theatro INSAT</strong> has been
            <strong style="color:white;">accepted</strong>.
          </p>
        </td></tr>
        <tr><td align="center" style="padding:10px 0 30px;">
          <div style="display:inline-block;padding:22px 48px;
                      background:rgba(255,255,255,0.05);
                      border:1px solid rgba(255,255,255,0.25);
                      font-size:28px;font-weight:700;color:white;letter-spacing:4px;">
            ✓ Welcome aboard
          </div>
        </td></tr>
        <tr><td>
          <p style="font-size:14px;line-height:1.7;color:rgba(255,255,255,0.6);margin:0 0 16px;">
            You can now log in to your account using the credentials you provided during registration.
          </p>
          <p style="font-size:12px;color:rgba(255,255,255,0.3);line-height:1.6;margin:0;">
            If you did not submit this request, please contact us immediately.
          </p>
        </td></tr>
HTML;
        return self::layout($body);
    }

    public static function decline(string $firstname, string $lastname): string
    {
        $name = trim("$firstname $lastname") ?: 'there';
        $body = <<<HTML
        <tr><td style="padding:30px 0 20px;">
          <p style="font-size:15px;line-height:1.7;color:rgba(255,255,255,0.7);margin:0 0 10px;">Hi {$name},</p>
          <p style="font-size:14px;line-height:1.7;color:rgba(255,255,255,0.6);margin:0;">
            After careful review, we regret to inform you that your registration request for
            <strong style="color:white;">Theatro INSAT</strong> has not been approved at this time.
          </p>
        </td></tr>
        <tr><td align="center" style="padding:10px 0 30px;">
          <div style="display:inline-block;padding:22px 48px;
                      background:rgba(255,255,255,0.05);
                      border:1px solid rgba(255,255,255,0.15);
                      font-size:28px;font-weight:700;color:rgba(255,255,255,0.5);letter-spacing:4px;">
            ✗ Not approved
          </div>
        </td></tr>
        <tr><td>
          <p style="font-size:14px;line-height:1.7;color:rgba(255,255,255,0.6);margin:0 0 16px;">
            If you believe this is a mistake or would like more information,
            feel free to reach out to the Theatro INSAT team.
          </p>
          <p style="font-size:12px;color:rgba(255,255,255,0.3);line-height:1.6;margin:0;">
            Thank you for your interest in joining Theatro INSAT.
          </p>
        </td></tr>
HTML;
        return self::layout($body);
    }

    public static function reset(string $code, string $firstname = ''): string
    {
        $greeting  = $firstname ? "Hi {$firstname}," : "Hello,";
        $formatted = substr($code, 0, 3) . ' ' . substr($code, 3, 3);
        $body = <<<HTML
        <tr><td style="padding:30px 0 20px;">
          <p style="font-size:15px;line-height:1.7;color:rgba(255,255,255,0.7);margin:0 0 10px;">{$greeting}</p>
          <p style="font-size:14px;line-height:1.7;color:rgba(255,255,255,0.6);margin:0;">
            Use the code below to reset your Theatro INSAT password.<br>
            This code expires in <strong style="color:white;">15 minutes</strong>.
          </p>
        </td></tr>
        <tr><td align="center" style="padding:10px 0 30px;">
          <div style="display:inline-block;padding:22px 48px;background:rgba(255,255,255,0.05);
                      border:1px solid rgba(255,255,255,0.25);letter-spacing:12px;
                      font-size:36px;font-weight:700;color:white;font-family:monospace;">
            {$formatted}
          </div>
        </td></tr>
        <tr><td>
          <p style="font-size:12px;color:rgba(255,255,255,0.3);line-height:1.6;margin:0;">
            If you didn't request this, you can safely ignore this email.<br>
            Never share this code with anyone.
          </p>
        </td></tr>
HTML;
        return self::layout($body);
    }
}