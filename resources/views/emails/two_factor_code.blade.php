<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Kod Weryfikacyjny</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f4f5; margin: 0; padding: 20px;">
    <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);">
        <h2 style="color: #1f2937; margin-top: 0;">Weryfikacja dwuetapowa</h2>
        <p style="color: #4b5563; font-size: 16px; line-height: 1.5;">
            Otrzymujesz tę wiadomość, ponieważ podjęto próbę logowania do Twojego konta. Aby dokończyć logowanie, użyj poniższego kodu:
        </p>
        
        <div style="background-color: #f3f4f6; border-radius: 6px; padding: 15px; text-align: center; margin: 25px 0;">
            <span style="font-size: 32px; font-weight: bold; letter-spacing: 5px; color: #4f46e5;">{{ $code }}</span>
        </div>

        <p style="color: #4b5563; font-size: 14px; line-height: 1.5;">
            Kod jest ważny przez ograniczony czas. Jeśli to nie Ty próbowałeś się zalogować, zignoruj tę wiadomość.
        </p>
    </div>
</body>
</html>
