<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2 bg-brand-600 dark:bg-brand-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-brand-800 uppercase tracking-widest hover:bg-brand-700 dark:hover:bg-white focus:bg-brand-700 dark:focus:bg-white active:bg-accent-500 dark:active:bg-brand-300 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
