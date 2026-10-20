<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompulsoryDocumentRequest extends Model
{
    use HasFactory;

    protected $table = 'compulsory_document_requests';

    protected $fillable = [
        'request_number',
        'reference_number',
        'request_date',
        'documents_count',
        'fee_per_document',
        'total_amount',
        'amount_in_words',
        'payment_method',
        'payment_reference',
        'payment_receipt_image',
        'attachments',
        'status',
        'signatory_name',
        'signatory_title',
        'subject',
        'addressed_to',
        'received_quantity',
        'received_date',
        'serial_from',
        'serial_to',
        'store_item_id',
        'warehouse_location',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'request_date' => 'date:Y-m-d',
        'received_date' => 'date:Y-m-d',
        'documents_count' => 'integer',
        'received_quantity' => 'integer',
        'fee_per_document' => 'float',
        'total_amount' => 'float',
        'attachments' => 'array',
    ];

    protected $appends = ['attachments_list'];

    public function getAttachmentsListAttribute()
    {
        $list = [];
        if (!empty($this->attachments)) {
            if (is_array($this->attachments)) {
                $list = $this->attachments;
            } elseif (is_string($this->attachments)) {
                $decoded = json_decode($this->attachments, true);
                $list = is_array($decoded) ? $decoded : [$this->attachments];
            }
        }
        if (empty($list) && !empty($this->payment_receipt_image)) {
            $list = [$this->payment_receipt_image];
        }
        return array_values(array_filter($list));
    }

    public function storeItem()
    {
        return $this->belongsTo(StoreItem::class, 'store_item_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
