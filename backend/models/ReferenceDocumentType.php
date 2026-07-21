<?php

declare(strict_types=1);

namespace Cidb\Backend\Models;

final class ReferenceDocumentType extends AbstractModel
{
    public function tableName(): string
    {
        return 'reference_document_types';
    }

    public function primaryKey(): string
    {
        return 'document_type_code';
    }

    public function fillable(): array
    {
        return [
            'label_en',
            'label_ms',
            'capture_mode',
            'is_required_for_submission',
            'allow_multiple',
            'sort_order',
            'allowed_mime_types',
            'max_file_size_mb',
            'requires_ocr',
            'is_active',
            'created_at',
            'updated_at',
        ];
    }

    public function relationships(): array
    {
        return [
            'uploadedDocuments' => $this->hasMany(UploadedDocument::class, 'document_type_code', 'document_type_code'),
        ];
    }
}

