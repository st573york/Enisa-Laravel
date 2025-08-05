<div hidden class="{{ !$dataAvailable ? 'no-data' : '' }}"></div>
<div hidden class="{{ (!$configurationEntity->eu_published && !$configurationEntity->ms_published) ? 'no-reports-visualisations' : '' }}"></div>

<div class="row mt-5 {{ (!$dataAvailable || (!$configurationEntity->eu_published && !$configurationEntity->ms_published)) ? 'd-none' : '' }}">
    <div class="col-10 offset-1 ps-0">
        @php
            $title = !Auth::user()->isAdmin() && !Auth::user()->isEnisa() ? '- ' . $userCountry : '';
        @endphp
        <h1 class="indicators-title ps-0 ">{{ __('EU Cybersecurity Index') }} {{ $title }}</h1>
    </div>
</div>

<div class="row {{ (!$dataAvailable || (!$configurationEntity->eu_published && !$configurationEntity->ms_published)) ? 'd-none' : '' }}">
    <div class="col-10 offset-1 col-lg-8 offset-lg-3 pe-0 ps-0">
        <ul id="tab-select" class="nav nav-tabs justify-content-end" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="map-tab" data-bs-toggle="tab" type="button"
                    role="tab" aria-controls="map" aria-selected="true">Map</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="sunburst-tab" data-bs-toggle="tab" type="button" role="tab"
                    aria-controls="sunburst" aria-selected="false">Sunburst</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tree-tab" data-bs-toggle="tab" type="button" role="tab"
                    aria-controls="tree" aria-selected="false">Tree</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="barchart-tab" data-bs-toggle="tab" type="button" role="tab"
                    aria-controls="barchart" aria-selected="false">Barchart</button>
            </li>
        </ul>
    </div>
</div>

