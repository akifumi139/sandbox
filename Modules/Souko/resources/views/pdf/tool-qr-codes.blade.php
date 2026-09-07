<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 10mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: JapaneseFont, DejaVu Sans, sans-serif;
        }

        /* テーブルレイアウト */
        table {
            width: 100%;
            table-layout: fixed;
            border-collapse: separate;
            border-spacing: 4mm 3mm;
        }

        /* ラベルセル */
        .label {
            width: 33.333%;
            height: 32mm;
            padding: 1mm 2mm;
            border: 0.2mm solid #d1d5db;
            overflow: hidden;
            vertical-align: middle;
            text-align: center;
            page-break-inside: avoid;
        }

        /* QRコード画像 */
        .qr {
            display: block;
            width: 20mm;
            height: 20mm;
            margin: 0 auto;
            padding: 0;
        }

        /* ツール名 */
        .tool-name {
            margin-top: -2mm;
            /* QRコード側へ近づける調整 */
            margin-bottom: 0;
            padding: 0;
            font-size: 8pt;
            font-weight: bold;
            line-height: 1.0;
            text-align: center;
            max-width: 100%;
            overflow-wrap: anywhere;
        }

        /* 管理番号 */
        .management-number {
            margin-top: 0;
            margin-bottom: 0;
            padding: 0;
            font-size: 9pt;
            line-height: 1.0;
            text-align: center;
            max-width: 100%;
            overflow-wrap: anywhere;
        }
    </style>
</head>

<body>
    <table>
        @foreach ($tools->chunk(3) as $row)
            <tr>
                @foreach ($row as $tool)
                    <td class="label">
                        <img class="qr" src="{{ $tool['qrDataUri'] }}" alt="{{ $tool['managementNumber'] }}">
                        <div class="tool-name">{{ $tool['name'] }}</div>
                        <div class="management-number">{{ $tool['managementNumber'] }}</div>
                    </td>
                @endforeach
                @for ($emptyCell = $row->count(); $emptyCell < 3; $emptyCell++)
                    <td></td>
                @endfor
            </tr>
        @endforeach
    </table>
</body>

</html>
