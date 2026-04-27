<?php

namespace App\Mail;

use App\Models\MailDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MailDocumentMailable extends Mailable
{
    use Queueable, SerializesModels;

    public $document;

    public function __construct(MailDocument $document)
    {
        $this->document = $document;
    }

    public function build()
    {
        $email = $this->subject('مراسلة جديدة من شركة المدار الليبي للتأمين: ' . $this->document->subject)
                    ->view('emails.mail_document')
                    ->with([
                        'subject' => $this->document->subject,
                        'referential_number' => $this->document->referential_number,
                        'description' => $this->document->description,
                    ]);

        if ($this->document->attachment_path) {
            $email->attachFromStorageDisk('public', $this->document->attachment_path);
        }

        return $email;
    }
}
