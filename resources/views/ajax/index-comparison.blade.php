<ul>
    <li>
        <div class="global-values">
            <span>{{ $data['configuration']['text'] }}</span>
            <div class="values hidden">
                @foreach ($data['chartData'][0]['global_index_values'] as $value)
                    <div class="{{ key($value) }} plotly-color hidden">
                        <div class="range-wrap">
                            <div class="key-value">{{ key($value) }}</div>
                            <input type="range" class="range tree-range" value="{{ $value[key($value)] }}" disabled>
                            <output class="tree-value">{{ $value[key($value)] }}&percnt;</output>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        <ul>

            @foreach ($data['chartData'] as $area)
                @if ($loop->index > 0)
                    <li class="area-{{ $loop->index }}">
                        @isset($area['area']['subareas'])
                            <span class="tree-arrow level-one"><i class="fa-solid fa-angle-up"></i></span>
                        @endisset
                        <span>{{ $area['area']['name'] }}</span>

                        <div class="values">
                            @foreach ($area['area']['values'] as $value)
                                <div class="{{ key($value) }} plotly-color hidden">
                                    <div class="range-wrap">
                                        <div class="key-value">{{ key($value) }}</div>
                                        <input type="range" class="range tree-range" value="{{ $value[key($value)] == '#NUM!' ? 0 : $value[key($value)] }}"
                                            disabled>
                                        <output class="tree-value">{{  $value[key($value)] == '#NUM!' ? 'N/A' : $value[key($value)]. '%'}}</output>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        @isset($area['area']['subareas'])
                            <ul class="collapsed">
                                @foreach ($area['area']['subareas'] as $subarea)
                                    <li class="subarea-{{ $loop->index + 1 }}">
                                        @isset($subarea['indicators'])
                                            <span class="tree-arrow level-two"><i class="fa-solid fa-angle-up"></i></span>
                                        @endisset
                                        <span>{{ $subarea['name'] }}</span>
                                        <div class="values">
                                            @foreach ($subarea['values'] as $value)
                                                <div class="{{ key($value) }} plotly-color hidden">
                                                    <div class="range-wrap">
                                                        <div class="key-value">{{ key($value) }}</div>
                                                        <input type="range" class="range tree-range"
                                                            value="{{ $value[key($value)] == '#NUM!' ? 0 : $value[key($value)] }}"
                                                            disabled>
                                                        <output
                                                            class="tree-value">{{  $value[key($value)] == '#NUM!' ? 'N/A' : $value[key($value)]. '%'}}</output>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                        @isset($subarea['indicators'])
                                            <ul class="collapsed">
                                                @foreach ($subarea['indicators'] as $indicator)
                                                    <li class="indicator-{{ $loop->index + 1 }}">
                                                        <span>{{ $indicator['name'] }}</span>
                                                        <div class="values">
                                                            @if (isset($indicator['values']))
                                                                @foreach ($indicator['values'] as $value)
                                                                    <div class="{{ key($value) }} plotly-color hidden">
                                                                        <div class="range-wrap">
                                                                            <div class="key-value">{{ key($value) }}</div>
                                                                            <input type="range" class="range tree-range"
                                                                                value="{{ $value[key($value)] == '#NUM!' ? 0 : $value[key($value)] }}" disabled>
                                                                            <output
                                                                                class="tree-value">{{  $value[key($value)] == '#NUM!' ? 'N/A' : $value[key($value)]. '%'}}</output>
                                                                        </div>
                                                                    </div>
                                                                @endforeach
                                                            @endif
                                                        </div>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @endisset
                                    </li>
                                @endforeach
                            </ul>
                        @endisset
                    </li>
                @endif
            @endforeach
        </ul>
    </li>
</ul>