<div id="access-index" class="row {{ (!$dataAvailable || (!$configurationEntity->eu_published && !$configurationEntity->ms_published)) ? 'd-none' : '' }}">
    <div class="filters-tab indicators tabbed col-md-3 col-lg-2 offset-lg-1 ps-0 hidden">

        <div class="index-wrapper pe-4 d-flex flex-column">
            {{-- Sunburst form --}}
            <div id="sunburst-form-wrap" class="hidden">
                <form id="sunburst-first-select" action="">
                    <div id="sunburst-first-index" class="row">
                        <div class="h6 sunburst-label hidden">Index</div>
                        <div class="col-md-6 col-lg-11 col-xl-11 pe-0">
                            <select id="sunburst-form-country-0" class="form-select me-2"
                                aria-label="Default select example">
                                <option selected>{{ $euAverageName }} {{ $configuration['year'] }}</option>
                                @if (count($countries['top']) > 0)
                                    <optgroup label="Top {{ count($countries['top']) }} Countries">
                                        @foreach ($countries['top'] as $country)
                                            <option
                                                value="{{ $country->country->name }} {{ $country->configuration->year }}">
                                                {{ $country->country->name }}
                                                {{ $country->configuration->year }}
                                            </option>
                                        @endforeach
                                    </optgroup>

                                    <optgroup label="Default Countries">
                                @endif

                                @foreach ($countries['rest'] as $country)
                                    <option
                                        value="{{ $country->country->name }} {{ $country->configuration->year }}">
                                        {{ $country->country->name }} {{ $country->configuration->year }}
                                    </option>
                                @endforeach
                                @if (count($countries['top']) > 0)
                                    </optgroup>
                                @endif
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            {{-- Sunburst form END --}}

            <div id="all-forms-wrap">
                <form id="first-select" action="">
                    <div id="first-index" class="row">
                        <h6 class="text-muted sunburst-label hidden">Indexes</h6>
                        <div class="col-md-6 col-lg-11 col-xl-11 pe-0">
                            <div class="h6">Select Indexes</div>
                            <select id="form-country-0" class="form-select me-2"
                                aria-label="Default select example" multiple>
                                @if (count($countries['top']) > 0)
                                    <optgroup label="Top {{ count($countries['top']) }} Countries">
                                        @foreach ($countries['top'] as $country)
                                            <option
                                                value="{{ $country->country->name }} {{ $country->configuration->year }}">
                                                {{ $country->country->name }}
                                                {{ $country->configuration->year }}
                                            </option>
                                        @endforeach
                                    </optgroup>

                                    <optgroup label="Default Countries">
                                @endif
                                @foreach ($countries['rest'] as $country)
                                    <option {{ $loop->index == 0 ? 'selected' : '' }}
                                        value="{{ $country->country->name }} {{ $country->configuration->year }}">
                                        {{ $country->country->name }} {{ $country->configuration->year }}
                                    </option>
                                @endforeach
                                @if (count($countries['top']) > 0)
                                    </optgroup>
                                @endif
                            </select>
                        </div>
                    </div>
                </form>
            </div>

            <div id="indicators-tree" class="indicators-wrapper checbox-wrapper mt-5">
                <h6 class="text-muted mt-2">Select Indicators</h6>
                <ul>
                    <li>
                        <input class="checkbox-input form-check-input default" id="{{ $configuration['id'] }}"
                            type="checkbox" checked />
                        <span id="configuration-id">{{ $configuration['text'] }}</span>
                        <ul>
                            @foreach ($configuration['children'] as $area)
                                <li>
                                    <input
                                        class="checkbox-input form-check-input area-checkbox default {{ $area->text == 'Select All' ? 'all' : '' }}"
                                        id="{{ $area->id }}" type="checkbox" checked />
                                    <span>{{ $area->text }}</span>
                                    @isset($area->children)
                                        <span class="tree-arrow"><i class="fa-solid fa-angle-up"></i></span>
                                    @endisset
                                    @isset($area->children)
                                        <ul class="collapsed">
                                            @foreach ($area->children as $subarea)
                                                <li>
                                                    <input
                                                        class="checkbox-input form-check-input subarea-checkbox {{ $subarea->text == 'Select All' ? 'all' : '' }}"
                                                        id="{{ $subarea->id }}" type="checkbox" />
                                                    <span>{{ $subarea->text }}</span>

                                                    @isset($subarea->children)
                                                        <span class="tree-arrow"><i
                                                                class="fa-solid fa-angle-up"></i></span>
                                                        <ul class="collapsed">
                                                            @foreach ($subarea->children as $indicator)
                                                                <li>
                                                                    <input
                                                                        class="checkbox-input form-check-input indicator-checkbox {{ $indicator->text == 'Select All' ? 'all' : '' }}"
                                                                        id="{{ $indicator->id }}" type="checkbox" />
                                                                    <span>{{ $indicator->text }}</span>
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    @endisset
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endisset
                                </li>
                            @endforeach
                        </ul>
                    </li>
                </ul>
            </div>

            <div class="btn-wrapper button-box mt-5">
                <a id="compare" class="btn btn-enisa">View</a>
                <a id="reset" class="btn btn-enisa">Reset</a>
            </div>

            <svg id="tab-svg" xmlns="http://www.w3.org/2000/svg" width="48" height="122"
                viewBox="0 0 48 122" fill="none">
                <g filter="url(#filter0_d_80_4063)">
                    <path d="M4 0H36C40.4183 0 44 3.58172 44 8V106C44 110.418 40.4183 114 36 114H4V0Z"
                        fill="#141414" shape-rendering="crispEdges"></path>
                    <g clip-path="url(#clip0_80_4063)">
                        <path
                            d="M24.3021 28.5706C24.1765 28.6966 24.0124 28.7657 23.8426 28.7657L18.8405 28.7657C18.5394 28.7657 18.3867 29.162 18.5983 29.3966L20.1974 30.9188C20.4215 31.1225 20.5324 31.2349 20.7542 31.2349L23.8415 31.2349C24.0112 31.2349 24.1753 31.3052 24.301 31.4299L28.6456 35.7979C28.9715 36.1251 29.5 35.8732 29.5 35.3892L29.5 24.6113C29.5 24.1274 28.9726 23.8743 28.6456 24.2027L24.3021 28.5706Z"
                            fill="white"></path>
                    </g>
                    <path
                        d="M30.375 47.3594L19 47.3594L19 45.0156L30.375 45.0156L30.375 47.3594ZM25.5156 51.8906L23.6875 51.8906L23.6875 46.7187L25.5156 46.7187L25.5156 51.8906ZM30.375 52.4375L28.5391 52.4375L28.5391 46.7187L30.375 46.7187L30.375 52.4375ZM27.4531 56.0312L19 56.0312L19 53.7734L27.4531 53.7734L27.4531 56.0312ZM29.6562 53.6328C29.9844 53.6328 30.2552 53.7474 30.4687 53.9766C30.6823 54.2057 30.7891 54.513 30.7891 54.8984C30.7891 55.2786 30.6823 55.5833 30.4687 55.8125C30.2552 56.0469 29.9844 56.1641 29.6562 56.1641C29.3281 56.1641 29.0573 56.0469 28.8437 55.8125C28.6302 55.5833 28.5234 55.2786 28.5234 54.8984C28.5234 54.513 28.6302 54.2057 28.8437 53.9766C29.0573 53.7474 29.3281 53.6328 29.6562 53.6328ZM31 60.2656L19 60.2656L19 58.0078L31 58.0078L31 60.2656ZM27.4531 66.2656L25.8594 66.2656L25.8594 61.3437L27.4531 61.3437L27.4531 66.2656ZM29.5391 62.5625L29.5391 64.8125L21.5469 64.8125C21.3021 64.8125 21.1146 64.8437 20.9844 64.9062C20.8542 64.974 20.763 65.0729 20.7109 65.2031C20.6641 65.3333 20.6406 65.4974 20.6406 65.6953C20.6406 65.8359 20.6458 65.9609 20.6562 66.0703C20.6719 66.1849 20.6875 66.2812 20.7031 66.3594L19.0469 66.3672C18.9844 66.1745 18.9349 65.9661 18.8984 65.7422C18.862 65.5182 18.8437 65.2708 18.8437 65C18.8437 64.5052 18.9245 64.0729 19.0859 63.7031C19.2526 63.3385 19.5182 63.0573 19.8828 62.8594C20.2474 62.6615 20.7266 62.5625 21.3203 62.5625L29.5391 62.5625ZM18.8437 71.4375C18.8437 70.7812 18.9479 70.1927 19.1562 69.6719C19.3698 69.151 19.6641 68.7083 20.0391 68.3437C20.4141 67.9844 20.849 67.7083 21.3437 67.5156C21.8437 67.3229 22.375 67.2266 22.9375 67.2266L23.25 67.2266C23.8906 67.2266 24.4766 67.3177 25.0078 67.5C25.5391 67.6823 26 67.9427 26.3906 68.2812C26.7812 68.625 27.0807 69.0417 27.2891 69.5312C27.5026 70.0208 27.6094 70.5729 27.6094 71.1875C27.6094 71.7865 27.5104 72.3177 27.3125 72.7812C27.1146 73.2448 26.8333 73.6328 26.4687 73.9453C26.1042 74.263 25.6667 74.5026 25.1562 74.6641C24.651 74.8255 24.0885 74.9062 23.4687 74.9062L22.5312 74.9062L22.5312 68.1875L24.0312 68.1875L24.0312 72.6953L24.2031 72.6953C24.5156 72.6953 24.7943 72.638 25.0391 72.5234C25.2891 72.4141 25.487 72.2474 25.6328 72.0234C25.7786 71.7995 25.8516 71.513 25.8516 71.1641C25.8516 70.8672 25.7865 70.612 25.6562 70.3984C25.526 70.1849 25.3437 70.0104 25.1094 69.875C24.875 69.7448 24.599 69.6458 24.2812 69.5781C23.9687 69.5156 23.625 69.4844 23.25 69.4844L22.9375 69.4844C22.599 69.4844 22.2865 69.5312 22 69.625C21.7135 69.724 21.4661 69.862 21.2578 70.0391C21.0495 70.2214 20.888 70.4401 20.7734 70.6953C20.6589 70.9557 20.6016 71.25 20.6016 71.5781C20.6016 71.9844 20.6797 72.362 20.8359 72.7109C20.9974 73.0651 21.2396 73.3698 21.5625 73.625L20.375 74.7187C20.1198 74.5417 19.875 74.2995 19.6406 73.9922C19.4062 73.6901 19.2135 73.3255 19.0625 72.8984C18.9167 72.4714 18.8437 71.9844 18.8437 71.4375ZM25.6094 78.4375L19 78.4375L19 76.1875L27.4531 76.1875L27.4531 78.3047L25.6094 78.4375ZM27.5078 80.9844L25.4219 80.9453C25.4375 80.8359 25.4505 80.7031 25.4609 80.5469C25.4766 80.3958 25.4844 80.2578 25.4844 80.1328C25.4844 79.8151 25.4427 79.5391 25.3594 79.3047C25.2812 79.0755 25.1641 78.8828 25.0078 78.7266C24.8516 78.5755 24.6615 78.4609 24.4375 78.3828C24.2135 78.3099 23.9583 78.2682 23.6719 78.2578L23.8125 77.8047C24.3594 77.8047 24.862 77.8594 25.3203 77.9687C25.7839 78.0781 26.1875 78.237 26.5312 78.4453C26.875 78.6589 27.1406 78.9193 27.3281 79.2266C27.5156 79.5339 27.6094 79.8854 27.6094 80.2812C27.6094 80.4062 27.599 80.5339 27.5781 80.6641C27.5625 80.7943 27.5391 80.901 27.5078 80.9844ZM21.3359 86.6172C21.4974 86.6172 21.6432 86.5703 21.7734 86.4766C21.9036 86.3828 22.0234 86.2083 22.1328 85.9531C22.2474 85.7031 22.3516 85.3411 22.4453 84.8672C22.5391 84.4401 22.6562 84.0417 22.7969 83.6719C22.9427 83.3073 23.1172 82.9896 23.3203 82.7187C23.5234 82.4531 23.763 82.2448 24.0391 82.0937C24.3203 81.9427 24.6406 81.8672 25 81.8672C25.3542 81.8672 25.6875 81.9427 26 82.0937C26.3125 82.25 26.5885 82.4714 26.8281 82.7578C27.0729 83.0495 27.263 83.4036 27.3984 83.8203C27.5391 84.2422 27.6094 84.7161 27.6094 85.2422C27.6094 85.9766 27.4922 86.6068 27.2578 87.1328C27.0234 87.6641 26.7005 88.0703 26.2891 88.3516C25.8828 88.638 25.4193 88.7812 24.8984 88.7812L24.8984 86.5312C25.1172 86.5312 25.3125 86.4844 25.4844 86.3906C25.6615 86.3021 25.7995 86.1615 25.8984 85.9687C26.0026 85.7812 26.0547 85.5365 26.0547 85.2344C26.0547 84.9844 26.0104 84.7682 25.9219 84.5859C25.8385 84.4036 25.724 84.263 25.5781 84.1641C25.4375 84.0703 25.2812 84.0234 25.1094 84.0234C24.9792 84.0234 24.862 84.0495 24.7578 84.1016C24.6589 84.1589 24.5677 84.25 24.4844 84.375C24.401 84.5 24.3229 84.6615 24.25 84.8594C24.1823 85.0625 24.1198 85.3125 24.0625 85.6094C23.9375 86.2187 23.7734 86.763 23.5703 87.2422C23.3724 87.7214 23.1016 88.1016 22.7578 88.3828C22.4193 88.6641 21.974 88.8047 21.4219 88.8047C21.0469 88.8047 20.7031 88.7214 20.3906 88.5547C20.0781 88.388 19.8047 88.1484 19.5703 87.8359C19.3411 87.5234 19.1615 87.1484 19.0312 86.7109C18.9062 86.2786 18.8437 85.7917 18.8437 85.25C18.8437 84.4635 18.9844 83.7969 19.2656 83.25C19.5469 82.7083 19.9036 82.2969 20.3359 82.0156C20.7734 81.7396 21.2214 81.6016 21.6797 81.6016L21.6797 83.7344C21.3724 83.7448 21.125 83.8229 20.9375 83.9687C20.75 84.1198 20.6146 84.3099 20.5312 84.5391C20.4479 84.7734 20.4062 85.026 20.4062 85.2969C20.4062 85.5885 20.4453 85.8307 20.5234 86.0234C20.6068 86.2161 20.7161 86.362 20.8516 86.4609C20.9922 86.5651 21.1536 86.6172 21.3359 86.6172Z"
                        fill="white"></path>
                </g>
                <defs>
                    <filter id="filter0_d_80_4063" x="0" y="0" width="48"
                        height="122" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">
                        <feFlood flood-opacity="0" result="BackgroundImageFix"></feFlood>
                        <feColorMatrix in="SourceAlpha" type="matrix"
                            values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" result="hardAlpha">
                        </feColorMatrix>
                        <feOffset dy="4"></feOffset>
                        <feGaussianBlur stdDeviation="2"></feGaussianBlur>
                        <feComposite in2="hardAlpha" operator="out"></feComposite>
                        <feColorMatrix type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0.25 0">
                        </feColorMatrix>
                        <feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow_80_4063">
                        </feBlend>
                        <feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow_80_4063"
                            result="shape"></feBlend>
                    </filter>
                    <clipPath id="clip0_80_4063">
                        <rect width="12" height="11" fill="white"
                            transform="translate(29.5 24) rotate(90)">
                        </rect>
                    </clipPath>
                </defs>
            </svg>

        </div>

    </div>

    <div class="col-md-9 col-lg-8 ps-3 col-expand width-expand w-100">

        <div class="row">
            <div class="col-11 col-lg-10"></div>
        </div>

        <div id="charts-row" class="row">
            <div class="col-10 offset-1 ps-0 pe-0">
                <div class="col-12 js-plotly-plot">
                    <div id="map-wrapper" class="width-full row">
                        <div class="mt-5 mb-5">
                            <div class="col-12 ps-0 pe-0">
                                <select id="areas-subareas" class="form-select me-2"
                                    aria-label="Select areas and subareas" style="width: 100%">
                                    <option class="option-group" value="Index">
                                        {{ $configurationEntity->name }}
                                    </option>
                                    @isset ($configurationEntity->json_data['contents'])
                                        @foreach ($configurationEntity->json_data['contents'] as $area)
                                            <option class="option-group" value="area-{{ $area['area']['id'] }}">
                                                {{ $area['area']['name'] }}
                                            </option>
                                            @foreach ($area['area']['subareas'] as $subarea)
                                                <option class="select-child" value="subarea-{{ $subarea['id'] }}">
                                                    {{ $subarea['name'] }}
                                                </option>
                                            @endforeach
                                        @endforeach
                                    @endisset
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12 col-md-5">
                                <div class="table-section transform-none">
                                    <div class="col-12 d-flex flex-column justify-content-center">
                                        <table id="baseline-table"
                                            class="table table-hover bg-white with-border-right">
                                            @if (Auth::user()->isAdmin() || (Auth::user()->isViewer() && Auth::user()->isEnisa()))
                                                <div id="baseline-table-title" class="h6 ps-2">
                                                    {{ __('EU Average') }}</div>
                                            @endif
                                            <thead>
                                                <tr>
                                                    <th>{{ __('Title') }}</th>
                                                    <th>{{ __('EU') }}</th>
                                                    @if (!Auth::user()->isAdmin() && !Auth::user()->isEnisa() && $configurationEntity->ms_published)
                                                        <th>{{ $userCountry }}</th>
                                                    @endif
                                                </tr>
                                            </thead>
                                            <tbody>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-md-7 pe-0">
                                <div id="map" style="height:645px"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="sunburst" class="col-sm-12 hidden mt-5" style="height:900px; width: 1000px;"></div>
                <div id="downloadTreeImage" class="col-sm-12 pe-2 mt-2 d-flex justify-content-end hidden">
                    <div id="treeTooltipWrapper" class="d-flex flex-column align-items-end">
                        <i class="icon-chart-download" onclick="saveCapture();"></i>
                        <p id="treeTooltip" class="hidden">{{ __('Save as Image') }}</p>
                    </div>
                </div>
                <div id="sliderChart" class="hidden"></div>
                <div id="chart" style="height:700px;width:100%" class="js-plotly-plot hidden"></div>
            </div>
            <div class="col-sm-12 offset-1 col-md-12  mt-5 label-section hidden">
                <p>The sunburst is a visual hierarchical representation of the Index ( The inner circle is the
                    Aggregated Index, the 1st donut corresponds the areas, the second donut to the subareas and
                    the last to the indicators).<br />
                    The color of each area, subarea or indicator corresponds to its value ( from 0 to 100)
                    whereas the size corresponds to its weight. You can "zoom" in a specific area or subarea of
                    the Index by clicking on it.</p>
            </div>
        </div>

    </div>

</div>

<script src="{{ mix('mix/js/comparison.js') }}" defer></script>
<script src="{{ mix('mix/js/sunburst.js') }}" defer></script>
<script src="{{ asset('js/html2canvas.min.js') }}"></script>
