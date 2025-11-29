<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $table = 'v2_ticket';
    protected $dateFormat = 'U';
    protected $guarded = ['id'];
    protected $casts = [
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp'
    ];

    /**
     * Get the messages for the ticket.
     */
    public function messages()
    {
        return $this->hasMany(TicketMessage::class, 'ticket_id');
    }

    /**
     * Get the user associated with the ticket.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
