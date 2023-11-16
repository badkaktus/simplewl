<x-app-layout>
    <div class="max-w-7xl mx-auto p-6 lg:p-8">
        <form method="POST" action="{{ route('wish.update', $wish) }}">
            @method('PUT')
            @csrf
            <div class="space-y-12">
                <div class="border-b border-gray-900/10 pb-12">
                    <h2 class="text-base font-semibold leading-7 text-gray-900">Update wish</h2>
                    <p class="mt-1 text-sm leading-6 text-gray-600">Update wish</p>

                    <div class="mt-10 grid grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-6">
                        <div class="sm:col-span-4">
                            <label for="title" class="block text-sm font-medium leading-6 text-gray-900">Title</label>
                            <div class="mt-2">
                                <input
                                    id="title"
                                    name="title"
                                    type="text"
                                    autocomplete="title"
                                    class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                    value="{{ $wish->title }}"
                                >
                            </div>
                        </div>

                        <div class="col-span-full">
                            <label for="description" class="block text-sm font-medium leading-6 text-gray-900">Description</label>
                            <div class="mt-2">
                                <textarea
                                    id="description"
                                    name="description"
                                    rows="3"
                                    class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                >{{ $wish->description }}</textarea>
                            </div>
                            <p class="mt-3 text-sm leading-6 text-gray-600">Write a few sentences about wish.</p>
                        </div>

                        <div class="sm:col-span-4">
                            <label for="url" class="block text-sm font-medium leading-6 text-gray-900">URL</label>
                            <div class="mt-2">
                                <input
                                    id="url"
                                    name="url"
                                    type="text"
                                    autocomplete="url"
                                    class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                    value="{{ $wish->url }}"
                                >
                            </div>
                        </div>

                        <div class="sm:col-span-4">
                            <label for="image_url" class="block text-sm font-medium leading-6 text-gray-900">Image URL</label>
                            <div class="mt-2">
                                <input
                                    id="image_url"
                                    name="image_url"
                                    type="text"
                                    autocomplete="image_url"
                                    class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                    value="{{ $wish->image_url }}"
                                >
                            </div>
                        </div>

                        <div class="sm:col-span-4">
                            <label for="amount" class="block text-sm font-medium leading-6 text-gray-900">Amount</label>
                            <div class="mt-2">
                                <input
                                    id="amount"
                                    name="amount"
                                    type="number"
                                    autocomplete="amount"
                                    class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                    value="{{ $wish->amount }}"
                                >
                            </div>
                        </div>

                        <div class="sm:col-span-4">
                            <label for="currency" class="block text-sm font-medium leading-6 text-gray-900">Currency</label>
                            <div class="mt-2">
                                <input
                                    id="currency"
                                    name="currency"
                                    type="text"
                                    autocomplete="currency"
                                    class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                    value="{{ $wish->currency }}"
                                >
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex items-center justify-end gap-x-6">
                <button type="button" class="text-sm font-semibold leading-6 text-gray-900">Cancel</button>
                <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Save</button>
            </div>
        </form>
    </div>

</x-app-layout>
