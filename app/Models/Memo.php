<?php

namespace App\Models;

use App\Models\Employee;
use App\Models\EmployeeIncident;
use App\Models\MemoTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Memo extends Model
{
    protected $fillable = [
        'company_id',
        'employee_id',
        'incident_id',
        'memo_template_id',
        'title',
        'body_rendered_html',
        'pdf_path',
        'status',
        'created_by_user_id',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'employee_id' => 'integer',
        'incident_id' => 'integer',
        'memo_template_id' => 'integer',
        'created_by_user_id' => 'integer',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }

    public function incident(): BelongsTo
    {
        return $this->belongsTo(EmployeeIncident::class, 'incident_id', 'id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(MemoTemplate::class, 'memo_template_id', 'id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id', 'id');
    }
}
