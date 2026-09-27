import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
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
                cream: {
                    50: '#FFFDF9',
                    100: '#FFF8ED',
                    200: '#FCEFD9',
                    300: '#F5E3BE', // primary — background utama, card, nav
                    400: '#EAD2A0',
                    500: '#D9BC7C',
                    600: '#B8985A',
                    700: '#96784A',
                },
                income: {
                    DEFAULT: '#4A7C59',
                    light: '#E8F0EA',
                },
                expense: {
                    DEFAULT: '#B85C4A',
                    light: '#F5E7E4',
                },
                warning: '#C7912E',
                ink: '#3A3128',
                'ink-muted': '#6B6154',
            },
        },
    },

    plugins: [forms],
};
