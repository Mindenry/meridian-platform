<?php

namespace App\Http\Controllers\Api\V1\PublicApi;

use App\Cms\BlockResolver;
use App\Cms\ContentPresenter;
use App\Cms\SiteContext;
use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Form;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicSiteController extends Controller
{
    public function __construct(
        protected ContentPresenter $presenter,
        protected BlockResolver $blocks,
        protected SiteContext $context,
    ) {}

    public function bootstrap(Request $request): JsonResponse
    {
        $site = $this->context->siteOrFail();
        $locale = $this->context->locale();

        return response()->json([
            'data' => $this->presenter->bootstrap($site, $locale),
        ]);
    }

    public function contact(Request $request): JsonResponse
    {
        $site = $this->context->siteOrFail();
        $locale = $this->context->locale();
        $settings = $this->presenter->publicSettings($site);
        $contact = $settings['contact.details'] ?? [];
        $form = Form::forSite($site)->where('key', 'contact')->where('is_active', true)->first();

        return response()->json([
            'data' => [
                'contact' => $contact,
                'departments' => $contact['departments'] ?? [],
                'form' => $form ? $this->blocks->formSchema($form, $locale) : null,
                'legal_note' => $contact['legal_note'] ?? null,
            ],
        ]);
    }

    public function investor(Request $request): JsonResponse
    {
        $site = $this->context->siteOrFail();
        $locale = $this->context->locale();
        $documents = Document::forSite($site)->with('media')->orderByDesc('year')->orderBy('sort')->get();
        $years = $this->blocks->financialHighlights($site, $locale, 4);

        return response()->json([
            'data' => [
                'disclosure' => 'Meridian Industrial Group is a fictional private company. Financial figures and documents shown here are illustrative demonstration data, not audited statements or an investment offer.',
                'financial_highlights' => $years,
                'documents' => $documents->map(fn (Document $document) => $this->blocks->documentSummary($document, $locale))->all(),
            ],
        ]);
    }
}
