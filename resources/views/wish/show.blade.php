<x-app-layout>
    <section class="text-gray-600 body-font overflow-hidden">
        <div class="container px-5 py-8 mx-auto">
            <div class="lg:w-4/5 mx-auto flex flex-wrap justify-center content-center">
                <x-image-with-check
                    class="lg:w-1/2 w-full lg:h-auto h-64 object-cover object-center rounded"
                    :wish="$wish"/>

                <div class="lg:w-1/2 w-full lg:pl-10 lg:py-6 mt-6 lg:mt-0">
                    <div class="flex justify-between">
                        <div>
                            <h1 class="text-gray-900 text-3xl title-font font-medium mb-1">{{ $wish->title }}</h1>
                            <div class="flex mb-4">
                            <span class="text-sm text-gray-400">
                                Added at {{ $wish->created_at->format('d.m.Y') }}
                            </span>
                                @if($wish->is_completed)
                                    <span
                                        class="bg-green-400 text-green-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded-full ml-2">
                                        Completed
                                    </span>
                                @endif
                            </div>
                        </div>
                        @if(Auth::check() && Auth::user()->id === $wish->wishlist->user_id)
                            <div class="flex justify-end gap-x-1">

                                @if(!$wish->is_completed)
                                    <div class="font-medium" x-data="{ showButton: true }">
                                            <button
                                                    x-show="showButton"
                                                    @click="toggleCompleteState"
                                                    class="rounded-full w-10 h-10 hover:bg-gray-200 bg-green-300 p-0 border-0 inline-flex items-center justify-center hover:text-gray-500 ml-4">
                                                <svg xmlns="http://www.w3.org/2000/svg" height="20" width="17.5"
                                                     viewBox="0 0 448 512">
                                                    <!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2023 Fonticons, Inc.-->
                                                    <path
                                                        d="M438.6 105.4c12.5 12.5 12.5 32.8 0 45.3l-256 256c-12.5 12.5-32.8 12.5-45.3 0l-128-128c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0L160 338.7 393.4 105.4c12.5-12.5 32.8-12.5 45.3 0z"/>
                                                </svg>
                                            </button>
                                        <script>
                                            function toggleCompleteState() {
                                                axios.post('{{ route('wish.complete',  $wish->slug) }}')
                                                    .then(response => {
                                                        if (response.data.isSuccess) {
                                                            this.showButton = false;
                                                        }
                                                    })
                                                    .catch(error => {
                                                        console.log(error);
                                                    });
                                            }
                                        </script>
                                    </div>
                                @endif
                                <div>
                                    <a href="{{ route('wish.edit', $wish->slug) }}"
                                       class="rounded-full w-10 h-10 hover:bg-gray-300 bg-gray-200 p-0 border-0 inline-flex items-center justify-center hover:text-gray-500 ml-4">
                                        <svg xmlns="http://www.w3.org/2000/svg" height="20" width="20"
                                             viewBox="0 0 512 512">
                                            <!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2023 Fonticons, Inc.-->
                                            <path fill="#1e3050"
                                                  d="M441 58.9L453.1 71c9.4 9.4 9.4 24.6 0 33.9L424 134.1 377.9 88 407 58.9c9.4-9.4 24.6-9.4 33.9 0zM209.8 256.2L344 121.9 390.1 168 255.8 302.2c-2.9 2.9-6.5 5-10.4 6.1l-58.5 16.7 16.7-58.5c1.1-3.9 3.2-7.5 6.1-10.4zM373.1 25L175.8 222.2c-8.7 8.7-15 19.4-18.3 31.1l-28.6 100c-2.4 8.4-.1 17.4 6.1 23.6s15.2 8.5 23.6 6.1l100-28.6c11.8-3.4 22.5-9.7 31.1-18.3L487 138.9c28.1-28.1 28.1-73.7 0-101.8L474.9 25C446.8-3.1 401.2-3.1 373.1 25zM88 64C39.4 64 0 103.4 0 152V424c0 48.6 39.4 88 88 88H360c48.6 0 88-39.4 88-88V312c0-13.3-10.7-24-24-24s-24 10.7-24 24V424c0 22.1-17.9 40-40 40H88c-22.1 0-40-17.9-40-40V152c0-22.1 17.9-40 40-40H200c13.3 0 24-10.7 24-24s-10.7-24-24-24H88z"/>
                                        </svg>
                                    </a>
                                </div>
                                <div>
                                    <form method="POST" action="{{ route('wish.destroy',  $wish->slug) }}">
                                        @method('DELETE')
                                        @csrf
                                        <button type="submit"
                                                class="rounded-full w-10 h-10 hover:bg-gray-200 bg-red-400 p-0 border-0 inline-flex items-center justify-center hover:text-gray-500 ml-4">
                                            <svg xmlns="http://www.w3.org/2000/svg" height="20" width="17.5"
                                                 viewBox="0 0 448 512">
                                                <!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2023 Fonticons, Inc.-->
                                                <path fill="#8f1f1e"
                                                      d="M135.2 17.7L128 32H32C14.3 32 0 46.3 0 64S14.3 96 32 96H416c17.7 0 32-14.3 32-32s-14.3-32-32-32H320l-7.2-14.3C307.4 6.8 296.3 0 284.2 0H163.8c-12.1 0-23.2 6.8-28.6 17.7zM416 128H32L53.2 467c1.6 25.3 22.6 45 47.9 45H346.9c25.3 0 46.3-19.7 47.9-45L416 128z"/>
                                            </svg>
                                        </button>
                                    </form>

                                </div>
                            </div>
                        @endif
                    </div>

                    <p class="leading-relaxed">{{ $wish->description }}</p>
                    <div class="flex justify-between mt-1 items-center pb-5 border-b-2 border-gray-100 mb-5">
                    </div>
                    <div class="flex justify-between">
                        <div>
                            <span class="title-font font-medium text-2xl text-gray-900">
                                {{ $wish->amount }} {{ $wish->currency }}
                            </span>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            @if($wish->url)
                                <a
                                    href="{{ $wish->url }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    data-tooltip-target="tooltip-open-link"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                                        <!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.-->
                                        <path
                                            d="M352 256c0 22.2-1.2 43.6-3.3 64H163.3c-2.2-20.4-3.3-41.8-3.3-64s1.2-43.6 3.3-64H348.7c2.2 20.4 3.3 41.8 3.3 64zm28.8-64H503.9c5.3 20.5 8.1 41.9 8.1 64s-2.8 43.5-8.1 64H380.8c2.1-20.6 3.2-42 3.2-64s-1.1-43.4-3.2-64zm112.6-32H376.7c-10-63.9-29.8-117.4-55.3-151.6c78.3 20.7 142 77.5 171.9 151.6zm-149.1 0H167.7c6.1-36.4 15.5-68.6 27-94.7c10.5-23.6 22.2-40.7 33.5-51.5C239.4 3.2 248.7 0 256 0s16.6 3.2 27.8 13.8c11.3 10.8 23 27.9 33.5 51.5c11.6 26 20.9 58.2 27 94.7zm-209 0H18.6C48.6 85.9 112.2 29.1 190.6 8.4C165.1 42.6 145.3 96.1 135.3 160zM8.1 192H131.2c-2.1 20.6-3.2 42-3.2 64s1.1 43.4 3.2 64H8.1C2.8 299.5 0 278.1 0 256s2.8-43.5 8.1-64zM194.7 446.6c-11.6-26-20.9-58.2-27-94.6H344.3c-6.1 36.4-15.5 68.6-27 94.6c-10.5 23.6-22.2 40.7-33.5 51.5C272.6 508.8 263.3 512 256 512s-16.6-3.2-27.8-13.8c-11.3-10.8-23-27.9-33.5-51.5zM135.3 352c10 63.9 29.8 117.4 55.3 151.6C112.2 482.9 48.6 426.1 18.6 352H135.3zm358.1 0c-30 74.1-93.6 130.9-171.9 151.6c25.5-34.2 45.2-87.7 55.3-151.6H493.4z"/>
                                    </svg>
                                </a>

                                <div id="tooltip-open-link" role="tooltip"
                                     class="absolute z-10 invisible inline-block px-3 py-2 text-sm font-medium text-white transition-opacity duration-300 bg-gray-900 rounded-lg shadow-sm opacity-0 tooltip dark:bg-gray-700">
                                    Open wish link in new tab
                                    <div class="tooltip-arrow" data-popper-arrow></div>
                                </div>
                            @endif

                                <x-share-page :wish="$wish"/>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
