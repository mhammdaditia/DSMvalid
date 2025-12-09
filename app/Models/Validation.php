<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Validation extends Model
{
    protected $table = 'validations';
    
    public $timestamps = true;
    
    protected $fillable = [
        'alarm_id',
        'no_unit',
        'alarm',
        'tanggal',
        'location',
        'speed',
        'status',
        'follow_up',
        'follow_up_file',
        'keterangan',
        'validated_by',
    ];
    
    protected $casts = [
        'tanggal' => 'datetime',
        'status' => 'boolean',
        'created_at' => 'datetime',   
        'updated_at' => 'datetime',   
    ];

    public function validator()
    {
        return $this->belongsTo(\App\Models\User::class, 'validated_by');
    }
}