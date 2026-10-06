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
            colors: {
                hoyos: {
                    bg: '#080002',
                    surface: '#0E0204',
                    card: '#150307',
                    cardHover: '#1D050B',
                    red: '#FF002E',
                    redHover: '#E00029',
                    darkred: '#4A0D16',
                    border: '#2A080E',
                    borderBright: '#5C121E',
                    text: '#FFFFFF',
                    muted: '#A59294',
                    dim: '#664F52',
                },
            },
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                display: ['Anton', 'sans-serif'],
                heading: ['Anton', 'sans-serif'],
                mono: ['"Space Mono"', ...defaultTheme.fontFamily.mono],
            },
            boxShadow: {
                'crimson-glow': '0 0 25px rgba(255, 0, 46, 0.4)',
                'crimson-subtle': '0 0 15px rgba(255, 0, 46, 0.2)',
            },
        },
    },

    plugins: [forms],
};
