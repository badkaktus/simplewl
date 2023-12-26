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
                                    <div class="font-medium">
                                        <form method="POST" action="{{ route('wish.complete',  $wish->slug) }}">
                                            @csrf
                                            <button type="submit"
                                                    class="rounded-full w-10 h-10 hover:bg-gray-200 bg-green-300 p-0 border-0 inline-flex items-center justify-center hover:text-gray-500 ml-4">
                                                <svg xmlns="http://www.w3.org/2000/svg" height="20" width="17.5"
                                                     viewBox="0 0 448 512">
                                                    <!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2023 Fonticons, Inc.-->
                                                    <path
                                                        d="M438.6 105.4c12.5 12.5 12.5 32.8 0 45.3l-256 256c-12.5 12.5-32.8 12.5-45.3 0l-128-128c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0L160 338.7 393.4 105.4c12.5-12.5 32.8-12.5 45.3 0z"/>
                                                </svg>
                                            </button>
                                        </form>

                                    </div>
                                @endif
                                <div>
                                    <a href="{{ route('wish.edit', $wish->slug) }}" class="rounded-full w-10 h-10 hover:bg-gray-300 bg-gray-200 p-0 border-0 inline-flex items-center justify-center hover:text-gray-500 ml-4">
                                        <svg xmlns="http://www.w3.org/2000/svg" height="20" width="20"
                                             viewBox="0 0 512 512">
                                            <!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2023 Fonticons, Inc.-->
                                            <path fill="#1e3050"
                                                  d="M441 58.9L453.1 71c9.4 9.4 9.4 24.6 0 33.9L424 134.1 377.9 88 407 58.9c9.4-9.4 24.6-9.4 33.9 0zM209.8 256.2L344 121.9 390.1 168 255.8 302.2c-2.9 2.9-6.5 5-10.4 6.1l-58.5 16.7 16.7-58.5c1.1-3.9 3.2-7.5 6.1-10.4zM373.1 25L175.8 222.2c-8.7 8.7-15 19.4-18.3 31.1l-28.6 100c-2.4 8.4-.1 17.4 6.1 23.6s15.2 8.5 23.6 6.1l100-28.6c11.8-3.4 22.5-9.7 31.1-18.3L487 138.9c28.1-28.1 28.1-73.7 0-101.8L474.9 25C446.8-3.1 401.2-3.1 373.1 25zM88 64C39.4 64 0 103.4 0 152V424c0 48.6 39.4 88 88 88H360c48.6 0 88-39.4 88-88V312c0-13.3-10.7-24-24-24s-24 10.7-24 24V424c0 22.1-17.9 40-40 40H88c-22.1 0-40-17.9-40-40V152c0-22.1 17.9-40 40-40H200c13.3 0 24-10.7 24-24s-10.7-24-24-24H88z"/>
                                        </svg>
                                    </a>
                                </div>
                                <div>
                                    <form method="POST" action="{{route('wish.destroy',  $wish->slug)}}">
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
                    <div class="flex">
                        <span class="title-font font-medium text-2xl text-gray-900">
                            {{ $wish->amount }} {{ $wish->currency }}
                        </span>
                        <a
                            class="flex ml-auto text-white bg-indigo-500 border-0 py-2 px-6 focus:outline-none hover:bg-indigo-600 rounded"
                            href="{{ $wish->url }}" target="_blank" rel="noopener noreferrer"
                        >
                            Link to item
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
