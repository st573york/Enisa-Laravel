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
                                <li class="breadcrumb-item active"><a href="#">Contacts</a></li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-10 offset-1 ps-0">
                    <h1>Contacts</h1>
                </div>
            </div>

            <div class="row">
                <div class="col-10 offset-1 ps-0">
                    <p>
                        You can contact ENISA and the team in the following ways.
                    </p>
                    <p>
                        <b>EU Cybersecurity Index team:</b>
                        <br>
                        Email: Security Index <a href="mailto:security-index@enisa.europa.eu">security-index@enisa.europa.eu</a>
                    </p>
                    <p>
                        <b>ENISA general contact information:</b>
                        <br>
                        Email: <a href="mailto:info@enisa.europa.eu">info@enisa.europa.eu</a>
                        <br>
                        Web: <a href="https://www.enisa.europa.eu/about-enisa/contact/contact" rel="noopener" target="_blank">https://www.enisa.europa.eu/about-enisa/contact/contact</a>                      
                    </p>
                </div>
            </div>

        </div>
        
    </main>
@endsection
