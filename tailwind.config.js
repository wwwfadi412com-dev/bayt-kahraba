/** @type {import('tailwindcss').Config} */
/*
 * "Volt — the datasheet" design system.
 * Paper + ink + one electric orange for actions; green is reserved for solar/ampere.
 * Legacy token names (navy, royal, amber, emerald…) are kept and re-pointed so every
 * template inherits the new identity without renaming classes.
 */
const ink = '#121212';
const brand = {
  ink: { DEFAULT: ink, 2: '#2B2A28' },
  paper: { DEFAULT: '#F2EFE8', 2: '#E8E3D7' },
  card: '#FBFAF7',
  volt: { DEFAULT: '#FF5B1F', ink: '#B83A0A', soft: '#FFE3D6' },
  sun: { DEFAULT: '#12A150', soft: '#DDF3E6', ink: '#0B6B36' },

  navy: { DEFAULT: ink, 900: ink, 800: '#1C1C1C' },
  royal: { DEFAULT: '#2B2A28', 700: ink, 50: '#E8E3D7' },
  amber: { DEFAULT: '#FF5B1F', hover: '#B83A0A', 50: '#FFF1EA', 100: '#FFE3D6' },
  emerald: { DEFAULT: '#12A150', hover: '#0B6B36', 50: '#EAF7EF', 100: '#CDEBDA' },
  canvas: '#F2EFE8',
  surface: '#FBFAF7',
  line: 'rgba(18,18,18,.12)',
  stripe: '#E8E3D7',
  body: '#2B2A28',
  muted: '#6E6A62',
};

module.exports = {
  content: [
    './*.php',
    './inc/**/*.php',
    './template-parts/**/*.php',
    './templates/**/*.php',
    './woocommerce/**/*.php',
    './assets/js/src/**/*.js',
  ],
  // Classes generated from PHP variables (power badges, grid columns, energy scale).
  safelist: [
    'bg-brand-emerald', 'bg-brand-emerald-50', 'text-brand-emerald-hover', 'border-brand-emerald-100',
    'bg-slate-100', 'text-slate-600', 'border-slate-200',
    'bg-brand-amber-50', 'text-brand-amber-hover',
    'lg:grid-cols-3', 'lg:grid-cols-4', 'xl:grid-cols-5',
  ],
  theme: {
    container: { center: true, padding: { DEFAULT: '1rem', lg: '1.5rem' }, screens: { '2xl': '1320px' } },
    extend: {
      colors: { brand },
      fontFamily: {
        sans: ['"IBM Plex Sans Arabic"', 'Tahoma', 'system-ui', 'sans-serif'],
        display: ['Alexandria', '"IBM Plex Sans Arabic"', 'sans-serif'],
        mono: ['"IBM Plex Mono"', 'ui-monospace', 'SFMono-Regular', 'monospace'],
        num: ['"IBM Plex Mono"', 'ui-monospace', 'monospace'],
      },
      boxShadow: {
        card: '0 1px 0 rgba(18,18,18,.04)',
        lift: '0 22px 40px -28px rgba(0,0,0,.45)',
        glow: '0 0 0 4px rgba(255,91,31,.22)',
        volt: '0 10px 26px -12px #FF5B1F',
      },
      borderRadius: { xl2: '1.125rem', '4xl': '2rem' },
      letterSpacing: { tightest: '-.035em', label: '.14em' },
      keyframes: {
        spark: { '0%,100%': { opacity: '1' }, '50%': { opacity: '.55' } },
        tick: { to: { transform: 'translateX(50%)' } },
        pulse2: { '50%': { boxShadow: '0 0 0 7px transparent' } },
      },
      animation: {
        spark: 'spark 2.4s ease-in-out infinite',
        tick: 'tick 40s linear infinite',
        pulse2: 'pulse2 2s infinite',
      },
    },
  },
  plugins: [require('@tailwindcss/container-queries')],
};
