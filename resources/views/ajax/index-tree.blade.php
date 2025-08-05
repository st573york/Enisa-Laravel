<div class="row">
    <div id="configure-index-tree" class="col-12 mt-2">
        <h2 class="index-name index-edit current">{!! $index->name !!}</h2>
        @if (!empty($tree))
            <ul>
                @foreach ($tree as $area)
                    <li class="level-one-children">
                        <span class="area-edit" data-id={{ $area->idx }}>{!! $area->text !!}</span>
                        <span class="tree-arrow"><i class="fa-solid fa-angle-up"></i></span>
                        <ul class="collapsed">
                            @isset($area->children)
                                @foreach ($area->children as $subarea)
                                    <li class="level-two-children">
                                        <span class="subarea-edit tree-node" parent-id={{ $area->idx }}
                                            data-id={{ $subarea->idx }}>{!! $subarea->text !!}
                                        </span>
                                        <span class="tree-arrow"><i class="fa-solid fa-angle-up"></i></span>
                                        <ul class="collapsed">
                                            @isset($subarea->children)
                                                @foreach ($subarea->children as $indicator)
                                                    <li>
                                                        <span class="indicator-edit"
                                                            data-id="{{ $indicator->idx }}">{!! $indicator->text !!}
                                                        </span>
                                                    </li>
                                                @endforeach
                                            @endisset
                                        </ul>
                                    </li>
                                @endforeach
                            @endisset
                        </ul>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
