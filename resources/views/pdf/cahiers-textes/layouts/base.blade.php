<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">

    <style>

        /* =============================================
         * BASE — DomPDF safe (pas de flexbox/grid)
         * ============================================= */

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #1e293b;
            margin: 0;
            padding: 0;
        }

        @page {
            margin: 110px 40px 90px 40px;
        }

        header, footer {
            position: fixed;
            left: 0;
            right: 0;
        }

        header {
            top: -90px;
            height: 75px;
            border-bottom: 2px solid #dbe4ef;
            padding-bottom: 6px;
        }

        footer {
            bottom: -65px;
            height: 50px;
            border-top: 1px solid #dbe4ef;
            text-align: center;
            font-size: 10px;
            color: #6b7280;
            padding-top: 8px;
        }

        .page-number:after {
            content: counter(page);
        }

        main {
            margin-top: 10px;
        }

        /* =============================================
         * COMPOSANTS PDF
         * ============================================= */

        .section {
            margin-bottom: 20px;
        }

        h1 {
            font-size: 16px;
            color: #1e3a5f;
            border-bottom: 2px solid #dbe4ef;
            padding-bottom: 6px;
            margin-bottom: 12px;
        }

        h2 {
            font-size: 13px;
            color: #1e3a5f;
            margin-bottom: 8px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }

        .table th,
        .table td {
            padding: 6px 10px;
            border: 1px solid #dbe4ef;
            text-align: left;
            vertical-align: top;
        }

        .table th {
            background-color: #f1f5f9;
            font-weight: bold;
            width: 35%;
        }

        .panel {
            background-color: #f8fafc;
            border: 1px solid #dbe4ef;
            border-radius: 4px;
            padding: 12px 16px;
            margin-bottom: 16px;
        }

        p {
            margin: 0;
            line-height: 1.6;
        }

    </style>

</head>
<body>

<header>
    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>

            @php
                $logo = config('keduc.cabinet.logo');
                // Convertit une URL publique en chemin filesystem si besoin
                $logoPath = str_starts_with($logo, 'http')
                    ? $logo
                    : public_path(ltrim($logo, '/'));
            @endphp

            <td width="70" style="vertical-align: middle;">
                <img src="{{ $logoPath }}" width="55" height="55"
                     style="display:block;">
            </td>

            <td style="vertical-align: middle; padding-left: 10px;">
                <strong style="font-size: 13px;">
                    {{ config('keduc.cabinet.nom') }}
                </strong><br>
                <span style="color: #6b7280; font-size: 11px;">
                    {{ config('keduc.cabinet.slogan') }}
                </span><br>
                <span style="font-size: 11px;">
                    {{ config('keduc.cabinet.telephone') }}
                    &nbsp;|&nbsp;
                    {{ config('keduc.cabinet.email') }}
                </span>
            </td>

        </tr>
    </table>
</header>

<footer>
    Document généré automatiquement &bull; KEDUC &bull;
    Page <span class="page-number"></span>
</footer>

<main>
    @yield('content')
</main>

</body>
</html>