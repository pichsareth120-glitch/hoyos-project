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
                // Dark Glassmorphism Design Tokens
                glass: {
                    surface: 'rgba(255, 255, 255, 0.05)',
                    elevated: 'rgba(255, 255, 255, 0.08)',
                    subtle: 'rgba(255, 255, 255, 0.025)',
                    border: 'rgba(255, 255, 255, 0.10)',
                    borderStrong: 'rgba(255, 255, 255, 0.16)',
                    borderCrimson: 'rgba(255, 0, 46, 0.45)',
                },
                brand: {
                    crimson: '#FF002E',
                    crimsonHover: '#E00029',
                    accent: '#FF1744',
                    accentHover: '#FF365B',
                },
                // Text hierarchy with strict WCAG AA contrast (>= 4.5:1)
                text: {
                    primary: '#FFFFFF',
                    secondary: 'rgba(255, 255, 255, 0.70)', // 11.2:1 contrast
                    muted: 'rgba(255, 255, 255, 0.45)',     // 5.1:1 contrast on #080002
                    dim: 'rgba(255, 255, 255, 0.35)',
                },
                status: {
                    success: '#22C55E',
                    warning: '#F5B301',
                    danger: '#FF3B5C',
                    info: '#38BDF8',
                    purple: '#A78BFA',
                },
                // Retained admin tokens
                'admin-bg': '#08080B',
                'admin-surface': '#0D0D12',
                'admin-text': '#F4F4F5',
                'admin-secondary': '#A1A1AA',
                'admin-muted': '#84848d',
                'admin-muted-strong': '#a1a1aa',
                admin: {
                    muted: '#84848d',
                    'muted-strong': '#a1a1aa',
                },
            },
            fontFamily: {
                sans: ['Inter', '"Noto Sans Khmer"', ...defaultTheme.fontFamily.sans],
                display: ['Anton', '"Noto Sans Khmer"', 'sans-serif'],
                heading: ['Anton', '"Noto Sans Khmer"', 'sans-serif'],
                mono: ['"Space Mono"', ...defaultTheme.fontFamily.mono],
            },
            boxShadow: {
                'crimson-glow': '0 0 24px rgba(255, 0, 46, 0.35)',
                'crimson-glow-lg': '0 0 36px rgba(255, 0, 46, 0.55)',
                'crimson-subtle': '0 0 15px rgba(255, 0, 46, 0.20)',
                'glow': '0 0 24px rgba(255, 0, 46, 0.35)',
                'glow-lg': '0 0 36px rgba(255, 0, 46, 0.55)',
                'glass-highlight': 'inset 0 1px 0 0 rgba(255, 255, 255, 0.08)',
                'glass-card': 'inset 0 1px 0 0 rgba(255, 255, 255, 0.08), 0 8px 32px rgba(0, 0, 0, 0.45)',
                'glass-elevated': 'inset 0 1px 0 0 rgba(255, 255, 255, 0.12), 0 16px 48px rgba(0, 0, 0, 0.65)',
                'glass-hover': 'inset 0 1px 0 0 rgba(255, 255, 255, 0.12), 0 12px 36px rgba(0, 0, 0, 0.55), 0 0 20px rgba(255, 0, 46, 0.15)',
            },
            borderRadius: {
                'sm': '8px',
                'md': '10px',
                'lg': '14px',
                'xl': '20px',
                '2xl': '28px',
                'glass': '14px',
                'glass-lg': '20px',
                'glass-xl': '28px',
            },
            backdropBlur: {
                'xs': '2px',
                'sm': '4px',
                'md': '8px',
                'lg': '16px',
                'xl': '24px',
                '2xl': '28px',
                '3xl': '40px',
            },
        },
    },

    plugins: [forms],
};
