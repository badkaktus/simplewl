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
            {{-- The browser checks availability, the server must not request user-provided URLs --}}
            <img alt="{{ $wish->title }}" class="{{ $class ?? '' }}"
                 src="{{ $wish->image_url }}"
                 referrerpolicy="no-referrer"
                 onerror="this.style.display = 'none'; this.nextElementSibling.style.display = 'contents';">
            <div style="display: none">
                <x-image-not-found :isShowText="$isShowText"/>
            </div>
        @else
            <x-image-not-found :isShowText="$isShowText"/>
        @endif
    @endif
@endproduction
