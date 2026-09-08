<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PosMachine extends Model
{
    protected $fillable = [
        'pos_code',
        'machine_name',
        'machine_serial',
        'bank_name',
        'merchant_id',
        'location',
        'is_active',
        'notes',
        'custody_status',
        'current_agent_id',
        'current_custody_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // ─── Relations ──────────────────────────────────────────────────────────────

    public function transactions()
    {
        return $this->hasMany(PosTransaction::class);
    }

    public function branchAgents()
    {
        return $this->belongsToMany(BranchAgent::class, 'agent_pos_machine', 'pos_machine_id', 'branch_agent_id');
    }

    public function custodyMovements()
    {
        return $this->hasMany(PosCustodyMovement::class, 'pos_machine_id')->orderBy('movement_date', 'desc')->orderBy('id', 'desc');
    }

    public function activeCustody()
    {
        return $this->hasOne(PosCustodyMovement::class, 'pos_machine_id')
                    ->where('status', 'active')
                    ->where('movement_type', 'handover')
                    ->latest();
    }

    public function currentAgent()
    {
        return $this->belongsTo(BranchAgent::class, 'current_agent_id');
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────────

    public function totalSales()
    {
        return $this->transactions()->sum('amount');
    }

    /**
     * توليد كود POS داخلي تلقائي مثل POS-001
     */
    public static function generatePosCode(): string
    {
        $last = self::whereNotNull('pos_code')
                    ->where('pos_code', 'like', 'POS-%')
                    ->orderByRaw('CAST(SUBSTRING(pos_code, 5) AS UNSIGNED) DESC')
                    ->value('pos_code');
        $seq = 1;
        if ($last) {
            $parts = explode('-', $last);
            $seq   = ((int) end($parts)) + 1;
        }
        return sprintf('POS-%03d', $seq);
    }

    // ─── Labels ──────────────────────────────────────────────────────────────────

    public function getCustodyStatusLabelAttribute(): string
    {
        return match ($this->custody_status ?? 'available') {
            'available'   => 'متاح بالشركة',
            'in_custody'  => 'بعهدة وكيل',
            'maintenance' => 'قيد الصيانة',
            'damaged'     => 'معطل / تالف',
            default       => $this->custody_status,
        };
    }
}

