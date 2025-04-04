<?php

namespace App\Enums;

enum ProcessingStatus: string
{
case
    PENDING = 'pending';
case
    IN_PROGRESS = 'in_progress';
case
    COMPLETED = 'completed';

    public
    static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public
    function label(): string
    {
        return match ($this) {
            self::PENDING => 'Đang chờ',
            self::IN_PROGRESS => 'Đang xử lý',
            self::COMPLETED => 'Hoàn thành',
        };
    }
} 
