<?php

namespace App\Http\Controllers;

use App\Models\ProposalTemplate;
use App\Traits\GridBuilderConverter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class ProposalTemplatePreviewController extends Controller
{
    use GridBuilderConverter;
    public function __invoke(ProposalTemplate $template)
    {
        // Process template content to convert grid builders
        $processedContent = $this->processTemplateContent($template->content);

        $pdf = PDF::loadView('proposal-templates.preview', [
            'template' => $template,
            'content' => $processedContent,
            'company' => config('app.name'),
            'customer' => 'Sample Customer',
            'date' => now()->format('d F Y'),
        ]);

        $pdf->getDomPDF()->setHttpContext(
            stream_context_create([
                'ssl' => [
                    'allow_self_signed' => TRUE,
                    'verify_peer' => FALSE,
                    'verify_peer_name' => FALSE,
                ]
            ])
        );

        $pdf->set_option('isRemoteEnabled', true);

        return $pdf->stream("template-preview-{$template->id}.pdf");
    }

    /**
     * Process template content to convert grid builders to PDF-compatible tables
     */
    private function processTemplateContent($content)
    {
        if (empty($content)) {
            return '';
        }

        // Convert tiptap JSON to HTML
        $htmlContent = tiptap_converter()->asHTML($content);

        // get all image URLs in the content and convert to base64
        preg_match_all('/<img[^>]+src="([^">]+)"/', $htmlContent, $matches);
        $imageUrls = $matches[1] ?? [];
        foreach ($imageUrls as $url) {
            if (filter_var($url, FILTER_VALIDATE_URL)) {
                try {
                    $imageData = file_get_contents($url);
                    $base64 = 'data:image/' . pathinfo($url, PATHINFO_EXTENSION) . ';base64,' . base64_encode($imageData);
                    $htmlContent = str_replace($url, $base64, $htmlContent);
                } catch (\Exception $e) {
                    // If fetching image fails, skip conversion
                    continue;
                }
            } elseif (Storage::disk('public')->exists($url)) {
                $imageData = Storage::disk('public')->get($url);
                $base64 = 'data:image/' . pathinfo($url, PATHINFO_EXTENSION) . ';base64,' . base64_encode($imageData);
                $htmlContent = str_replace($url, $base64, $htmlContent);
            }
        }

        // Convert Filament TipTap grid builder to PDF-compatible table layout
        return $this->convertGridBuilderToTable($htmlContent);
    }
}
