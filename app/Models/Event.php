<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $table = 'tbl_t_deviasi_dsm';
    
    public $timestamps = false;
    
    protected $primaryKey = 'ID';
    
    protected $fillable = [
        'Alarm_id',
        'No_Unit',
        'Alarm',
        'Tanggal',
        'Location',
        'Speed',
        'IsVideoSent',
        'VideoFileName',
    ];

    protected $casts = [
        'Tanggal' => 'datetime',
        'Speed' => 'float',
        'IsVideoSent' => 'boolean',
    ];

    // ✅ OVERRIDE getAttribute untuk handle case-sensitive column names
    public function getAttribute($key)
    {
        // Map lowercase to actual column names
        $mapping = [
            'id' => 'ID',
            'alarm_id' => 'Alarm_id',
            'no_unit' => 'No_Unit',
            'alarm' => 'Alarm',
            'tanggal' => 'Tanggal',
            'location' => 'Location',
            'speed' => 'Speed',
        ];

        if (array_key_exists($key, $mapping)) {
            return parent::getAttribute($mapping[$key]);
        }

        return parent::getAttribute($key);
    }

    public function hasVideo()
    {
        return $this->IsVideoSent && !empty($this->VideoFileName);
    }

    public function getVideoPath()
    {
        if (!$this->hasVideo()) {
            return null;
        }

        $basePath = config('filesystems.disks.videos.root');
        $date = $this->getAttribute('Tanggal')->format('Ymd');
        $unit = $this->getAttribute('No_Unit');
        
        return "{$basePath}\\{$date}\\{$unit}\\{$this->VideoFileName}";
    }

    public function getVideoUrl()
    {
        if (!$this->hasVideo()) {
            return null;
        }

        $date = $this->getAttribute('Tanggal')->format('Ymd');
        $unit = $this->getAttribute('No_Unit');
        
        return route('video.stream', [
            'date' => $date,
            'unit' => $unit,
            'filename' => $this->VideoFileName
        ]);
    }
}