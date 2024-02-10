@if(isset($user->id) && isset($wishlist->id))
    @php
        $isPrivate = $wishlist->is_private;
    @endphp

    <div x-data="{ isPrivate: {{ $isPrivate }}, isShowBlock: false }">
        <div id="tooltip-wishlist-vision" role="tooltip"
             class="absolute z-10 invisible inline-block px-3 py-2 text-sm font-medium text-white transition-opacity duration-300 bg-gray-900 rounded-lg shadow-sm opacity-0 tooltip dark:bg-gray-700">
            <span
                x-text="isPrivate ? 'Your wishlist is private. Only you can see it.' : 'Your wishlist is public. Everyone can see it.'"></span>
            Click to change.
            <div class="tooltip-arrow" data-popper-arrow></div>
        </div>

        <div
            @click="makeAjaxRequest"
            class="cursor-pointer align-text-bottom hidden"
            :class="{ 'block': isShowBlock, 'hidden': !isShowBlock }"
            data-tooltip-target="tooltip-wishlist-vision"
            x-init="isShowBlock = true"
        >
            <div x-show="!isPrivate">
                <x-wishlist-visible-icon/>
            </div>
            <div x-show="isPrivate">
                <x-wishlist-private-icon/>
            </div>
        </div>

        <script>
            function makeAjaxRequest() {
                axios.post('{{ route('wishlist.changeVisibility', ['name' => $user->name, 'slug' => $wishlist->slug]) }}')
                    .then(response => {
                        if (response.status === 200) {
                            this.isPrivate = response.data.isPrivate;
                        }
                    })
                    .catch(error => {
                        console.log(error);
                    });
            }
        </script>
    </div>
@endif
