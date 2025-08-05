<?php

namespace Tests\Browser\Pages;

use Tests\Browser\Components\Loader;
use Laravel\Dusk\Browser;
use Laravel\Dusk\Page;

class Documents extends Page
{
    const WAIT_FOR_SECONDS = 10;
    const DOCUMENTS = [
        '@cybersecurity_policies' => [
            'title' => 'Cybersecurity Policies',
            'file' => 'cybersecurity-policies.pdf',
            'text' => 'This document provides a comprehensive overview of the cybersecurity policies applied to the EU Cybersecurity Index (EU-CSI) platform, both in terms of software solutions and infrastructure in general. The technical details outline the measures taken to safeguard against unauthorized access, data breaches, and other potential cyber threats. Through the implementation of these policies, the focus is to maintain the confidentiality, integrity, and availability of application\'s data and services while minimizing the risk of cyber-attacks.'
        ],
        '@disaster_recovery' => [
            'title' => 'Disaster Recovery',
            'file' => 'disaster-recovery.pdf',
            'text' => 'This document outlines the measures that have been taken and proposes preventive actions to ensure the availability, resilience, and recoverability of the EU Cybersecurity Index (EU-CSI) platform. The document presents a comprehensive disaster recovery plan that will minimize downtime and ensure the integrity and availability of critical data, while maintaining business continuity and meeting compliance and regulatory requirements.'
        ],
        '@end_user_statement' => [
            'title' => 'End User Statement',
            'file' => 'end-user-statement.pdf',
            'text' => 'This document contains the end-user statement for the registering, accessing, and using the EU Cybersecurity Index (EU-CSI) platform, in form of General terms of Agreement and scope of activities on and content of the platform.'
        ],
        '@data_privacy_statement' => [
            'title' => 'Data Privacy Statement',
            'file' => 'data-privacy-statement.pdf',
            'text' => 'This document addresses the legal basis related to managing personal data collected from the registered users of the EU Cybersecurity Index (EU-CSI) platform, as well as the rights of the owners of the data.'
        ],
        '@user_manual_admin' => [
            'title' => 'User Manual - Admin',
            'file' => 'user-manual-admin.pdf',
            'text' => 'This document describes the offered functionalities by the EU Cybersecurity Index (EU-CSI) platform to the ENISA Admin role.'
        ],
        '@user_manual_non_admin' => [
            'title' => 'User Manual - PPoC/PoC/Operator',
            'file' => 'user-manual-PoC.pdf',
            'text' => 'This document describes the offered functionalities by the EU Cybersecurity Index (EU-CSI) platform for the Member State user roles.'
        ],
        '@eu_login_user_manual' => [
            'title' => 'EU Login User Manual',
            'file' => 'eu-login-user-manual.pdf',
            'text' => 'This document describes the steps to create and use EU ID credentials that are required to access the EU Cybersecurity Index (EU-CSI) platform. Additionally, it describes the alternatives to enabling and using Two Factor Authentication (2FA) that is used by the EU-CSI platform.'
        ],
        '@eu_csi_framework_overview_2023' => [
            'title' => '2023 - EU-CSI framework - Overview',
            'file' => 'eu-csi-framework-overview.pdf',
            'text' => 'This document describes the structure and indicators of the EU-CSI 2023 Pilot containing an overview of EU-CSI, the Weighting methodology employed, the list of EU-Wide indicators and changes with respect to the framework presented at the beginning of 2023.'
        ],
        '@eu_csi_framework_overview_2024' => [
            'title' => '2024 - EU-CSI framework - Overview',
            'file' => '2024-EUCSI-Framework-Overview.pdf',
            'text' => 'This document describes the structure and indicators of the EU-CSI 2024 Index containing an overview of EU-CSI, the Weighting methodology employed, and the list of EU-Wide indicators.'
        ],
        '@eu_csi_detailed_list_of_indicators_2023' => [
            'title' => 'EU-CSI 2023 - Detailed list of indicators',
            'file' => 'eu-csi-detailed-list-of-indicators.xlsm',
            'text' => 'This excel file contains a detailed list of the indicators and their relevant descriptions and details, that comprise EU-CSI 2023.'
        ],
        '@eu_csi_detailed_list_of_indicators_2024' => [
            'title' => 'EU-CSI 2024 - Detailed list of indicators',
            'file' => 'eu-csi-detailed-list-of-indicators-2024.xlsx',
            'text' => 'This excel file contains a detailed list of the indicators and their relevant descriptions and details, that comprise EU-CSI 2024.'
        ]
    ];

    protected $user;

    public function __construct($user)
    {
        $this->user = $user;
    }

    /**
     * Get the URL for the page.
     */
    public function url(): string
    {
        return '/documents-library';
    }

    /**
     * Assert the documents page.
     */
    public function assert(Browser $browser): void
    {
        $browser->on(new Loader(self::WAIT_FOR_SECONDS))
                ->waitForLocation($this->url(), self::WAIT_FOR_SECONDS)
                ->assertSeeIn('@documents_title', 'Documents');
        $documents = self::DOCUMENTS;
        if ($this->user->permissions->first()->role_id != 1) {
            unset($documents['@user_manual_admin']);
        }
        foreach ($documents as $section => $document)
        {
            $browser->within($section, function ($data) use ($section, $document) {
                $data->assertSeeIn('.card-title', $document['title'])
                     ->assertScript("$(\"[dusk='" . substr($section, 1) . "']\").find('.card-title').attr('href');", '/documents-library/download/' . $document['file'])
                     ->assertSeeIn('.card-text', $document['text']);
            });
        }
    }
}

