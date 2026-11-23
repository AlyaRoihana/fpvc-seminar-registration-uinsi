<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Registration extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'seminar_id',
        'user_id',
        'ticket_code',
        'checked_in_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'checked_in_at' => 'datetime',
        ];
    }

    /**
     * Seminar yang diikuti melalui pendaftaran ini.
     */
    public function seminar(): BelongsTo
    {
        return $this->belongsTo(Seminar::class);
    }

    /**
     * Mahasiswa pemilik e-ticket ini.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
