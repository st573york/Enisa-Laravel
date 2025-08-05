<?php

namespace Tests\Feature;

use App\HelperFunctions\EcasTestHelper;
use App\Models\Audit;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_audit()
    {
        $response = $this->get('/audit');
        $response->assertOk()->assertViewIs('components.auth-failed');
    }

    public function test_authorized_audit()
    {
        $user = EcasTestHelper::validateTestUser('admin');
        $response = $this->actingAs($user)->get('/audit');
        $response->assertOk()->assertViewIs('components.audit')->assertViewHasAll(['dateToday', 'models', 'events']);
    }

    public function test_unauthenticated_audit_list()
    {
        $response = $this->get('/audit/list', []);
        $response->assertOk()->assertViewIs('components.auth-failed');
    }

    public function test_authenticated_audit_list()
    {
        $user = EcasTestHelper::validateTestUser('admin');
        $models = Audit::getAuditByField('auditable_type');   
        $events = Audit::getAuditByField('event'); 
       
        $response = $this->actingAs($user)->call('get', '/audit/list', [
            'draw' => '1',
            'columns' => [
                [
                    'data' => 'id'
                ]
            ],
            'order' => [
                [
                    'column' => '0', 
                    'dir' => 'desc'
                ]
            ],
            'start' => '0',
            'length' => '10',
            'search' => [
                'value' => null, 
                'regex' => false
            ],
            'minDate' => Carbon::today()->format('d-m-Y'),
            'maxDate' => Carbon::today()->format('d-m-Y'),
            'model' => $models[0],
            'event' => $events[0]
        ]);
        $response->assertOk();
        $response->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data']);
    }

    public function test_unauthenticated_audit_changes_show()
    {
        $response = $this->get('/audit/changes/show');
        $response->assertOk()->assertViewIs('components.auth-failed');
    }

    public function test_authenticated_audit_changes_show()
    {
        $user = EcasTestHelper::validateTestUser('admin');
        $response = $this->actingAs($user)->get('/audit/changes/show');
        $response->assertOk()->assertViewIs('ajax.audit-changes');
    }

    public function test_unauthenticated_audit_changes_list()
    {
        Audit::setCustomAuditEvent(
            User::first(),
            ['event' => 'logged in']
        );
        $audit = Audit::first();
        $response = $this->get('/audit/changes/list/' . $audit->id);
        $response->assertOk()->assertViewIs('components.auth-failed');
    }

    public function test_authenticated_audit_changes_list()
    {
        $user = EcasTestHelper::validateTestUser('admin');
        $audit = Audit::first();
        $response = $this->actingAs($user)->get('/audit/changes/list/' . $audit->id);
        $response->assertOk();
        $response->assertJsonStructure(['data']);
    }
}
