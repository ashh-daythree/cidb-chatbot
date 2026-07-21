<?php

declare(strict_types=1);

namespace Cidb\Backend\Models;

final class UploadedDocument extends AbstractModel
{
    public function tableName(): string
    {
        return 'uploaded_documents';
    }

    public function primaryKey(): string
    {
        return 'id';
    }

    public function fillable(): array
    {
        return [
            'session_id',
            'request_id',
            'document_type_code',
            'upload_source',
            'storage_disk',
            'storage_path',
            'storage_file_name',
            'original_file_name_ciphertext',
            'mime_type',
            'file_extension',
            'file_size_bytes',
            'sha256_checksum',
            'upload_status',
            'security_status',
            'metadata',
            'created_at',
            'updated_at',
        ];
    }

    public function relationships(): array
    {
        return [
            'session' => $this->belongsTo(ChatbotSession::class, 'session_id'),
            'serviceRequest' => $this->belongsTo(ServiceRequest::class, 'request_id'),
            'documentType' => $this->belongsTo(ReferenceDocumentType::class, 'document_type_code', 'document_type_code'),
            'verifications' => $this->hasMany(DocumentVerification::class, 'uploaded_document_id'),
            'statusHistory' => $this->hasMany(ChatbotStatusHistory::class, 'document_id'),
        ];
    }
}

