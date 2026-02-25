<?php

namespace App\DTO;

use Illuminate\Http\Request;

class ExpiringDocumentsFilterData
{
    private function __construct(
        public readonly int $days,
        public readonly bool $expiredOnly,
        public readonly string $search,
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        $days = (int) $request->query('days', 30);
        if (!in_array($days, [30, 60, 90], true)) {
            $days = 30;
        }

        $expiredOnly = (bool) $request->boolean('expired', false);
        $search = trim((string) $request->query('search', ''));

        return new self($days, $expiredOnly, $search);
    }

    /**
     * @return array{days:int,expired:int,search:string}
     */
    public function toFiltersArray(): array
    {
        return [
            'days' => $this->days,
            'expired' => $this->expiredOnly ? 1 : 0,
            'search' => $this->search,
        ];
    }
}
