<div x-data="{ options: [], selectedOption: null }" x-init="loadOptions">
    <label for="select-currency" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Currency</label>
    <select x-model="selectedOption" id="select-currency" name="currency"
            class="block w-full p-2 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"">
        <option selected value="0">Choose a currency</option>
        <template x-for="option in options" :key="option.id">
            <option :value="option.short_code" x-text="`${option.name} (${option.short_code})`" x-bind:selected="option.short_code === '{{ $selectedCurrency }}'"></option>
        </template>
        <template x-if="options.length === 0">
            <option disabled>Loading...</option>
        </template>
    </select>

    <script>
        function loadOptions() {
            axios.get('/currency/all')
                .then(response => {
                    this.options = response.data;
                })
                .catch(error => {
                    console.error(error);
                });
        }
    </script>
</div>
