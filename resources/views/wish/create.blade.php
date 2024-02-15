<x-app-layout>
    <div class="max-w-7xl mx-auto py-6 lg:py-8 px-8 md:px-40">
        <div>
            <form method="POST" action="{{ route('wish.store') }}">
                @csrf
                <div class="space-y-12">
                    <div class="border-b border-gray-900/10 pb-12">
                        <h2 class="text-base font-semibold leading-7 text-gray-900">Your new wish</h2>
                        <p class="mt-1 leading-6 text-gray-600">Create a new Wish and share it with your friends</p>

                        <div class="mt-10 flex flex-col gap-x-6 gap-y-8 sm:grid-cols-6">
                            <div class="sm:col-span-4">
                                <label for="title" class="block font-medium leading-6 text-gray-900">Title
                                    <span class="text-red-300 text-sm">(required)</span>
                                </label>
                                <div class="mt-2">
                                    <input
                                        id="title"
                                        name="title"
                                        type="text"
                                        autocomplete="title"
                                        class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                    >
                                </div>
                            </div>

                            <div class="sm:col-span-4">
                                <label for="description" class="block font-medium leading-6 text-gray-900">Description</label>
                                <div class="mt-2">
                                    <textarea id="description" name="description" rows="3" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"></textarea>
                                </div>
                                <p class="mt-3 text-sm leading-6 text-gray-600">Write a few sentences about wish.</p>
                            </div>

                            <div class="sm:col-span-4">
                                <label for="url" class="block font-medium leading-6 text-gray-900">URL</label>
                                <div class="mt-2">
                                    <input id="url" name="url" type="text" autocomplete="url" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                </div>
                            </div>

                            <div class="sm:col-span-4">
                                <label for="image_url" class="block font-medium leading-6 text-gray-900">Image URL</label>
                                <div class="mt-2">
                                    <input id="image_url" name="image_url" type="text" autocomplete="image_url" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                </div>
                            </div>

                            <div class="sm:col-span-4 grid grid-cols-4 gap-6">
                                <div>
                                    <label for="amount" class="block font-medium leading-6 text-gray-900">Amount</label>
                                    <div class="mt-2">
                                        <input id="amount" name="amount" type="number" autocomplete="amount" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                    </div>
                                </div>
                                <div class="pt-1">
                                    <x-currency-select/>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex items-center justify-end gap-x-6">
                    <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Create</button>
                </div>
            </form>
        </div>
    </div>

</x-app-layout>
