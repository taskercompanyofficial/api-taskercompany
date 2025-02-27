<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatRoom extends Model
{
    protected $fillable = ['complaint_id', 'applicant_whatsapp', 'user_id', 'status'];

    public function complaint()
    {
        return $this->belongsTo(Complaint::class);
    }

    public function agent()
    {
        return $this->belongsTo(Staff::class);
    }
}
