<?php

declare(strict_types=1);

namespace Cidb\Backend\Repositories;

final class ChatbotConfigurationRepository extends BaseRepository
{
    protected function tableName(): string
    {
        return 'chatbot_configuration';
    }

    public function findByConfigKey(string $configKey): ?array
    {
        return $this->findOneBy(['config_key' => $configKey]);
    }

    public function findByGroup(string $configGroup, int $limit = 100, int $offset = 0): array
    {
        return $this->findAll(['config_group' => $configGroup], $limit, $offset, 'config_key', 'ASC');
    }

    public function findSensitive(int $limit = 100, int $offset = 0): array
    {
        return $this->findAll(['is_sensitive' => true], $limit, $offset, 'config_key', 'ASC');
    }
}

