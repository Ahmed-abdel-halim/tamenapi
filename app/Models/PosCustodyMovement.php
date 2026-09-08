<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PosCustodyMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference_no',
        'pos_machine_id',
        'branch_agent_id',
        'movement_type',
        'movement_date',
        'device_condition',
        'accessories',
        'return_reason',
        'financial_clearance',
        'received_by_name',
        'notes',
        'processed_by',
        'status',
    ];

    protected $casts = [
        'movement_date' => 'date',
        'accessories'   => 'array',
    ];

    // ─── Relations ──────────────────────────────────────────────────────────────

    public function machine()
    {
        return $this->belongsTo(PosMachine::class, 'pos_machine_id');
    }

    public function agent()
    {
        return $this->belongsTo(BranchAgent::class, 'branch_agent_id');
    }

    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    // ─── Static Helpers ──────────────────────────────────────────────────────────

    /**
     * توليد رقم مرجعي تسلسلي بشكل آمن
     * handover  → POS-TR-2026-0001
     * return    → POS-RET-2026-0001
     */
    public static function generateReferenceNo(string $movementType): string
    {
        $year   = now()->year;
        $prefix = in_array($movementType, ['handover', 'maintenance_out']) ? 'POS-TR' : 'POS-RET';

        $lastRef = self::where('reference_no', 'like', "{$prefix}-{$year}-%")
            ->lockForUpdate()
            ->orderBy('id', 'desc')
            ->value('reference_no');

        $seq = 1;
        if ($lastRef) {
            $parts = explode('-', $lastRef);
            $seq   = ((int) end($parts)) + 1;
        }

        return sprintf('%s-%d-%04d', $prefix, $year, $seq);
    }

    // ─── Labels ──────────────────────────────────────────────────────────────────

    public function getMovementTypeLabelAttribute(): string
    {
        return match ($this->movement_type) {
            'handover'        => 'تسليم عهدة لوكيل',
            'return'          => 'إرجاع عهدة للشركة',
            'maintenance_out' => 'إرسال للصيانة',
            'maintenance_in'  => 'استلام من الصيانة',
            default           => $this->movement_type,
        };
    }

    public function getFinancialClearanceLabelAttribute(): string
    {
        return match ($this->financial_clearance) {
            'cleared'       => 'تم إبراء الذمة',
            'pending_audit' => 'قيد المراجعة',
            'has_deductions'=> 'يوجد خصومات',
            default         => $this->financial_clearance,
        };
    }
}
