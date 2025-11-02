<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IncidentStatusLog extends Model
{
    use HasFactory;

    protected $table = 'incident_status_logs';

    protected $fillable = [
        'incident_id',
        'old_status',
        'new_status',
        'updated_by',
    ];

    public function incident()
    {
        return $this->belongsTo(IncidentReport::class, 'incident_id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
