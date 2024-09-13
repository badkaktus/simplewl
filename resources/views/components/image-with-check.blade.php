@env('testing')
    <img alt="{{ $wish->title }}" class="{{ $class ?? '' }}"
         src="{{ $wish->image_url }}">
@endenv

@production
    @if($wish->local_file_name)
        <img alt="{{ $wish->title }}" class="{{ $class ?? '' }}"
             src="{{ asset('/storage/' . $wish->local_file_name) }}">
    @else
        @if($wish->image_url && filter_var($wish->image_url, FILTER_VALIDATE_URL))
            @php
                $headers = get_headers($wish->image_url);
                $image_exists = stripos($headers[0], "200 OK");
            @endphp

            @if($image_exists)
                <img alt="{{ $wish->title }}" class="{{ $class ?? '' }}"
                     src="{{ $wish->image_url }}">
            @else
                <x-image-not-found :isShowText="$isShowText"/>
            @endif
        @else
            <x-image-not-found :isShowText="$isShowText"/>
        @endif
    @endif
@endproduction
