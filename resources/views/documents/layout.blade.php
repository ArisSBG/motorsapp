<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>@yield('titre')</title>
    <style>
        @page { margin: 18mm 16mm 20mm 16mm; }
        body { font-family: "DejaVu Sans", sans-serif; font-size: 10.5pt; color: #1f2937; }
        h1 { font-size: 17pt; margin: 0; color: #1e3a8a; letter-spacing: .5px; }
        .entete { width: 100%; border-bottom: 2px solid #1e3a8a; padding-bottom: 8px; margin-bottom: 16px; }
        .entete td { vertical-align: top; }
        .societe { font-size: 15pt; font-weight: bold; color: #1e3a8a; }
        .petit { font-size: 8.5pt; color: #4b5563; }
        .droite { text-align: right; }
        .centre { text-align: center; }
        .cadre { border: 1px solid #cbd5e1; border-radius: 4px; padding: 8px 10px; }
        .titre-cadre { font-size: 8pt; text-transform: uppercase; color: #64748b; margin-bottom: 3px; }
        table.lignes { width: 100%; border-collapse: collapse; margin-top: 14px; }
        table.lignes th { background: #e0e7ff; color: #1e3a8a; font-size: 9pt; text-align: left; padding: 6px; border: 1px solid #c7d2fe; }
        table.lignes td { padding: 6px; border: 1px solid #e2e8f0; }
        table.totaux { width: 45%; margin-left: 55%; border-collapse: collapse; margin-top: 10px; }
        table.totaux td { padding: 5px 6px; }
        table.totaux tr.fort td { font-weight: bold; font-size: 11.5pt; border-top: 2px solid #1e3a8a; }
        .lettres { margin-top: 14px; font-style: italic; }
        .signatures { width: 100%; margin-top: 40px; }
        .signatures td { width: 50%; vertical-align: top; height: 90px; }
        .mono { font-family: "DejaVu Sans Mono", monospace; font-size: 9.5pt; }
        .pied { position: fixed; bottom: -12mm; left: 0; right: 0; text-align: center; font-size: 7.5pt; color: #94a3b8; }
    </style>
</head>
<body>
    <table class="entete">
        <tr>
            <td>
                <div class="societe">{{ config('entreprise.nom') }}</div>
                <div class="petit">
                    {{ config('entreprise.adresse') }} — Tél. {{ config('entreprise.telephone') }}<br>
                    @if (config('entreprise.ifu')) IFU : {{ config('entreprise.ifu') }} @endif
                    @if (config('entreprise.rccm')) — RCCM : {{ config('entreprise.rccm') }} @endif
                </div>
            </td>
            <td class="droite">
                <h1>@yield('titre')</h1>
                <div>@yield('sous-titre')</div>
            </td>
        </tr>
    </table>

    @yield('contenu')

    <div class="pied">{{ config('entreprise.nom') }} — {{ config('entreprise.adresse') }} — document édité le {{ now()->format('d/m/Y à H:i') }}</div>
</body>
</html>
