<?php

declare(strict_types=1);

namespace Cidb\Backend\Models;

final class CimsVerificationResult extends AbstractModel
{
    public function tableName(): string
    {
        return 'cims_verification_results';
    }

    public function primaryKey(): string
    {
        return 'id';
    }

    public function fillable(): array
    {
        return [
            'request_id',
            'attempt_no',
            'result_status',
            'response_code',
            'response_message',
            'external_reference_no',
            'latency_ms',
            'rpa_response_text',
            'display_message',
            'retry_available',
            'response_payload',
            'verified_at',
            'created_at',
        ];
    }

    public function relationships(): array
    {
        return [
            'serviceRequest' => $this->belongsTo(ServiceRequest::class, 'request_id'),
        ];
    }
}
