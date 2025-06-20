<?php

namespace App\Events;

use App\Models\Campaign;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event for broadcasting campaign status updates
 */
class CampaignStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Campaign $campaign;
    public array $updateData;

    /**
     * Create a new event instance
     */
    public function __construct(Campaign $campaign, array $updateData)
    {
        $this->campaign = $campaign;
        $this->updateData = $updateData;
    }

    /**
     * Get the channels the event should broadcast on
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("campaign.{$this->campaign->id}"),
            new PrivateChannel("user.campaigns.{$this->campaign->user_id}")
        ];
    }

    /**
     * Get the data to broadcast
     */
    public function broadcastWith(): array
    {
        return [
            'campaign_id' => $this->campaign->id,
            'user_id' => $this->campaign->user_id,
            'update_data' => $this->updateData,
            'timestamp' => now()->toISOString()
        ];
    }

    /**
     * The event's broadcast name
     */
    public function broadcastAs(): string
    {
        return 'campaign.status.updated';
    }
}
