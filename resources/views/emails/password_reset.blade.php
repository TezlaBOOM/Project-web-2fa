<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Resetowanie hasła</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f4f5; margin: 0; padding: 20px;">
    <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);">
        <h2 style="color: #1f2937; margin-top: 0;">Resetowanie hasła</h2>
        <p style="color: #4b5563; font-size: 16px; line-height: 1.5;">
            Otrzymujesz tę wiadomość, ponieważ wysłano prośbę o zresetowanie hasła do Twojego konta.
        </p>

        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $resetUrl }}"
               style="display: inline-block; background: linear-gradient(135deg, #6366f1, #8b5cf6); color: #ffffff; text-decoration: none; padding: 14px 32px; border-radius: 8px; font-size: 16px; font-weight: 600;">
                Zresetuj hasło
            </a>
        </div>

        <p style="color: #4b5563; font-size: 14px; line-height: 1.5;">
            Link jest ważny przez <strong>60 minut</strong>. Jeśli to nie Ty wysłałeś tę prośbę, możesz zignorować tę wiadomość — Twoje hasło pozostanie bez zmian.
        </p>

        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 20px 0;">

        <p style="color: #9ca3af; font-size: 12px; line-height: 1.5;">
            Jeśli przycisk nie działa, skopiuj i wklej poniższy adres URL do przeglądarki:<br>
            <span style="color: #6366f1; word-break: break-all;">{{ $resetUrl }}</span>
        </p>
    </div>
</body>
</html>
