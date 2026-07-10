<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SiteEvaluation extends Model
{
    use HasFactory;
    protected $fillable = [
        'score',
        'comment',
        'url_evaluated',
        'user_agent',
        'ip_address',
        'user_id',
        'device_type',
        'browser',
        'browser_version',
        'os',
        'os_version',
        'screen_resolution',
        'language',
        'time_zone',
    ];
}
