<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Model
{
    use SoftDeletes;

    protected $table = 'users';
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'role_id',
        'bio',
        'skills',
        'availability',
        'email_verified_at'
    ];

    protected $hidden = ['password'];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function menteeRequests()
    {
        return $this->hasMany(MentorRequest::class, 'mentee_id');
    }

    public function mentoringRequests()
    {
        return $this->hasMany(MentorRequest::class, 'mentor_id');
    }
}