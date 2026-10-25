<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>{{ $document_titre ?? 'Document' }} {{ $document_reference ?? '' }}</title>

    <style>
        /* =========================================================
         * Gabarit commun des documents PDF de la PLATEFORME
         * (facture cabinet, contrat commercial).
         * DomPDF safe : tables uniquement ; couleurs littérales ;
         * en-tête/pied en position fixed (recopiés sur chaque page).
         * ========================================================= */

        @page { margin: 112px 42px 84px 42px; }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11.4px;
            color: #1e293b;
            margin: 0;
            padding: 0;
        }

        /* ---------- EN-TÊTE FIXE (chaque page) ---------- */

        .entete {
            position: fixed;
            top: -96px;
            left: 0;
            right: 0;
            height: 84px;
            border-bottom: 2.5px solid #12305e;
        }

        .entete table { width: 100%; border-collapse: collapse; }
        .entete td { vertical-align: middle; padding: 0; }

        .entete .cell-logo { width: 88px; padding-right: 15px !important; }
        .entete .cell-logo img { max-height: 64px; max-width: 68px; }

        .entete .monogramme {
            width: 56px; height: 56px; line-height: 56px;
            background: #12305e; color: #ffffff;
            font-size: 26px; font-weight: bold; text-align: center;
        }

        .entete .nom-plateforme { font-size: 17px; font-weight: bold; color: #12305e; letter-spacing: 0.4px; }
        .entete .classement { font-size: 10.4px; font-style: italic; color: #64748b; margin-top: 3px; }
        .entete .plateforme-contact { font-size: 9.6px; color: #334155; margin-top: 4px; }

        .entete .cabinet-cible { text-align: right; font-size: 10.4px; color: #334155; }
        .entete .cabinet-cible b { color: #12305e; }

        /* ---------- BANDEAU DE TITRE ---------- */

        .bande-titre { width: 100%; background: #12305e; color: #ffffff; border-collapse: collapse; margin-top: 3px; }
        .bande-titre td { padding: 11px 15px; vertical-align: middle; }
        .bande-titre .titre { font-size: 15.5px; font-weight: bold; letter-spacing: 1.4px; text-transform: uppercase; }
        .bande-titre .sous-titre { font-size: 10.4px; color: #c7d6ee; margin-top: 5px; }
        .bande-titre .cell-ref { text-align: right; width: 190px; }
        .bande-titre .ref { font-size: 10.8px; font-weight: bold; }
        .bande-titre .edition { font-size: 9.4px; color: #c7d6ee; margin-top: 4px; }

        /* ---------- LIGNE MÉTA + STATUT ---------- */

        .ligne-meta { width: 100%; border-collapse: collapse; margin-top: 9px; }
        .ligne-meta td {
            background: #eef3fb; border: 1px solid #dbe4ef;
            padding: 7px 11px; font-size: 11px;
        }
        .ligne-meta .cell-statut { text-align: right; width: 160px; }

        .badge {
            display: inline-block; padding: 3px 10px; border-radius: 9px;
            font-size: 9.6px; font-weight: bold; color: #ffffff;
            background: #15803d; text-transform: uppercase; letter-spacing: 0.5px;
        }
        .badge.info { background: #1d4e89; }
        .badge.warn { background: #b45309; }
        .badge.mute { background: #64748b; }
        .badge.danger { background: #b91c1c; }

        /* ---------- SECTIONS ---------- */

        .section { margin-top: 17px; }
        .section-titre {
            background: #eef3fb; border-left: 4px solid #1d4e89;
            padding: 7px 11px; font-size: 12.4px; font-weight: bold;
            color: #12305e; letter-spacing: 0.5px; text-transform: uppercase;
            page-break-after: avoid;
        }
        .section-note { font-size: 10.3px; color: #64748b; font-style: italic; margin-top: 6px; }
        .desc { display: block; font-size: 9.3px; color: #64748b; font-style: italic; margin-top: 1px; }

        /* ---------- TABLEAU D'INFORMATIONS ---------- */

        .infos { width: 100%; border-collapse: collapse; margin-top: 9px; }
        .infos td { border: 1px solid #dbe4ef; padding: 7px 10px; font-size: 11.1px; vertical-align: top; }
        .infos .lbl { background: #f8fafc; font-weight: bold; color: #334155; width: 18%; }
        .infos .val { width: 32%; }

        /* ---------- TABLEAU DE DONNÉES ---------- */

        .data { width: 100%; border-collapse: collapse; margin-top: 9px; font-size: 10.4px; }
        .data thead { display: table-header-group; }
        .data tr { page-break-inside: avoid; }

        .data th {
            background: #12305e; color: #ffffff;
            padding: 7px 8px; text-align: left; font-size: 9.8px;
            letter-spacing: 0.5px; text-transform: uppercase;
            border: 1px solid #12305e;
        }
        .data td { border: 1px solid #dbe4ef; padding: 6px 8px; vertical-align: top; }
        .data tr.alt td { background: #f8fafc; }

        .data .num { text-align: right; white-space: nowrap; }

        .data tr.total td {
            background: #eef3fb; font-weight: bold; color: #12305e;
            border-top: 2px solid #1d4e89;
        }

        .credit { color: #15803d; font-weight: bold; }
        .pire { color: #b91c1c; }

        /* ---------- CADRE / MENTION ---------- */

        .cadre { border: 1px solid #dbe4ef; background: #f8fafc; padding: 10px 12px; line-height: 1.55; margin-top: 8px; }

        .mention { text-align: center; font-size: 11.1px; color: #334155; margin-top: 30px; }

        .signature { width: 100%; border-collapse: collapse; margin-top: 6px; page-break-inside: avoid; }
        .signature td { width: 50%; text-align: center; font-size: 11.2px; vertical-align: top; }
        .signature .trait { border-top: 1px solid #94a3b8; margin: 44px 42px 0 42px; }
        .signature .role { font-weight: bold; color: #12305e; margin-top: 7px; }
    </style>
</head>

<body>

    {{-- En-tête fixe : plateforme à gauche, cabinet destinataire à droite. --}}
    <div class="entete">
        <table>
            <tr>
                <td class="cell-logo">
                    @if (! empty($plateforme_logo))
                        <img src="{{ $plateforme_logo }}" alt="Logo">
                    @else
                        <div class="monogramme">
                            {{ strtoupper(mb_substr($plateforme_nom, 0, 1)) }}
                        </div>
                    @endif
                </td>

                <td>
                    <div class="nom-plateforme">{{ $plateforme_nom }}</div>

                    @if (! empty($plateforme_tagline))
                        <div class="classement">{{ $plateforme_tagline }}</div>
                    @endif

                    <div class="plateforme-contact">
                        {{ $plateforme_contact }}
                    </div>
                </td>

                <td class="cabinet-cible">
                    <b>{{ $cabinet_nom }}</b><br>
                    ÇabinetKeduc ({{ $cabinet_code }})
                </td>
            </tr>
        </table>
    </div>

    {{-- Bandeau de titre : document + référence + édition --}}
    <table class="bande-titre">
        <tr>
            <td>
                <div class="titre">{{ $document_titre ?? 'Document' }}</div>
                @if (! empty($document_sous_titre))
                    <div class="sous-titre">{{ $document_sous_titre }}</div>
                @endif
            </td>

            <td class="cell-ref">
                <div class="ref">Réf. {{ $document_reference ?? '—' }}</div>
                <div class="edition">
                    Édité le {{ ($date_edition ?? now())->format('d/m/Y à H:i') }}
                </div>
            </td>
        </tr>
    </table>

    {{-- Ligne méta + badge de statut (optionnels) --}}
    @if (! empty($ligne_meta) || ! empty($badge))
        <table class="ligne-meta">
            <tr>
                <td>{!! $ligne_meta ?? '' !!}</td>

                @if (! empty($badge))
                    <td class="cell-statut">
                        <span class="badge {{ $badge['classe'] ?? '' }}">
                            {{ $badge['libelle'] ?? '' }}
                        </span>
                    </td>
                @endif
            </tr>
        </table>
    @endif

    {{-- Contenu propre au document --}}
    @yield('document_contenu')

    @hasSection('document_mention')
        <div class="mention">@yield('document_mention')</div>
    @endif

    @hasSection('document_signatures')
        <table class="signature">
            <tr>
                @yield('document_signatures')
            </tr>
        </table>
    @endif

</body>

</html>
