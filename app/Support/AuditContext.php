<?php

namespace App\Support;

/**
 * Request-scoped holder for audit context (client IP + user agent).
 *
 * Populated by the AuditContext middleware so that audit records written
 * from observers or events — which do not receive the current Request —
 * still capture the correct actor network context.
 */
class AuditContext
{
    protected ?string $ipAddress = null;

    protected ?string $userAgent = null;

    public function set(string $ipAddress, ?string $userAgent): void
    {
        $this->ipAddress = $ipAddress;
        $this->userAgent = $userAgent;
    }

    public function clear(): void
    {
        $this->ipAddress = null;
        $this->userAgent = null;
    }

    public function ipAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function userAgent(): ?string
    {
        return $this->userAgent;
    }
}
