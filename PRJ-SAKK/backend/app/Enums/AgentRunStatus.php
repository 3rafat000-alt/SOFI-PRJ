<?php

declare(strict_types=1);

namespace App\Enums;

enum AgentRunStatus: string
{
    case RUNNING = 'running';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case TIMEOUT = 'timeout';
    case SKIPPED = 'skipped';

    public function isTerminal(): bool
    {
        return in_array($this, [
            self::COMPLETED,
            self::FAILED,
            self::TIMEOUT,
            self::SKIPPED,
        ]);
    }

    public function labelAr(): string
    {
        return match ($this) {
            self::RUNNING => 'قيد التشغيل',
            self::COMPLETED => 'مكتمل',
            self::FAILED => 'فشل',
            self::TIMEOUT => 'انتهاء مهلة',
            self::SKIPPED => 'تم التخطي',
        };
    }
}
