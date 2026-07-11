import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Paleta da marca, a partir da logo (vinho escuro como cor
                // principal, rosa vibrante reservado para detalhes/destaques).
                brand: {
                    50: '#fdf2f6',
                    100: '#fbe6ee',
                    200: '#f4c2d6',
                    300: '#e691b0',
                    400: '#c85c82',
                    500: '#a8395a',
                    600: '#873a4e',
                    700: '#6b2c3d',
                    800: '#4f2030',
                    900: '#3a1824',
                },
                accent: {
                    DEFAULT: '#cf006f',
                    50: '#fef1f7',
                    500: '#cf006f',
                    600: '#b8005f',
                },
            },
        },
    },

    plugins: [forms],
};
