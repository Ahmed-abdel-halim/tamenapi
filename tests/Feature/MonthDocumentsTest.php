<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\BranchAgent;
use App\Models\InsuranceDocument;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class MonthDocumentsTest extends TestCase
{
    use DatabaseTransactions;

    private $user;
    private $agent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'username' => 'monthdocuser',
            'name'     => 'Month Doc User',
            'password' => bcrypt('password'),
            'is_admin' => true,
        ]);

        $this->agent = BranchAgent::create([
            'code'        => 'AGM100',
            'agency_name' => 'Month Doc Agency',
            'agent_name'  => 'Manager Name',
            'status'      => 'نشط',
            'contract_date'=> '2026-01-01',
            'city'        => 'طرابلس',
            'type'        => 'وكيل',
        ]);
    }

    public function test_get_agent_month_documents()
    {
        InsuranceDocument::create([
            'insurance_type'   => 'تأمين إجباري سيارات',
            'insurance_number' => 'DOCM1001',
            'issue_date'       => '2026-07-15',
            'start_date'       => '2026-07-15',
            'end_date'         => '2027-07-14',
            'insured_name'     => 'علي حسن',
            'premium'          => 100.0,
            'total'            => 120.0,
            'branch_agent_id'  => $this->agent->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')->getJson(
            "/api/financial-statistics/agent-month-documents?agent_id={$this->agent->id}&year=2026&month=7"
        );

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('summary.total_documents', 1);
    }

    public function test_update_agent_month_document()
    {
        $doc = InsuranceDocument::create([
            'insurance_type'   => 'تأمين إجباري سيارات',
            'insurance_number' => 'DOCM1002',
            'issue_date'       => '2026-07-15',
            'start_date'       => '2026-07-15',
            'end_date'         => '2027-07-14',
            'insured_name'     => 'خالد محمود',
            'premium'          => 100.0,
            'total'            => 120.0,
            'branch_agent_id'  => $this->agent->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')->putJson(
            '/api/financial-statistics/agent-month-document',
            [
                'table'           => 'insurance_documents',
                'id'              => $doc->id,
                'insured_name'    => 'خالد محمود المعدل',
                'document_number' => 'DOCM1002-MOD',
                'total'           => 150.0,
                'premium'         => 130.0,
            ]
        );

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        $this->assertDatabaseHas('insurance_documents', [
            'id'               => $doc->id,
            'insured_name'     => 'خالد محمود المعدل',
            'insurance_number' => 'DOCM1002-MOD',
            'total'            => 150.0,
        ]);
    }

    public function test_delete_agent_month_document()
    {
        $doc = InsuranceDocument::create([
            'insurance_type'   => 'تأمين إجباري سيارات',
            'insurance_number' => 'DOCM1003',
            'issue_date'       => '2026-07-15',
            'start_date'       => '2026-07-15',
            'end_date'         => '2027-07-14',
            'insured_name'     => 'سالم الفيتوري',
            'premium'          => 100.0,
            'total'            => 120.0,
            'branch_agent_id'  => $this->agent->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')->deleteJson(
            '/api/financial-statistics/agent-month-document',
            [
                'table' => 'insurance_documents',
                'id'    => $doc->id,
            ]
        );

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        $this->assertDatabaseMissing('insurance_documents', [
            'id' => $doc->id,
        ]);
    }
}
