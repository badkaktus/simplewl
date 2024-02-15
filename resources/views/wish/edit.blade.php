<x-app-layout>
    <div
        class="max-w-7xl mx-auto py-6 lg:py-8 px-8 md:px-40"
        x-data="{
        loading: false,
        inputText: '{{ $wish->title }}',
        descText: '{{ $wish->description }}',
        url: '{{ $wish->url }}',
        imageUrl: '{{ $wish->image_url }}',
        amount: '{{ $wish->amount }}',
        }"
    >
        <form method="POST" action="{{ route('wish.update', $wish) }}">
            @method('PUT')
            @csrf
            <div class="space-y-12">
                <div class="border-b border-gray-900/10 pb-12">
                    <h2 class="text-base font-semibold leading-7 text-gray-900">
                        Edit your wish
                    </h2>
                    <p class="mt-1 text-sm leading-6 text-gray-600">
                        If something has changed, edit your Wish on this page
                    </p>

                    <div class="mt-10 flex flex-col gap-x-6 gap-y-8 sm:grid-cols-6">
                        <div class="sm:col-span-4">
                            <label for="title" class="block text-sm font-medium leading-6 text-gray-900">Title</label>
                            <div class="mt-2">
                                <input
                                    x-model="inputText"
                                    id="title"
                                    name="title"
                                    type="text"
                                    autocomplete="title"
                                    class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                >
                            </div>
                        </div>

                        <div class="sm:col-span-4">
                            <label for="description" class="block text-sm font-medium leading-6 text-gray-900">Description</label>
                            <div class="mt-2">
                                <textarea
                                    x-model="descText"
                                    id="description"
                                    name="description"
                                    rows="3"
                                    class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                    :disabled="loading"
                                >
                                </textarea>
                            </div>
                            <div
                                id="tooltip-generate-ai"
                                role="tooltip"
                                class="absolute z-10 invisible inline-block px-3 py-2 text-sm font-medium text-white transition-opacity duration-300 bg-gray-900 rounded-lg shadow-sm opacity-0 tooltip dark:bg-gray-700"
                            >
                                Click and try to generate a description using AI.<br/>To make the description as correct
                                as possible,<br/>fill in all other fields
                                <div class="tooltip-arrow" data-popper-arrow></div>
                            </div>
                            <p class="mt-3 text-sm leading-6 text-gray-600">
                                Write a few sentences about wish or try to
                                <span
                                    @click="getGeneratedDescription"
                                    :class="{ 'cursor-progress': loading, 'cursor-pointer': !loading}"
                                    data-tooltip-target="tooltip-generate-ai"
                                    class="hover:underline text-indigo-600 focus:text-indigo-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
                                >
                                    generate it with AI
                                </span>
                                <svg
                                    :class="{ 'inline-block': loading, 'hidden': !loading }"
                                    aria-hidden="true"
                                    class="w-4 h-4 me-2  text-gray-200 animate-spin dark:text-gray-600 fill-blue-600"
                                    viewBox="0 0 100 101"
                                    fill="none"
                                    xmlns="http://www.w3.org/2000/svg"
                                >
                                    <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/><path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="currentFill"/>
                                </svg>
                            </p>
                        </div>

                        <div class="sm:col-span-4">
                            <label for="url" class="block text-sm font-medium leading-6 text-gray-900">
                                URL
                            </label>
                            <div class="mt-2">
                                <input
                                    x-model="url"
                                    id="url"
                                    name="url"
                                    type="text"
                                    autocomplete="url"
                                    class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                >
                            </div>
                        </div>

                        <div class="sm:col-span-4">
                            <label for="image_url" class="block text-sm font-medium leading-6 text-gray-900">
                                Image URL
                            </label>
                            <div class="mt-2">
                                <input
                                    x-model="imageUrl"
                                    id="image_url"
                                    name="image_url"
                                    type="text"
                                    autocomplete="image_url"
                                    class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                >
                            </div>
                        </div>

                        <div class="sm:col-span-4 grid grid-cols-4 gap-6">
                            <div>
                                <label for="amount" class="block text-sm font-medium leading-6 text-gray-900">
                                    Amount
                                </label>
                                <div class="mt-2">
                                    <input
                                        x-model="amount"
                                        id="amount"
                                        name="amount"
                                        type="number"
                                        autocomplete="amount"
                                        class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                    >
                                </div>
                            </div>
                            <div class="pt-1">
                                <x-currency-select selected-currency="{{ $wish->currency }}"/>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex items-center justify-end gap-x-6">
                <button type="button" class="text-sm font-semibold leading-6 text-gray-900">Cancel</button>
                <button
                    type="submit"
                    class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
                    :disabled="!inputText.trim()"
                    :class="{ 'cursor-not-allowed opacity-50': !inputText.trim() }"
                >
                    Save
                </button>
            </div>
        </form>

        <script>
            function getGeneratedDescription() {
                if (this.loading) return

                this.loading = true
                axios
                    .post('{{ route('generate.description') }}', {
                        title: this.inputText,
                        url: this.url,
                        imageUrl: this.imageUrl,
                        amount: this.amount,
                    })
                    .then(response => {
                        console.log(response.data);
                    })
                    .catch(error => {
                        console.log(error);
                    })
                    .finally(() => {
                        this.loading = false
                    });
            }
        </script>
    </div>

</x-app-layout>
