<?php

namespace CleaniqueCoders\LaravelOrganization\Events;

use CleaniqueCoders\LaravelOrganization\Models\OwnershipTransferRequest;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OwnershipTransferRequested
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public OwnershipTransferRequest $transferRequest,
    ) {}
}
