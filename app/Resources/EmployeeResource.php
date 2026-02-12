<?php

namespace App\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
	/**
	 * Transform the resource into an array.
	 *
	 * @return array<string, mixed>
	 */
	public function toArray(Request $request): array
	{
		return [
			'employee_id' => $this->employee_id,
			'department_id' => $this->department_id,
			'employee_code' => $this->employee_code,
			'has_photo' => (bool) $this->has_photo,
			'photo_url' => $this->photo_url,
			'first_name' => $this->first_name,
			'middle_name' => $this->middle_name,
			'last_name' => $this->last_name,
			'suffix' => $this->suffix,
			'email' => $this->email,
			'mobile_number' => $this->mobile_number,
			'status' => $this->status,
			'position_title' => $this->position_title,
			'date_hired' => $this->date_hired?->format('Y-m-d'),
			'regularization_date' => $this->regularization_date?->format('Y-m-d'),
			'employment_type' => $this->employment_type,
			'notes' => $this->notes,
			'created_by' => $this->created_by,
			'updated_by' => $this->updated_by,
			'deleted_by' => $this->deleted_by,
			'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
			'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
			'deleted_at' => $this->deleted_at?->format('Y-m-d H:i:s'),
		];
	}
}
