<?php

namespace App\Services\GovernmentParsers;

interface GovernmentParser
{
    /**
     * @param  array{source_type:string,source_url:string,content_type:?string,detected_at:string}  $context
     * @return array Parsed payload suitable for government_update_drafts.parsed_payload
     */
    public function parse(string $rawContent, array $context): array;
}
