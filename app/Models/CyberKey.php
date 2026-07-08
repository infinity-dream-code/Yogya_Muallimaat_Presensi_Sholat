<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CyberKey extends Model
{
    protected $table = 'cyber_key';

    protected $primaryKey = 'urut';

    public $incrementing = true;

    public $timestamps = false;

    protected $fillable = [
        'users',
        'kunci',
        'fid',
        'ket',
        'kel',
        'urut',
        'password',
    ];
}
