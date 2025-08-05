@extends('layouts.app')

@section('title', 'Index')

@section('content')
    <main id="enisa-main" class="bg-white">
        <div class="container-fluid">

            <div class="row ps-0">
                <div class="col-10 offset-1 ps-0">
                    <div class="enisa-breadcrump d-flex">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb text-end">
                                <li class="breadcrumb-item"><a href="/">Home</a></li>
                                <li class="breadcrumb-item active"><a href="#">About EU-CSI</a></li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-10 offset-1 ps-0">
                    <h1>About EU-CSI</h1>
                </div>
            </div>

            <div class="row">
                <div class="col-10 offset-1 ps-0">
                    <p>
                        To support the EU in making informed decisions on identified challenges and gaps in cybersecurity,
                        insights on the cybersecurity maturity and posture of the Union and Member State’s policies,
                        capabilities and operations are required. The objective of the EU-CSI is to provide this insight by:
                    </p>
                    <ul>
                        <li>
                            assessing the current level of maturity of cybersecurity and relevant cyber capabilities,
                        </li>
                        <li>
                            identifying opportunities for collaborative and local cybersecurity enhancements,
                        </li>
                        <li>
                            identifying areas of network and information system security weaknesses which may provide a risk to the
                            Union and its MS as well as its citizens, governmental structures, CI/CII and digital services, and small,
                            medium, and large enterprises.
                        </li>
                    </ul>
                </div>
            </div>

            <div class="row">
                <div class="col-10 offset-1 ps-0">
                    <p>The EU-CSI is a Composite Index, and its structure is depicted in the following figure:</p>
                    <img src="/images/eu-csi-index.png" alt="EU-CSI Index">
                </div>
            </div>

            <div class="row">
                <div class="col-10 offset-1 ps-0 mt-3">
                    <p>
                        The EU-CSI 2024 Index is composed by <b>60 indicators</b>, structured hierarchically across <b>15 sub-areas</b> and <b>4 areas</b>.
                    </p>
                    <p>
                        For more information on EU-CSI, and especially the statistical framework details, please consult the document "<a href="/documents-library/download/2024-EUCSI-Framework-Overview.pdf">2024 - EU-CSI Framework - Overview</a>" found in the <a href="/documents-library">Documents library</a>.
                    </p>
                    <p>
                        A detailed list of the indicators that comprise the 2024 EUCS Index can be downloaded from the <a href="/documents-library/download/eu-csi-detailed-list-of-indicators-2024.xlsx">excel file</a> in the <a href="/documents-library">Documents library</a>.
                    </p>
                </div>
            </div>

        </div>
        
    </main>
@endsection
