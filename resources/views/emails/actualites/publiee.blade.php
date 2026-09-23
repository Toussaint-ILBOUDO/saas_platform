{{-- ======================================================
    Email : Nouvelle actualité publiée
    Utilisé par : App\Mail\ActualitePublishedMail
    Variables : $actualite, $url
====================================================== --}}

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouvelle actualité K'Educ</title>
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
                                📰 K'Educ Actualités
                            </p>
                        </td>
                    </tr>

                    {{-- Corps --}}
                    <tr>
                        <td style="padding:32px;">

                            <h1 style="margin:0 0 8px 0; font-size:20px; color:#1a365d;">
                                {{ $actualite->titre }}
                            </h1>

                            <p style="margin:0 0 24px 0; font-size:13px; color:#6b7280;">
                                Publiée le {{ $actualite->published_at?->format('d/m/Y à H\hi') ?? $actualite->created_at->format('d/m/Y') }}
                            </p>

                            @if($actualite->image_original && !str_contains($actualite->image_original, 'assets/img/logo.png'))
                                <p style="margin:0 0 24px 0;">
                                    <img src="{{ $actualite->image_original }}" alt="{{ $actualite->titre }}"
                                         width="100%" style="max-width:100%; border-radius:8px; display:block;">
                                </p>
                            @endif

                            @if($actualite->resume)
                                <p style="margin:0 0 24px 0; font-size:15px; line-height:1.6;">
                                    {{ $actualite->resume }}
                                </p>
                            @endif

                            <p style="margin:0 0 24px 0;">
                                <a href="{{ $url }}" style="display:inline-block; background-color:#2563eb; color:#ffffff; padding:12px 24px; border-radius:8px; text-decoration:none; font-weight:bold;">
                                    Lire l'actualité
                                </a>
                            </p>

                            @if($actualite->lien_externe)
                                <p style="margin:0; font-size:14px;">
                                    Lien externe :
                                    <a href="{{ $actualite->lien_externe }}" style="color:#2563eb;">
                                        {{ $actualite->lien_externe }}
                                    </a>
                                </p>
                            @endif

                        </td>
                    </tr>

                    {{-- Pied de page --}}
                    <tr>
                        <td style="background-color:#f8fafc; padding:16px 32px; text-align:center;">
                            <p style="margin:0; font-size:12px; color:#9ca3af;">
                                K'Educ — L'école pour tous les âges !
                            </p>
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>
</html>
