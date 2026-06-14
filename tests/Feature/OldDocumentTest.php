<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\BranchAgent;
use App\Models\InsuranceDocument;
use App\Models\TravelInsuranceDocument;
use App\Models\TravelInsurancePassenger;
use App\Models\ResidentInsuranceDocument;
use App\Models\ResidentInsurancePassenger;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Carbon\Carbon;

class OldDocumentTest extends TestCase
{
    use DatabaseTransactions;

    private $user;
    private $agent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'username' => 'testuser',
            'name' => 'Test User',
            'password' => bcrypt('password'),
            'is_admin' => true,
        ]);

        $this->agent = BranchAgent::create([
            'code' => 'BK0001',
            'agency_name' => 'Test Agent',
            'agent_name' => 'Agent Manager',
            'status' => 'نشط',
            'contract_date' => now()->toDateString(),
            'city' => 'طرابلس',
            'type' => 'وكيل',
        ]);
    }

    public function test_compulsory_old_document_creation()
    {
        $issueDate = '2024-05-10';

        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/old-documents', [
            'document_type' => 'compulsory',
            'branch_agent_id' => $this->agent->id,
            'issue_date' => $issueDate,
            'start_date' => '2024-05-10',
            'end_date' => '2025-05-09',
            'document_number' => 'TESTCOMP123',
            'insured_name' => 'أحمد العميل',
            'nid_passport' => '1234567890',
            'phone' => '0912345678',
            'premium' => 120.0,
            'tax' => 10.0,
            'stamp' => 1.5,
            'issue_fees' => 5.0,
            'supervision_fees' => 2.0,
            'total' => 138.5,
            'plate_number_manual' => '12345',
            'chassis_number' => 'CHASSIS987654321',
            'color' => 'أحمر',
            'year' => 2020,
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('success', true);

        // Verify it was stored with the custom issue date as created_at and updated_at
        $this->assertDatabaseHas('insurance_documents', [
            'insurance_number' => 'TESTCOMP123',
            'branch_agent_id' => $this->agent->id,
            'insured_name' => 'أحمد العميل',
            'eidc_sync_status' => 'synced',
        ]);

        $doc = InsuranceDocument::where('insurance_number', 'TESTCOMP123')->first();
        $this->assertEquals(Carbon::parse($issueDate)->toDateString(), $doc->created_at->toDateString());
        $this->assertEquals(Carbon::parse($issueDate)->toDateString(), $doc->updated_at->toDateString());
        $this->assertEquals(Carbon::parse($issueDate)->toDateString(), Carbon::parse($doc->issue_date)->toDateString());
    }

    public function test_travel_old_document_creation_with_passenger()
    {
        $issueDate = '2023-10-15';

        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/old-documents', [
            'document_type' => 'travel',
            'branch_agent_id' => $this->agent->id,
            'issue_date' => $issueDate,
            'start_date' => '2023-10-15',
            'end_date' => '2024-10-14',
            'document_number' => 'TESTTRV123',
            'insured_name' => 'محمد المسافر',
            'name_en' => 'Mohamed Traveler',
            'nid_passport' => 'A1234567',
            'passport_number' => 'A1234567',
            'phone' => '0921111111',
            'birth_date' => '1990-01-01',
            'age' => 33,
            'gender' => 'ذكر',
            'nationality' => 'ليبي',
            'premium' => 150.0,
            'total' => 150.0,
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('success', true);

        // Verify document
        $this->assertDatabaseHas('travel_insurance_documents', [
            'insurance_number' => 'TESTTRV123',
            'branch_agent_id' => $this->agent->id,
        ]);

        $doc = TravelInsuranceDocument::where('insurance_number', 'TESTTRV123')->first();
        $this->assertEquals(Carbon::parse($issueDate)->toDateString(), $doc->created_at->toDateString());
        $this->assertEquals(Carbon::parse($issueDate)->toDateString(), $doc->updated_at->toDateString());

        // Verify passenger
        $this->assertDatabaseHas('travel_insurance_passengers', [
            'travel_insurance_document_id' => $doc->id,
            'is_main_passenger' => true,
            'name_ar' => 'محمد المسافر',
            'name_en' => 'Mohamed Traveler',
            'passport_number' => 'A1234567',
        ]);
    }
}
