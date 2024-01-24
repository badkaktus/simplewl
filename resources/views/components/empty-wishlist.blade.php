<div class="max-w-4xl mx-auto px-10 py-4">
    <div class="flex flex-col justify-center items-center py-12">
        <svg xmlns="http://www.w3.org/2000/svg" height="64" width="64" viewBox="0 0 512 512">
            <!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.-->
            <path fill="#4b5563"
                  d="M400 406.1V288c0-13.3-10.7-24-24-24s-24 10.7-24 24V440.6c-28.7 15-61.4 23.4-96 23.4s-67.3-8.5-96-23.4V288c0-13.3-10.7-24-24-24s-24 10.7-24 24V406.1C72.6 368.2 48 315 48 256C48 141.1 141.1 48 256 48s208 93.1 208 208c0 59-24.6 112.2-64 150.1zM256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM159.6 220c10.6 0 19.9 3.8 25.4 9.7c7.6 8.1 20.2 8.5 28.3 .9s8.5-20.2 .9-28.3C199.7 186.8 179 180 159.6 180s-40.1 6.8-54.6 22.3c-7.6 8.1-7.1 20.7 .9 28.3s20.7 7.1 28.3-.9c5.5-5.8 14.8-9.7 25.4-9.7zm166.6 9.7c5.5-5.8 14.8-9.7 25.4-9.7s19.9 3.8 25.4 9.7c7.6 8.1 20.2 8.5 28.3 .9s8.5-20.2 .9-28.3C391.7 186.8 371 180 351.6 180s-40.1 6.8-54.6 22.3c-7.6 8.1-7.1 20.7 .9 28.3s20.7 7.1 28.3-.9zM208 320v32c0 26.5 21.5 48 48 48s48-21.5 48-48V320c0-26.5-21.5-48-48-48s-48 21.5-48 48z"/>
        </svg>
        <p class="text-xl font-semibold text-gray-600 mb-2">Your wishlist is empty</p>
        <p class="text-gray-500 text-center mb-6">Create first wish and share it with your friends</p>
        <a href="{{ route('wish.create') }}" class="px-4 py-2 rounded bg-blue-500 text-white font-semibold hover:bg-blue-600 focus:bg-blue-600">
            Create a wish
        </a>
    </div>
</div>
