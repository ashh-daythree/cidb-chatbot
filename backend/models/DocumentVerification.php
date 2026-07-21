<?php

declare(strict_types=1);

namespace Cidb\Backend\Models;

final class DocumentVerification extends AbstractModel
{
    public function tableName(): string
    {
        return 'document_verifications';
    }

    public function primaryKey(): string
    {
        return 'id';
    }

    public function fillable(): array
    {
        return [
            'uploaded_document_id',
            'verification_type',
            'verifier',
            'status',
            'score',
            'reason_code',
            'reason_message',
            'details',
            'verified_at',
            'created_at',
        ];
    }

    public function relationships(): array
    {
        return [
            'uploadedDocument' => $this->belongsTo(UploadedDocument::class, 'uploaded_document_id'),
        ];
    }
}

