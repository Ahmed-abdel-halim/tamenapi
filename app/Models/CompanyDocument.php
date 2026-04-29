<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'document_number',

        'issue_date',
        'expiry_date',
        'attachments',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'attachments' => 'array',
    ];

    /**
     * Path relative to storage.
     */
    protected $appends = ['attachment_urls'];

    public function getAttachmentUrlsAttribute()
    {
        $urls = [];
        if ($this->attachments && is_array($this->attachments)) {
            foreach ($this->attachments as $path) {
                $urls[] = '/storage/' . $path;
            }
        }
        return $urls;
    }
}
