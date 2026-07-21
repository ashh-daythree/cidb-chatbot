<?php

declare(strict_types=1);

namespace Cidb\Backend\Utils;

final class Logger
{
    public function __construct(
        private readonly string $logPath,
        private readonly string $minimumLevel = 'debug'
    ) {
        if (!is_dir($this->logPath)) {
            @mkdir($this->logPath, 0775, true);
        }
    }

    public function debug(string $message, array $context = []): void
    {
        $this->write('debug', $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->write('info', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->write('warning', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->write('error', $message, $context);
    }

    private function write(string $level, string $message, array $context = []): void
    {
        if (!$this->shouldLog($level)) {
            return;
        }

        $line = sprintf(
            "[%s] %s %s %s\n",
            strtoupper($level),
            (new \DateTimeImmutable())->format('Y-m-d H:i:sP'),
            $message,
            $context === [] ? '' : json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        file_put_contents($this->logPath . DIRECTORY_SEPARATOR . 'app.log', $line, FILE_APPEND | LOCK_EX);
    }

    private function shouldLog(string $level): bool
    {
        $levels = ['debug' => 0, 'info' => 1, 'warning' => 2, 'error' => 3];
        $current = $levels[$this->minimumLevel] ?? 0;
        $incoming = $levels[$level] ?? 0;

        return $incoming >= $current;
    }
}

