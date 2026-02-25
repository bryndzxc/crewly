<?php

namespace App\DTO;

use Illuminate\Http\Request;

class AuditLogFilterData
{
    private function __construct(
        public readonly string $from,
        public readonly string $to,
        public readonly string $action,
        public readonly string $module,
        public readonly ?int $userId,
        public readonly int $perPage,
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        $perPage = min(max((int) $request->input('per_page', 15), 5), 100);

        return new self(
            from: $request->string('from')->toString(),
            to: $request->string('to')->toString(),
            action: $request->string('action')->toString(),
            module: $request->string('module')->toString(),
            userId: $request->integer('user_id') ?: null,
            perPage: $perPage,
        );
    }

    /**
     * @return array{from:string,to:string,action:string,module:string,user_id:int|null,per_page:int}
     */
    public function toArray(): array
    {
        return [
            'from' => $this->from,
            'to' => $this->to,
            'action' => $this->action,
            'module' => $this->module,
            'user_id' => $this->userId,
            'per_page' => $this->perPage,
        ];
    }
}
