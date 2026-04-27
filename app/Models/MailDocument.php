<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MailDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'referential_number',
        'serial_number',
        'entity_id',
        'sender_name_manual',
        'recipient_name_manual',
        'subject',
        'description',
        'date',
        'registered_at',
        'messenger_name',
        'messenger_phone',
        'employee_id',
        'attachment_path',
        'pages_count',
    ];

    protected $casts = [
        'date' => 'date',
        'registered_at' => 'date',
    ];

    public function entity()
    {
        return $this->belongsTo(ExternalEntity::class, 'entity_id');
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    /**
     * Path relative to storage.
     */
    protected $appends = ['attachment_url'];

    public function getAttachmentUrlAttribute()
    {
        if (!$this->attachment_path)
            return null;
        return '/storage/' . $this->attachment_path;
    }
}
