<?php

namespace Tests\Feature;

use App\HelperFunctions\EcasTestHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentLibraryControllerTest extends TestCase
{
    use RefreshDatabase;

    const DOCUMENTS = [
        'cybersecurity-policies.pdf',
        'disaster-recovery.pdf',
        'end-user-statement.pdf',
        'data-privacy-statement.pdf',
        'user-manual-PoC.pdf',
        'eu-login-user-manual.pdf'
    ];

    public function test_unauthenticated_document_view()
    {
        $response = $this->get('/documents-library');
        $response->assertOk()->assertViewIs('components.auth-failed');
    }

    public function test_authenticated_document_view()
    {
        $user = EcasTestHelper::validateTestUser('admin');
        $response = $this->actingAs($user)->get('/documents-library');
        $response->assertOk()->assertViewIs('components.documents-library');
    }

    public function test_unauthenticated_document_download()
    {
        $response = $this->get('/documents-library/download/' . self::DOCUMENTS[0]);
        $response->assertOk()->assertViewIs('components.auth-failed');
    }

    public function test_authenticated_document_download()
    {
        $user = EcasTestHelper::validateTestUser('admin');

        foreach (self::DOCUMENTS as $document)
        {
            $response = $this->actingAs($user)->get('/documents-library/download/' . $document);
            $response->assertOk()->assertDownload($document);
        }
    }
}
