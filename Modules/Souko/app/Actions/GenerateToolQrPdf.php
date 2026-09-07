<?php

namespace Modules\Souko\Actions;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Dompdf\Dompdf;
use Dompdf\Options;
use Modules\Souko\Models\Tool;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GenerateToolQrPdf
{
    /**
     * @param  array<int, int|string>  $toolIds
     */
    public function handle(array $toolIds): StreamedResponse
    {
        $tools = Tool::query()
            ->whereKey($toolIds)
            ->orderBy('type')
            ->orderBy('name')
            ->orderBy('management_number')
            ->get()
            ->map(fn (Tool $tool): array => [
                'name' => $tool->name,
                'managementNumber' => $tool->management_number,
                'qrDataUri' => $this->qrDataUri($tool->management_number),
            ]);

        $html = view('souko::pdf.tool-qr-codes', [
            'tools' => $tools,
        ])->render();

        $options = new Options;
        $fontCachePath = storage_path('fonts');
        if (! is_dir($fontCachePath)) {
            mkdir($fontCachePath, 0755, true);
        }

        $options->setFontDir($fontCachePath);
        $options->setFontCache($fontCachePath);
        $options->setChroot([base_path(), $fontCachePath]);

        $dompdf = new Dompdf($options);
        $japaneseFontPath = $this->japaneseFontPath();
        if ($japaneseFontPath !== null) {
            $fontMetrics = $dompdf->getFontMetrics();
            $fontMetrics->registerFont(
                ['family' => 'JapaneseFont', 'style' => 'normal', 'weight' => 'normal'],
                'file://'.$japaneseFontPath,
            );
            $fontMetrics->registerFont(
                ['family' => 'JapaneseFont', 'style' => 'normal', 'weight' => 'bold'],
                'file://'.$japaneseFontPath,
            );
        }

        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('a4', 'portrait');
        $dompdf->render();

        $filename = 'tool-qr-codes-'.now()->format('Ymd-His').'.pdf';
        $pdf = $dompdf->output();

        return response()->streamDownload(
            static function () use ($pdf): void {
                echo $pdf;
            },
            $filename,
            ['Content-Type' => 'application/pdf'],
        );
    }

    private function qrDataUri(string $managementNumber): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle(180, 4),
            new SvgImageBackEnd,
        );

        $svg = (new Writer($renderer))->writeString($managementNumber);

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    private function japaneseFontPath(): ?string
    {
        $fontPath = base_path('Modules/Souko/resources/fonts/ArialUnicodeJapanese.ttf');

        return is_file($fontPath) ? $fontPath : null;
    }
}
