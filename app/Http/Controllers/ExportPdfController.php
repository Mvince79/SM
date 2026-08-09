<?php

namespace App\Http\Controllers;

use App\Models\MissingChild;
use App\Models\Photo;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class ExportPdfController extends Controller
{
    public function missingChild(int $id): SymfonyResponse
    {
        $case = MissingChild::query()
            ->with([
                'province',
                'primaryPhoto',
                'photos' => fn($q) => $q->orderBy('sort_order'),
            ])
            // Chỉ cho xuất PDF các case đã công khai — tránh lộ hồ sơ PENDING chưa duyệt
            ->whereIn('status', ['APPROVED', 'FOUND', 'CLOSED'])
            ->findOrFail($id);

        $detailUrl = route('missing.detail', ['id' => $case->id]);

        $pdf = Pdf::loadView('pdf.missing-child', [
            'case' => $case,
            'detailUrl' => $detailUrl,
            'photoDataUri' => $this->resolvePhotoDataUri($case),
            'qrDataUri' => $this->resolveQrDataUri($detailUrl),
            'generatedAt' => now(),
        ])->setPaper('a4', 'portrait');

        $fileName = 'ho-so-tim-tre-' . ($case->case_code ?: $case->id) . '.pdf';

        return $pdf->download($fileName);
    }

    private function resolvePhotoDataUri(MissingChild $case): ?string
    {
        $photo = $case->primaryPhoto ?: $case->photos->first();

        if (!$photo instanceof Photo) {
            return null;
        }

        foreach (['path_display', 'path_original', 'path_thumb', 'path_blurred'] as $field) {
            $path = $photo->{$field} ?? null;

            if ($path && Storage::disk('public')->exists($path)) {
                $contents = Storage::disk('public')->get($path);
                $mime = Storage::disk('public')->mimeType($path) ?: 'image/jpeg';

                return 'data:' . $mime . ';base64,' . base64_encode($contents);
            }
        }

        return null;
    }

    private function resolveQrDataUri(string $url): string
    {
        try {
            $png = QrCode::format('png')
                ->size(260)
                ->margin(1)
                ->errorCorrection('H')
                ->generate($url);

            return 'data:image/png;base64,' . base64_encode($png);
        } catch (\Throwable $e) {
            $svg = QrCode::format('svg')
                ->size(260)
                ->margin(1)
                ->errorCorrection('H')
                ->generate($url);

            return 'data:image/svg+xml;base64,' . base64_encode($svg);
        }
    }
}