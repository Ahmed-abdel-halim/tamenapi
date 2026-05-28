<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        $documentModels = [
            \App\Models\InsuranceDocument::class,
            \App\Models\TravelInsuranceDocument::class,
            \App\Models\ResidentInsuranceDocument::class,
            \App\Models\MarineStructureInsuranceDocument::class,
            \App\Models\ProfessionalLiabilityInsuranceDocument::class,
            \App\Models\PersonalAccidentInsuranceDocument::class,
            \App\Models\InternationalInsuranceDocument::class,
            \App\Models\SchoolStudentInsuranceDocument::class,
            \App\Models\CargoInsuranceDocument::class,
            \App\Models\CashInTransitInsuranceDocument::class,
        ];

        foreach ($documentModels as $modelClass) {
            $modelClass::created(function ($document) {
                try {
                    if ($document->branch_agent_id) {
                        $admins = \App\Models\User::where('is_admin', true)->get();
                        
                        $docTypeLabel = match (get_class($document)) {
                            \App\Models\InsuranceDocument::class => 'تأمين سيارات إجباري',
                            \App\Models\TravelInsuranceDocument::class => 'تأمين مسافرين',
                            \App\Models\ResidentInsuranceDocument::class => 'تأمين وافدين',
                            \App\Models\MarineStructureInsuranceDocument::class => 'تأمين هياكل بحرية',
                            \App\Models\ProfessionalLiabilityInsuranceDocument::class => 'تأمين مسؤولية مهنية',
                            \App\Models\PersonalAccidentInsuranceDocument::class => 'تأمين حوادث شخصية',
                            \App\Models\InternationalInsuranceDocument::class => 'تأمين سيارات دولي',
                            \App\Models\SchoolStudentInsuranceDocument::class => 'تأمين حماية طلاب المدارس',
                            \App\Models\CargoInsuranceDocument::class => 'تأمين شحن بضائع',
                            \App\Models\CashInTransitInsuranceDocument::class => 'تأمين نقل نقدية',
                            default => 'وثيقة تأمين جديدة'
                        };

                        $docNumber = $document->policy_number ?? $document->insurance_number ?? $document->document_number ?? $document->id;
                        $agentName = $document->branchAgent?->agency_name ?? 'الوكيل';
                        
                        $title = 'إصدار وثيقة جديدة';
                        $message = "قام الوكيل ({$agentName}) بإصدار وثيقة ({$docTypeLabel}) جديدة رقم: {$docNumber}";
                        
                        $url = match (get_class($document)) {
                            \App\Models\InsuranceDocument::class => "/insurance-documents/{$document->id}",
                            \App\Models\TravelInsuranceDocument::class => "/travel-insurance-documents/{$document->id}",
                            \App\Models\ResidentInsuranceDocument::class => "/resident-insurance-documents/{$document->id}",
                            \App\Models\MarineStructureInsuranceDocument::class => "/marine-structure-insurance-documents/{$document->id}",
                            \App\Models\ProfessionalLiabilityInsuranceDocument::class => "/professional-liability-insurance-documents/{$document->id}",
                            \App\Models\PersonalAccidentInsuranceDocument::class => "/personal-accident-insurance-documents/{$document->id}",
                            \App\Models\InternationalInsuranceDocument::class => "/international-insurance-documents/{$document->id}",
                            \App\Models\SchoolStudentInsuranceDocument::class => "/school-student-insurance/{$document->id}",
                            \App\Models\CargoInsuranceDocument::class => "/cargo-insurance/{$document->id}",
                            \App\Models\CashInTransitInsuranceDocument::class => "/cash-in-transit-insurance/{$document->id}",
                            default => "/dashboard"
                        };

                        foreach ($admins as $admin) {
                            $admin->notify(new \App\Notifications\SystemNotification($title, $message, 'success', $url));
                        }
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Error triggering document notification: ' . $e->getMessage());
                }
            });
        }
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
