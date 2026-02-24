<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateMemoRequest;
use App\Models\Employee;
use App\Models\EmployeeIncident;
use App\Models\Memo;
use App\Models\MemoTemplate;
use App\Services\MemoPdfService;
use App\Services\MemoRenderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MemoController extends Controller
{
    public function __construct(
        private readonly MemoRenderService $render,
        private readonly MemoPdfService $pdf,
    ) {}

    public function previewForIncident(GenerateMemoRequest $request, Employee $employee, EmployeeIncident $incident): JsonResponse
    {
        Gate::authorize('generate-memos');

        if ((int) $incident->employee_id !== (int) $employee->employee_id) {
            abort(404);
        }

        $validated = $request->validated();

        /** @var MemoTemplate $template */
        $template = MemoTemplate::query()
            ->whereNull('company_id')
            ->where('is_active', true)
            ->findOrFail((int) $validated['memo_template_id']);

        $vars = $this->render->buildIncidentVars($employee, $incident, [
            'incident_summary' => $validated['incident_summary'] ?? null,
            'memo_date' => $validated['memo_date'] ?? null,
            'hr_signatory_name' => $validated['hr_signatory_name'] ?? ($request->user()?->name ?? ''),
        ]);

        $renderedHtml = $this->render->render((string) $template->body_html, $vars);

        $title = trim($template->name . ' - ' . ($vars['employee_name'] ?? ''));

        return response()->json([
            'title' => $title,
            'rendered_html' => $renderedHtml,
        ]);
    }

    public function storeForIncident(GenerateMemoRequest $request, Employee $employee, EmployeeIncident $incident): RedirectResponse
    {
        Gate::authorize('generate-memos');

        if ((int) $incident->employee_id !== (int) $employee->employee_id) {
            abort(404);
        }

        $validated = $request->validated();

        /** @var MemoTemplate $template */
        $template = MemoTemplate::query()
            ->whereNull('company_id')
            ->where('is_active', true)
            ->findOrFail((int) $validated['memo_template_id']);

        $user = $request->user();
        abort_unless($user, 401);

        $vars = $this->render->buildIncidentVars($employee, $incident, [
            'incident_summary' => $validated['incident_summary'] ?? null,
            'memo_date' => $validated['memo_date'] ?? null,
            'hr_signatory_name' => $validated['hr_signatory_name'] ?? ($user->name ?? ''),
        ]);

        $renderedHtml = $this->render->render((string) $template->body_html, $vars);
        $title = trim($template->name . ' - ' . ($vars['employee_name'] ?? ''));

        $memo = DB::transaction(function () use ($employee, $incident, $template, $user, $title, $renderedHtml) {
            $stored = $this->pdf->renderAndStore((int) $employee->employee_id, $title, $renderedHtml);

            return Memo::create([
                'company_id' => null,
                'employee_id' => (int) $employee->employee_id,
                'incident_id' => (int) $incident->id,
                'memo_template_id' => (int) $template->id,
                'title' => $title,
                'body_rendered_html' => $renderedHtml,
                'pdf_path' => (string) $stored['pdf_path'],
                'status' => 'generated',
                'created_by_user_id' => (int) $user->id,
            ]);
        });

        return to_route('employees.show', $employee->employee_id)
            ->with('success', 'Memo generated.')
            ->setStatusCode(303);
    }

    public function download(Memo $memo)
    {
        Gate::authorize('download-memos');

        if ($memo->company_id !== null) {
            abort(404);
        }

        abort_unless(Storage::disk('local')->exists($memo->pdf_path), 404);

        $absolutePath = Storage::disk('local')->path($memo->pdf_path);
        abort_unless(is_file($absolutePath), 404);

        $filename = Str::slug($memo->title) ?: 'memo';
        $filename = Str::limit($filename, 80, '');

        return response()->download($absolutePath, $filename . '.pdf', [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
