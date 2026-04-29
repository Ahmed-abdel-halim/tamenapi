<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExternalEntity extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'entity_number',
        'address',
        'phone',
        'email',
        'default_messenger_name',
        'default_messenger_phone',
        'logo_path',
    ];

    protected $appends = ['logo_url'];

    public function getLogoUrlAttribute()
    {
        return $this->logo_path ? '/storage/' . $this->logo_path : null;
    }

    /**
     * Get the mail documents associated with this entity.
     */
    public function mailDocuments()
    {
        return $this->hasMany(MailDocument::class, 'entity_id');
    }
}
