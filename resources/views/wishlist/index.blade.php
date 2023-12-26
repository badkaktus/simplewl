<x-app-layout>
    <div class="py-6 font-bold">
        Link to wishlist:
        <span>
            <a href="{{ route('wishlist.index', $user->name) }}" class="text-indigo-600 hover:text-indigo-900">
                {{ route('wishlist.index', $user->name) }}
            </a>
        </span>
    </div>
    <div class="flex flex-wrap -m-4">
        @foreach($wishes as $wish)
            <a href="{{ route('wish.show', [
                                'user' => $user,
                                'wish' => $wish->slug
                            ]) }}" class="xl:w-1/3 md:w-1/2 p-4">
                <div @class([
                    'border',
                    'border-gray-200',
                    'p-6',
                    'rounded-lg',
                    'relative',
                    'opacity-50' => $wish->is_completed
                ])>
                    @if($wish->is_completed)
                        <span
                                class="bg-green-400 text-green-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded-full ml-2 absolute left-10 top-10">
                                        Completed
                                    </span>
                    @endif
                    <div
                            class="w-full h-1/2 inline-flex items-center justify-center text-indigo-500 mb-4">
                        <x-image-with-check
                                :wish="$wish"
                                :isShowText="false"/>
                    </div>
                    <h2 class="text-lg text-gray-900 font-medium title-font mb-2 text-center">
                        {{ $wish->title }}
                    </h2>
                </div>
            </a>
        @endforeach
    </div>
</x-app-layout>
