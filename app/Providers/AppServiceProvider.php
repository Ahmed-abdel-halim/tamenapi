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

    /**
     * Bootstrap any application services.
     */
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
    }
}
