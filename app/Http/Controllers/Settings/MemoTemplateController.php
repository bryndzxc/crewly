<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreMemoTemplateRequest;
use App\Http\Requests\Settings\UpdateMemoTemplateRequest;
use App\Models\MemoTemplate;
use App\Services\MemoRenderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class MemoTemplateController extends Controller
{
    public function __construct(private readonly MemoRenderService $render)
    {
    }

    private function indexTemplates()
    {
        return MemoTemplate::query()
            ->whereNull('company_id')
            ->orderByDesc('is_system')
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'slug',
                'description',
                'body_html',
                'is_active',
                'is_system',
                'created_at',
            ]);
    }

    public function index(Request $request): Response
    {
        Gate::authorize('manage-memo-templates');

        $templates = $this->indexTemplates();

        return Inertia::render('Settings/MemoTemplates/Index', [
            'templates' => $templates,
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('manage-memo-templates');

        return Inertia::render('Settings/MemoTemplates/Index', [
            'templates' => $this->indexTemplates(),
            'modal' => [
                'mode' => 'create',
            ],
        ]);
    }

    public function store(StoreMemoTemplateRequest $request): RedirectResponse
    {
        Gate::authorize('manage-memo-templates');

        $validated = $request->validated();

        $slug = trim((string) ($validated['slug'] ?? ''));
        if ($slug === '') {
            $slug = $this->render->defaultSlug((string) ($validated['name'] ?? ''));
        }

        $template = MemoTemplate::create([
            'company_id' => null,
            'name' => (string) $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'body_html' => $this->render->sanitizeTemplateHtml((string) $validated['body_html']),
            'is_active' => array_key_exists('is_active', $validated) ? (bool) $validated['is_active'] : true,
            'is_system' => false,
            'created_by_user_id' => $request->user()?->id,
        ]);

        return to_route('settings.memo_templates.edit', $template->id)
            ->with('success', 'Memo template created.')
            ->setStatusCode(303);
    }

    public function edit(MemoTemplate $template): Response
    {
        Gate::authorize('manage-memo-templates');

        if ($template->company_id !== null) {
            abort(404);
        }

        return Inertia::render('Settings/MemoTemplates/Index', [
            'templates' => $this->indexTemplates(),
            'modal' => [
                'mode' => 'edit',
                'template' => $template->only([
                    'id',
                    'name',
                    'slug',
                    'description',
                    'body_html',
                    'is_active',
                    'is_system',
                ]),
            ],
        ]);
    }

    public function update(UpdateMemoTemplateRequest $request, MemoTemplate $template): RedirectResponse
    {
        Gate::authorize('manage-memo-templates');

        if ($template->company_id !== null) {
            abort(404);
        }

        $validated = $request->validated();

        $slug = trim((string) ($validated['slug'] ?? ''));
        if ($slug === '') {
            $slug = $this->render->defaultSlug((string) ($validated['name'] ?? ''));
        }

        $template->update([
            'name' => (string) $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'body_html' => $this->render->sanitizeTemplateHtml((string) $validated['body_html']),
            'is_active' => array_key_exists('is_active', $validated) ? (bool) $validated['is_active'] : $template->is_active,
        ]);

        return back()->with('success', 'Memo template updated.')->setStatusCode(303);
    }

    public function toggle(Request $request, MemoTemplate $template): RedirectResponse
    {
        Gate::authorize('manage-memo-templates');

        if ($template->company_id !== null) {
            abort(404);
        }

        $template->update([
            'is_active' => !$template->is_active,
        ]);

        return back()->with('success', $template->is_active ? 'Template activated.' : 'Template deactivated.')
            ->setStatusCode(303);
    }
}
