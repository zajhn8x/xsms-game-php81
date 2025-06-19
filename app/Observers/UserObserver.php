<?php

namespace App\Observers;

use App\Models\User;
use App\Services\WalletService;

class UserObserver
{
    protected $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        // Automatically create wallet for new user
        $this->walletService->createWallet($user->id);
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        //
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        // When user is deleted, their wallet will be cascaded deleted
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        //
    }

    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $user): void
    {
        //
    }
}
