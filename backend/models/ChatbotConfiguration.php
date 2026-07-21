<?php

declare(strict_types=1);

namespace Cidb\Backend\Models;

final class ChatbotConfiguration extends AbstractModel
{
    public function tableName(): string
    {
        return 'chatbot_configuration';
    }

    public function primaryKey(): string
    {
        return 'id';
    }

    public function fillable(): array
    {
        return [
            'config_key',
            'config_group',
            'config_value',
            'is_sensitive',
            'description',
            'created_at',
            'updated_at',
        ];
    }

    public function relationships(): array
    {
        return [];
    }
}

