import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                display: ['"Plus Jakarta Sans"', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Deep navy/charcoal foundation with a single controlled accent,
                // per the UI/UX brief (section 4).
                ink: {
                    50: '#f4f6f8',
                    100: '#e4e8ec',
                    200: '#c7d0d9',
                    300: '#9dacba',
                    400: '#6b7f92',
                    500: '#4c6075',
                    600: '#3a4b5e',
                    700: '#2c3a49',
                    800: '#1c2733',
                    900: '#10161d',
                    950: '#0a0e13',
                },
                accent: {
                    50: '#eefcf6',
                    100: '#d6f7e8',
                    200: '#aeeed3',
                    300: '#78dfb8',
                    400: '#41c996',
                    500: '#1eab7c',
                    600: '#128a65',
                    700: '#106f54',
                    800: '#115846',
                    900: '#10493b',
                },
            },
            boxShadow: {
                card: '0 1px 2px 0 rgb(16 22 29 / 0.05), 0 1px 3px 0 rgb(16 22 29 / 0.08)',
                elevated: '0 8px 24px -8px rgb(16 22 29 / 0.18)',
            },
        },
    },

    plugins: [forms],
};
