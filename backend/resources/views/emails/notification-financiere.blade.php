{{-- ======================================================
    Email : Notification financière (T7A.10)
    Utilisé par : App\Mail\NotificationFinanciereMail
    Variables : $sujet, $contenu, $url (?string), $cabinet
====================================================== --}}

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $sujet }} — {{ $cabinet }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f7fb; font-family:Arial, Helvetica, sans-serif; color:#333;">

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f7fb; padding:24px 0;">
        <tr>
            <td align="center">

                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff; border-radius:12px; overflow:hidden;">

                    {{-- En-tête --}}
                    <tr>
                        <td style="background-color:#2563eb; padding:24px 32px;">
                            <p style="margin:0; color:#ffffff; font-size:22px; font-weight:bold;">
                                {{ $cabinet }}
                            </p>
                            <p style="margin:4px 0 0 0; color:#dbeafe; font-size:13px;">
                                Notification
                            </p>
                        </td>
                    </tr>

                    {{-- Corps --}}
                    <tr>
                        <td style="padding:32px;">

                            <h1 style="margin:0 0 16px 0; font-size:20px; color:#1a365d;">
                                {{ $sujet }}
                            </h1>

                            <p style="margin:0 0 24px 0; font-size:15px; line-height:1.6; color:#374151;">
                                {{ $contenu }}
                            </p>

                            @if($url)
                                <p style="margin:0 0 24px 0;">
                                    <a href="{{ $url }}" style="display:inline-block; background-color:#2563eb; color:#ffffff; padding:12px 24px; border-radius:8px; text-decoration:none; font-weight:bold;">
                                        Consulter
                                    </a>
                                </p>
                            @endif

                        </td>
                    </tr>

                    {{-- Pied de page --}}
                    <tr>
                        <td style="background-color:#f8fafc; padding:16px 32px; text-align:center;">
                            <p style="margin:0; font-size:12px; color:#9ca3af;">
                                {{ $cabinet }} — L'école pour tous les âges !
                            </p>
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>
</html>
