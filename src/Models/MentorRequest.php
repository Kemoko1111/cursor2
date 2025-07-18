<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MentorRequest extends Model
{
    protected $table = 'mentor_requests';

    protected $fillable = [
        'mentee_id',
        'mentor_id',
        'status' // pending, accepted, rejected, cancelled
    ];

    public function mentee()
    {
        return $this->belongsTo(User::class, 'mentee_id');
    }

    public function mentor()
    {
        return $this->belongsTo(User::class, 'mentor_id');
    }
}