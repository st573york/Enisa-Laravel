@extends('layouts.app')

@section('title', 'Documents')

@section('content')

<main id="enisa-main" class="bg-white">
    <div class="container-fluid">

        <div class="row ps-0">
            <div class="col-10 offset-1 ps-0">
                <div class="enisa-breadcrump d-flex">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb text-end">
                            <li class="breadcrumb-item"><a href="/">Home</a></li>
                            <li class="breadcrumb-item active"><a href="#">Documents</a></li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-10 offset-1 ps-0">
                <h1 dusk="documents_title">Documents</h1>
            </div>
        </div>

        @if (Auth::user()->isAdmin())
        <div class="row mt-4">
            <div class="col-10 offset-1 ps-0">
                <div class="card">
                    <div dusk="user_manual_admin" class="card-body">
                        <h5><a href="/documents-library/download/user-manual-admin.pdf" class="card-title">User Manual - Admin</a></h5>
                        <p class="card-text">This document describes the offered functionalities by the EU Cybersecurity Index (EU-CSI) platform to the ENISA Admin role.</p>
                        <h6 class="text-secondary">Posted on March 10, 2025</h6>
                    </div>
                </div>
            </div>
        </div>
        @endif
        <div class="row mt-4">
            <div class="col-10 offset-1 ps-0">
                <div class="card">
                    <div dusk="user_manual_non_admin" class="card-body">
                        <h5><a href="/documents-library/download/user-manual-PoC.pdf" class="card-title">User Manual - PPoC/PoC/Operator</a></h5>
                        <p class="card-text">This document describes the offered functionalities by the EU Cybersecurity Index (EU-CSI) platform for the Member State user roles.</p>
                        <h6 class="text-secondary">Posted on March 10, 2025</h6>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-10 offset-1 ps-0">
                <div class="card">
                    <div dusk="cybersecurity_policies" class="card-body">
                        <h5><a href="/documents-library/download/cybersecurity-policies.pdf" class="card-title">Cybersecurity Policies</a></h5>
                        <p class="card-text">This document provides a comprehensive overview of the cybersecurity policies applied to the EU Cybersecurity Index (EU-CSI) platform, both in terms of software solutions and infrastructure in general. The technical details outline the measures taken to safeguard against unauthorized access, data breaches, and other potential cyber threats. Through the implementation of these policies, the focus is to maintain the confidentiality, integrity, and availability of application's data and services while minimizing the risk of cyber-attacks.</p>
                        <h6 class="text-secondary">Posted on October 11, 2024</h6>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mt-4">
            <div class="col-10 offset-1 ps-0">
                <div class="card">
                    <div dusk="disaster_recovery" class="card-body">
                        <h5><a href="/documents-library/download/disaster-recovery.pdf" class="card-title">Disaster Recovery</a></h5>
                        <p class="card-text">This document outlines the measures that have been taken and proposes preventive actions to ensure the availability, resilience, and recoverability of the EU Cybersecurity Index (EU-CSI) platform. The document presents a comprehensive disaster recovery plan that will minimize downtime and ensure the integrity and availability of critical data, while maintaining business continuity and meeting compliance and regulatory requirements.</p>
                        <h6 class="text-secondary">Posted on October 11, 2024</h6>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mt-4">
            <div class="col-10 offset-1 ps-0">
                <div class="card">
                    <div dusk="end_user_statement" class="card-body">
                        <h5><a href="/documents-library/download/end-user-statement.pdf" class="card-title">End User Statement</a></h5>
                        <p class="card-text">This document contains the end-user statement for the registering, accessing, and using the EU Cybersecurity Index (EU-CSI) platform, in form of General terms of Agreement and scope of activities on and content of the platform.</p>
                        <h6 class="text-secondary">Posted on October 11, 2024</h6>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mt-4">
            <div class="col-10 offset-1 ps-0">
                <div class="card">
                    <div dusk="data_privacy_statement" class="card-body">
                        <h5><a href="/documents-library/download/data-privacy-statement.pdf" class="card-title">Data Privacy Statement</a></h5>
                        <p class="card-text">This document addresses the legal basis related to managing personal data collected from the registered users of the EU Cybersecurity Index (EU-CSI) platform, as well as the rights of the owners of the data.</p>
                        <h6 class="text-secondary">Posted on October 11, 2024</h6>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-10 offset-1 ps-0">
                <div class="card">
                    <div dusk="eu_csi_framework_overview_2024" class="card-body">
                        <h5><a href="/documents-library/download/2024-EUCSI-Framework-Overview.pdf" class="card-title">EU-CSI 2024 - Framework - Overview</a></h5>
                        <p class="card-text">This document describes the structure and indicators of the EU-CSI 2024 Index containing an overview of EU-CSI, the Weighting methodology employed, and the list of EU-Wide indicators.</p>
                        <h6 class="text-secondary">Posted on September 24, 2024</h6>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mt-4">
            <div class="col-10 offset-1 ps-0">
                <div class="card">
                    <div dusk="eu_csi_detailed_list_of_indicators_2024" class="card-body">
                        <h5><a href="/documents-library/download/eu-csi-detailed-list-of-indicators-2024.xlsx" class="card-title">EU-CSI 2024 - Detailed list of indicators</a></h5>
                        <p class="card-text">This excel file contains a detailed list of the indicators and their relevant descriptions and details, that comprise EU-CSI 2024.</p>
                        <h6 class="text-secondary">Posted on September 24, 2024</h6>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mt-4">
            <div class="col-10 offset-1 ps-0">
                <div class="card">
                    <div dusk="eu_csi_framework_overview_2023" class="card-body">
                        <h5><a href="/documents-library/download/eu-csi-framework-overview.pdf" class="card-title">EU-CSI 2023 - Framework Overview</a></h5>
                        <p class="card-text">This document describes the structure and indicators of the EU-CSI 2023 Pilot containing an overview of EU-CSI, the Weighting methodology employed, the list of EU-Wide indicators and changes with respect to the framework presented at the beginning of 2023.</p>
                        <h6 class="text-secondary">Posted on December 18, 2023</h6>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mt-4">
            <div class="col-10 offset-1 ps-0">
                <div class="card">
                    <div dusk="eu_csi_detailed_list_of_indicators_2023" class="card-body">
                        <h5><a href="/documents-library/download/eu-csi-detailed-list-of-indicators.xlsm" class="card-title">EU-CSI 2023 - Detailed list of indicators</a></h5>
                        <p class="card-text">This excel file contains a detailed list of the indicators and their relevant descriptions and details, that comprise EU-CSI 2023.</p>
                        <h6 class="text-secondary">Posted on December 18, 2023</h6>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mt-4">
            <div class="col-10 offset-1 ps-0">
                <div class="card">
                    <div dusk="eu_login_user_manual" class="card-body">
                        <h5><a href="/documents-library/download/eu-login-user-manual.pdf" class="card-title">EU Login User Manual</a></h5>
                        <p class="card-text">This document describes the steps to create and use EU ID credentials that are required to access the EU Cybersecurity Index (EU-CSI) platform. Additionally, it describes the alternatives to enabling and using Two Factor Authentication (2FA) that is used by the EU-CSI platform.</p>
                        <h6 class="text-secondary">Posted on August 2, 2023</h6>
                    </div>
                </div>
            </div>
        </div>
    </div>

</main>

@endsection