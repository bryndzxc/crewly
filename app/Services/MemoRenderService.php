<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeIncident;
use Illuminate\Support\Str;

class MemoRenderService extends Service
{
    /**
     * Render template HTML by replacing placeholders with escaped values.
     */
    public function render(string $templateHtml, array $vars): string
    {
        $replacements = [];

        foreach ($vars as $key => $value) {
            $safeKey = trim((string) $key);
            if ($safeKey === '') {
                continue;
            }

            $replacements['{{' . $safeKey . '}}'] = e((string) ($value ?? ''));
        }

        return strtr($templateHtml, $replacements);
    }

    /**
     * Build the MVP placeholder values.
     */
    public function buildIncidentVars(Employee $employee, ?EmployeeIncident $incident, array $overrides = []): array
    {
        $companyNameOverride = array_key_exists('company_name', $overrides) ? (string) ($overrides['company_name'] ?? '') : '';
        $companyName = trim($companyNameOverride);

        if ($companyName === '') {
            $companyId = (int) ($employee->company_id ?? 0);
            if ($companyId > 0) {
                $companyName = (string) (Company::query()->whereKey($companyId)->value('name') ?? '');
            }
        }

        $employeeName = trim(collect([
            $employee->first_name,
            $employee->middle_name,
            $employee->last_name,
            $employee->suffix,
        ])->filter(fn ($v) => trim((string) $v) !== '')->implode(' '));

        $vars = [
            'company_name' => $companyName,
            'employee_name' => $employeeName,
            'employee_id' => (string) ($employee->employee_code ?? $employee->employee_id ?? ''),
            'employee_position' => (string) ($employee->position_title ?? ''),
            'incident_date' => $incident?->incident_date?->toDateString() ?? '',
            'incident_category' => (string) ($incident?->category ?? ''),
            'incident_description' => (string) ($incident?->description ?? ''),
            'memo_date' => (string) ($overrides['memo_date'] ?? now()->toDateString()),
            'hr_signatory_name' => (string) ($overrides['hr_signatory_name'] ?? ''),
        ];

        // Optional summary override (maps into incident_description if provided)
        if (array_key_exists('incident_summary', $overrides) && $overrides['incident_summary'] !== null) {
            $vars['incident_description'] = (string) $overrides['incident_summary'];
        }

        // Allow explicit overrides of any placeholder.
        foreach ($overrides as $k => $v) {
            if (!is_string($k) || $k === '') {
                continue;
            }
            if (array_key_exists($k, $vars)) {
                $vars[$k] = (string) ($v ?? '');
            }
        }

        return $vars;
    }

    /**
     * Very small sanitizer for admin-entered HTML.
     * Removes script tags, inline event handlers, and javascript: URLs.
     */
    public function sanitizeTemplateHtml(string $html): string
    {
        $html = (string) $html;

        // Remove <script> blocks.
        $html = preg_replace('/<\s*script\b[^>]*>(.*?)<\s*\/\s*script\s*>/is', '', $html) ?? $html;

        // Remove inline event handlers (onclick, onload, ...).
        $html = preg_replace('/\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html) ?? $html;

        // Neutralize javascript: URLs.
        $html = preg_replace('/(href|src)\s*=\s*("|\')\s*javascript\s*:[^"\']*("|\')/i', '$1=$2#$3', $html) ?? $html;

        return trim($html);
    }

    public function defaultSlug(string $name): string
    {
        $slug = Str::slug((string) $name);
        return $slug !== '' ? $slug : Str::random(8);
    }
}
