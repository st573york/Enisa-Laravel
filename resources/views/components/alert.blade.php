<div class="row mt-2 d-none" role="alert">
    @php
        $alert = '<div dusk="alert" class="alert alert-dismissible ' . $type . '" role="alert">' .
                    '<svg class="bi flex-shrink-0 me-2 svg-alert d-none" width="24" height="24" role="img"><use xlink:href=""/></svg>' .
                    '<span></span>' .
                    '<button type="button" class="btn-close close-alert" aria-label="Close"></button>' .
                 '</div>';
    @endphp
    @if ($type == 'pageModalAlert')
        <div class="alert-wrapper col-12">
            {!! $alert !!}
        </div>
    @elseif ($type == 'pageAlert')
        <div class="alert-wrapper col-10 offset-1 {{ $padding ?? '' }}">
            <div class="row">
                {!! $alert !!}
            </div>
        </div>
    @endif
</div>