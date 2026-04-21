<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Visit extends Model
{
    protected $fillable = [
        'patient_id',
        'patient_name',
        'doctor_id',
        'doctor_name',
        'appointment_id',
        'visit_date',
        'visit_time',
        'chief_complaint',
        'diagnosis',
        'medical_notes',
        'vital_signs',
        'consultation_fee',
        'status',
        'workflow_status',
        'visit_number',
    ];

    protected $casts = [
        'visit_date' => 'date',
        'visit_time' => 'datetime:H:i',
        'vital_signs' => 'json',
        'consultation_fee' => 'decimal:2',
        'workflow_status' => 'string',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function labOrders(): HasMany
    {
        return $this->hasMany(LabOrder::class);
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function latestLabOrder(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(LabOrder::class)->latestOfMany();
    }
}
