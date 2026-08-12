<?php

namespace App\Http\Controllers;

use App\Models\ProposalTemplate;

class ProposalTemplatePreviewHtmlController extends Controller
{
    public function __invoke(ProposalTemplate $template)
    {
        return view('proposal-templates.preview', [
            'template' => $template,
            'content' => $template->content,
            'company' => config('app.name'),
            'customer' => 'Sample Customer',
            'date' => now()->format('d F Y'),
        ]);
    }
}
// test push