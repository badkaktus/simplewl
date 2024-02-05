<div>
    <a href="#" id="dropdownShareLink" data-tooltip-target="tooltip-share-page" data-dropdown-toggle="dropdownShare" data-dropdown-placement="bottom">
        @if($isShowIcon)
            <svg xmlns="http://www.w3.org/2000/svg" height="20" width="22.5" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M307 34.8c-11.5 5.1-19 16.6-19 29.2v64H176C78.8 128 0 206.8 0 304C0 417.3 81.5 467.9 100.2 478.1c2.5 1.4 5.3 1.9 8.1 1.9c10.9 0 19.7-8.9 19.7-19.7c0-7.5-4.3-14.4-9.8-19.5C108.8 431.9 96 414.4 96 384c0-53 43-96 96-96h96v64c0 12.6 7.4 24.1 19 29.2s25 3 34.4-5.4l160-144c6.7-6.1 10.6-14.7 10.6-23.8s-3.8-17.7-10.6-23.8l-160-144c-9.4-8.5-22.9-10.6-34.4-5.4z"/></svg>
        @else
            Share
        @endif
    </a>
    <div id="tooltip-share-page" role="tooltip"
         class="absolute z-10 invisible inline-block px-3 py-2 text-sm font-medium text-white transition-opacity duration-300 bg-gray-900 rounded-lg shadow-sm opacity-0 tooltip dark:bg-gray-700">
        Share this page with your friends
        <div class="tooltip-arrow" data-popper-arrow></div>
    </div>

    <div id="dropdownShare" class="z-10 hidden bg-white divide-y divide-gray-100 rounded-lg shadow w-96 p-4">
        <div class="relative w-full" x-data="{
                    link: '{{ URL::current() }}',
                    copied: false,
                    copy () {
                        $clipboard(this.link)
                        this.copied = true
                        clearTimeout(this.timeout)
                        this.timeout = setTimeout(() => {
                            this.copied = false
                          }, 3000)
                    }
                }">
            <input
                type="text"
                id="small-input"
                class="block w-full p-2 text-gray-900 border border-gray-300 rounded-lg bg-gray-50 sm:text-xs focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                value="{{ URL::current() }}"
                disabled
            >
            <button type="submit"
                    x-on:click="copy" x-text="copied ? `Copied!` : `Copy`"
                    class="absolute w-24 top-0 end-0 h-full text-sm font-medium text-white bg-blue-700 rounded-e-lg border border-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                Copy
            </button>
        </div>
    </div>
</div>
