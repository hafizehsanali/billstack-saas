<?php

namespace App\Services;

use App\Models\PlatformActivityLog;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Model;

class PlatformActivityService
{
    public function record(
        string $action,
        string $description,
        ?Model $subject = null,
        ?Tenant $tenant = null,
        array $metadata = []
    ): PlatformActivityLog {
        return PlatformActivityLog::create([
            'actor_id' => auth()->id(),
            'tenant_id' => $tenant?->id,
            'action' => $action,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'subject_label' => $this->subjectLabel($subject),
            'description' => $description,
            'metadata' => $metadata ?: null,
            'ip_address' => request()?->ip(),
        ]);
    }

    private function subjectLabel(?Model $subject): ?string
    {
        if (! $subject) {
            return null;
        }

        return $subject->name
            ?? $subject->invoice_no
            ?? $subject->code
            ?? class_basename($subject).' #'.$subject->getKey();
    }
}
