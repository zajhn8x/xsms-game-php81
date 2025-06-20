<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event for broadcasting campaign metrics updates
 */
class CampaignMetricsUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $userId;
    public ?int $campaignId;
    public array $updateData;

    /**
     * Create a new event instance
     */
    public function __construct(int $userId, ?int $campaignId, array $updateData)
    {
        $this->userId = $userId;
        $this->campaignId = $campaignId;
        $this->updateData = $updateData;
    }

    /**
     * Get the channels the event should broadcast on
     */
    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel("user.campaigns.{$this->userId}")
        ];

        if ($this->campaignId) {
            $channels[] = new PrivateChannel("campaign.{$this->campaignId}");
        }

        return $channels;
    }

    /**
     * Get the data to broadcast
     */
    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->userId,
            'campaign_id' => $this->campaignId,
            'update_data' => $this->updateData,
            'timestamp' => now()->toISOString()
        ];
    }

    /**
     * The event's broadcast name
     */
    public function broadcastAs(): string
    {
        return 'campaign.metrics.updated';
    }
}
