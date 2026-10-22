<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrafficRequestEvent extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'occurred_at',
        'route_name',
        'method',
        'status',
        'duration_ms',
        'client_ip',
        'session_hash',
        'user_agent_hash',
        'referer_hash',
        'user_id',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
    ];

    public function maskedIp(): string
    {
        if (filter_var($this->client_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return explode('.', $this->client_ip)[0].'.***.***.***';
        }

        if (filter_var($this->client_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return implode(':', array_slice(explode(':', $this->client_ip), 0, 2)).':****:****:****:****:****:****';
        }

        return 'No disponible';
    }

    public function shortFingerprint(?string $fingerprint): string
    {
        return $fingerprint ? substr($fingerprint, 0, 10) : 'No disponible';
    }
}
