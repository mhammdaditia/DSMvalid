<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class NewEventCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $eventData;

    public function __construct($eventData)
    {
        $this->eventData = $eventData;
        Log::info('🔵 NewEventCreated constructor called', ['id' => $eventData->ID ?? $eventData->id]);
    }

    public function broadcastOn(): Channel
    {
        Log::info('🔵 broadcastOn() called - Channel: dsm-events');
        return new Channel('dsm-events');
    }

    public function broadcastAs(): string
    {
        Log::info('🔵 broadcastAs() called - Event: new-event');
        return 'new-event';
    }

    public function broadcastWith(): array
    {

        $data = [
            'ID' => $this->eventData->ID,
            'Alarm_id' => $this->eventData->Alarm_id,          
            'No_Unit' => $this->eventData->No_Unit,            
            'Alarm' => $this->eventData->Alarm,                
            'Tanggal' => $this->eventData->Tanggal->format('Y-m-d H:i:s'),
            'Location' => $this->eventData->Location,          
            'Speed' => $this->eventData->Speed,                
        ];
        
        Log::info('🔵 broadcastWith() called', $data);
        return $data;
    }
}