<?php

namespace App\Enums;

enum ProgramStatus: string
{
    case Draft = 'draft';
    case PendingApproval = 'pending_approval';
    case Approved = 'approved';
    case Active = 'active';
    case Paused = 'paused';
    case Completed = 'completed';
    case Archived = 'archived';

    /**
     * @return list<self>
     */
    public function allowedNextStatuses(): array
    {
        return match ($this) {
            self::Draft => [self::PendingApproval, self::Archived],
            self::PendingApproval => [self::Approved, self::Draft],
            self::Approved => [self::Active, self::Archived],
            self::Active => [self::Paused, self::Completed, self::Archived],
            self::Paused => [self::Active, self::Archived],
            self::Completed => [self::Archived],
            self::Archived => [],
        };
    }
}
