<?php

namespace Database\Seeders;

use App\Models\MemoTemplate;
use Illuminate\Database\Seeder;

class MemoTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'name' => 'Notice to Explain (NTE)',
                'slug' => 'notice-to-explain',
                'description' => 'Request for explanation regarding an incident.',
                'body_html' => $this->nteHtml(),
            ],
            [
                'name' => 'Written Warning',
                'slug' => 'written-warning',
                'description' => 'Formal warning memo for policy/behavior/performance issues.',
                'body_html' => $this->writtenWarningHtml(),
            ],
            [
                'name' => 'Preventive Suspension Notice',
                'slug' => 'preventive-suspension-notice',
                'description' => 'Preventive suspension notice pending investigation.',
                'body_html' => $this->preventiveSuspensionHtml(),
            ],
        ];

        foreach ($templates as $t) {
            MemoTemplate::query()->updateOrCreate(
                [
                    'company_id' => null,
                    'slug' => $t['slug'],
                ],
                [
                    'name' => $t['name'],
                    'description' => $t['description'],
                    'body_html' => $t['body_html'],
                    'is_active' => true,
                    'is_system' => true,
                    'created_by_user_id' => null,
                ]
            );
        }
    }

    private function nteHtml(): string
    {
        return trim(''
            . '<div>'
            . '<p><strong>{{company_name}}</strong></p>'
            . '<p><strong>NOTICE TO EXPLAIN (NTE)</strong></p>'
            . '<p>Date: <strong>{{memo_date}}</strong></p>'
            . '<br />'
            . '<p>To: <strong>{{employee_name}}</strong> ({{employee_id}})</p>'
            . '<p>Position: <strong>{{employee_position}}</strong></p>'
            . '<br />'
            . '<p>This is to inform you that an incident was recorded with the following details:</p>'
            . '<ul>'
            . '<li>Incident Date: <strong>{{incident_date}}</strong></li>'
            . '<li>Category: <strong>{{incident_category}}</strong></li>'
            . '</ul>'
            . '<p><strong>Incident Description:</strong></p>'
            . '<p>{{incident_description}}</p>'
            . '<br />'
            . '<p>You are hereby required to submit a written explanation within 48 hours from receipt of this notice.</p>'
            . '<br />'
            . '<p>Sincerely,</p>'
            . '<p><strong>{{hr_signatory_name}}</strong></p>'
            . '</div>'
        );
    }

    private function writtenWarningHtml(): string
    {
        return trim(''
            . '<div>'
            . '<p><strong>{{company_name}}</strong></p>'
            . '<p><strong>WRITTEN WARNING</strong></p>'
            . '<p>Date: <strong>{{memo_date}}</strong></p>'
            . '<br />'
            . '<p>To: <strong>{{employee_name}}</strong> ({{employee_id}})</p>'
            . '<p>Position: <strong>{{employee_position}}</strong></p>'
            . '<br />'
            . '<p>This written warning is issued in relation to the incident below:</p>'
            . '<ul>'
            . '<li>Incident Date: <strong>{{incident_date}}</strong></li>'
            . '<li>Category: <strong>{{incident_category}}</strong></li>'
            . '</ul>'
            . '<p><strong>Details:</strong></p>'
            . '<p>{{incident_description}}</p>'
            . '<br />'
            . '<p>Please ensure immediate corrective action. Repetition of similar violations may lead to further disciplinary action up to and including termination.</p>'
            . '<br />'
            . '<p>Issued by:</p>'
            . '<p><strong>{{hr_signatory_name}}</strong></p>'
            . '</div>'
        );
    }

    private function preventiveSuspensionHtml(): string
    {
        return trim(''
            . '<div>'
            . '<p><strong>{{company_name}}</strong></p>'
            . '<p><strong>PREVENTIVE SUSPENSION NOTICE</strong></p>'
            . '<p>Date: <strong>{{memo_date}}</strong></p>'
            . '<br />'
            . '<p>To: <strong>{{employee_name}}</strong> ({{employee_id}})</p>'
            . '<p>Position: <strong>{{employee_position}}</strong></p>'
            . '<br />'
            . '<p>Pending investigation of the following incident, you are placed under preventive suspension:</p>'
            . '<ul>'
            . '<li>Incident Date: <strong>{{incident_date}}</strong></li>'
            . '<li>Category: <strong>{{incident_category}}</strong></li>'
            . '</ul>'
            . '<p><strong>Incident Description:</strong></p>'
            . '<p>{{incident_description}}</p>'
            . '<br />'
            . '<p>Further instructions will be communicated upon completion of the investigation.</p>'
            . '<br />'
            . '<p>Issued by:</p>'
            . '<p><strong>{{hr_signatory_name}}</strong></p>'
            . '</div>'
        );
    }
}
