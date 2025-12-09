<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class NewEventNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $event;

    public function __construct($event)
    {
        $this->event = $event;
        \Log::info('🔔 NewEventNotification constructed', [
            'event_id' => $event->ID ?? $event->id
        ]);
    }

    public function via($notifiable): array
    {
        \Log::info('📬 Notification via called for user: ' . $notifiable->id);
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        // ✅ FIX: Tambah null check untuk Tanggal
        $tanggal = null;
        if ($this->event->Tanggal) {
            try {
                $tanggal = $this->event->Tanggal->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                $tanggal = $this->event->Tanggal; // fallback ke raw value
            }
        }

        $data = [
            'event_id' => $this->event->ID,
            'ID' => $this->event->ID,
            'Alarm_id' => $this->event->Alarm_id,      
            'No_Unit' => $this->event->No_Unit,        
            'Alarm' => $this->event->Alarm,            
            'Location' => $this->event->Location ?? '',      
            'Speed' => $this->event->Speed ?? 0,            
            'Tanggal' => $tanggal,  // ✅ SAFE NOW
            'IsVideoSent' => $this->event->IsVideoSent ?? false,
            'VideoFileName' => $this->event->VideoFileName ?? '',
            'message' => "Event baru: {$this->event->Alarm} - Unit {$this->event->No_Unit}",
        ];
        
        \Log::info('📦 Notification data prepared', $data);
        
        return $data;
    }
}