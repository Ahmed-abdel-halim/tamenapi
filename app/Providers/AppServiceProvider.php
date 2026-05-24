<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        \Illuminate\Database\Eloquent\Relations\Relation::morphMap([
            'InsuranceDocument' => \App\Models\InsuranceDocument::class,
            'InternationalInsuranceDocument' => \App\Models\InternationalInsuranceDocument::class,
            'TravelInsuranceDocument' => \App\Models\TravelInsuranceDocument::class,
            'ResidentInsuranceDocument' => \App\Models\ResidentInsuranceDocument::class,
            'MarineStructureInsuranceDocument' => \App\Models\MarineStructureInsuranceDocument::class,
            'ProfessionalLiabilityInsuranceDocument' => \App\Models\ProfessionalLiabilityInsuranceDocument::class,
            'PersonalAccidentInsuranceDocument' => \App\Models\PersonalAccidentInsuranceDocument::class,
            'SchoolStudentInsuranceDocument' => \App\Models\SchoolStudentInsuranceDocument::class,
            'CashInTransitInsuranceDocument' => \App\Models\CashInTransitInsuranceDocument::class,
            'CargoInsuranceDocument' => \App\Models\CargoInsuranceDocument::class,
        ]);

        $policyModels = [
            \App\Models\InsuranceDocument::class,
            \App\Models\InternationalInsuranceDocument::class,
            \App\Models\TravelInsuranceDocument::class,
            \App\Models\ResidentInsuranceDocument::class,
            \App\Models\MarineStructureInsuranceDocument::class,
            \App\Models\ProfessionalLiabilityInsuranceDocument::class,
            \App\Models\PersonalAccidentInsuranceDocument::class,
            \App\Models\SchoolStudentInsuranceDocument::class,
            \App\Models\CashInTransitInsuranceDocument::class,
            \App\Models\CargoInsuranceDocument::class,
        ];

        foreach ($policyModels as $modelClass) {
            $modelClass::created(function ($policy) {
                try {
                    \App\Services\AgentRewardService::rewardForPolicy($policy);
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Failed to reward agent: ' . $e->getMessage());
                }
            });
        }
    }
}
