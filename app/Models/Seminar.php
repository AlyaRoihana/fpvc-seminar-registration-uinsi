<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Seminar extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'speaker',
        'seminar_date',
        'location',
        'capacity',
        'registered_count',
        'created_by',
    ];

    /**
     * Atribut turunan yang ikut disertakan pada response JSON.
     *
     * @var array<int, string>
     */
    protected $appends = ['remaining_seats'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'seminar_date' => 'datetime',
        ];
    }

    /**
     * Sisa kursi = kapasitas dikurangi jumlah pendaftar.
     */
    public function getRemainingSeatsAttribute(): int
    {
        return $this->capacity - $this->registered_count;
    }

    /**
     * Panitia yang membuat seminar ini.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Pendaftaran/e-ticket untuk seminar ini.
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }
}
