@if($wish->image_url && filter_var($wish->image_url, FILTER_VALIDATE_URL))
    @php
        $headers = get_headers($wish->image_url);
        $image_exists = stripos($headers[0], "200 OK");
    @endphp

    @if($image_exists)
        <img alt="ecommerce" class="{{ $class ?? '' }}"
             src="{{ $wish->image_url }}">
    @else
        <x-image-not-found :isShowText="$isShowText"/>
    @endif
@else
    <x-image-not-found :isShowText="$isShowText"/>
@endif
