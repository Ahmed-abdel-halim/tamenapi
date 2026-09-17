<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;
use Illuminate\Http\Request;

class ExchangeRateController extends Controller
{
    public function index()
    {
        return response()->json([
            'usd_to_lyd' => (float) SiteSetting::getValue('usd_to_lyd', 7.15),
            'tnd_to_lyd' => (float) SiteSetting::getValue('tnd_to_lyd', 2.30),
            'eur_to_lyd' => (float) SiteSetting::getValue('eur_to_lyd', 7.65),
            'updated_at' => SiteSetting::getValue('exchange_rates_updated_at', now()->toIso8601String()),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'usd_to_lyd' => 'required|numeric|min:0.01',
            'tnd_to_lyd' => 'nullable|numeric|min:0.01',
            'eur_to_lyd' => 'nullable|numeric|min:0.01',
        ]);

        SiteSetting::setValue('usd_to_lyd', (string) $validated['usd_to_lyd']);
        if (isset($validated['tnd_to_lyd'])) {
            SiteSetting::setValue('tnd_to_lyd', (string) $validated['tnd_to_lyd']);
        }
        if (isset($validated['eur_to_lyd'])) {
            SiteSetting::setValue('eur_to_lyd', (string) $validated['eur_to_lyd']);
        }
        SiteSetting::setValue('exchange_rates_updated_at', now()->toIso8601String());

        return response()->json([
            'message' => 'تم تحديث أسعار الصرف بنجاح',
            'rates' => [
                'usd_to_lyd' => (float) SiteSetting::getValue('usd_to_lyd', 7.15),
                'tnd_to_lyd' => (float) SiteSetting::getValue('tnd_to_lyd', 2.30),
                'eur_to_lyd' => (float) SiteSetting::getValue('eur_to_lyd', 7.65),
                'updated_at' => SiteSetting::getValue('exchange_rates_updated_at'),
            ]
        ]);
    }
}
