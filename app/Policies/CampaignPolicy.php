<?php

namespace App\Policies;

use App\Models\Campaign;
use App\Models\User;

class CampaignPolicy
{
    public function view(User $user, Campaign $campaign)
    {
        return $user->id === $campaign->user_id;
    }

    public function create(User $user)
    {
        return true;
    }

    public function delete(User $user, Campaign $campaign)
    {
        return $user->id === $campaign->user_id;
    }
}
