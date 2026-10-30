/** @type {import('tailwindcss').Config} */
const brand = {
  navy: { DEFAULT: '#0F172A', 900: '#0F172A', 800: '#1E293B' },
  royal: { DEFAULT: '#1E3A8A', 700: '#1D4ED8', 50: '#EFF6FF' },
  amber: { DEFAULT: '#F59E0B', hover: '#D97706', 50: '#FFFBEB', 100: '#FEF3C7' },
  emerald: { DEFAULT: '#10B981', hover: '#059669', 50: '#ECFDF5', 100: '#D1FAE5' },
  canvas: '#F8FAFC',
  surface: '#FFFFFF',
  line: '#E2E8F0',
  stripe: '#F1F5F9',
  ink: '#0F172A',
  body: '#334155',
  muted: '#64748B',
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
  // Classes generated from PHP variables (power badges, brand accents, grid columns).
  safelist: [
    'bg-brand-emerald', 'bg-brand-emerald-50', 'text-brand-emerald-hover', 'border-brand-emerald-100',
    'bg-slate-100', 'text-slate-600', 'border-slate-200',
    'bg-brand-amber-50', 'text-brand-amber-hover',
    'lg:grid-cols-3', 'lg:grid-cols-4', 'xl:grid-cols-5',
  ],
  theme: {
    container: { center: true, padding: { DEFAULT: '1rem', lg: '1.5rem' }, screens: { '2xl': '1360px' } },
    extend: {
      colors: { brand },
      fontFamily: {
        sans: ['"IBM Plex Sans Arabic"', 'Tahoma', 'system-ui', 'sans-serif'],
        num: ['"IBM Plex Sans Arabic"', 'ui-monospace', 'SFMono-Regular', 'monospace'],
      },
      boxShadow: {
        card: '0 1px 2px rgba(15,23,42,.04), 0 1px 3px rgba(15,23,42,.06)',
        lift: '0 12px 28px -8px rgba(15,23,42,.18), 0 4px 10px -4px rgba(15,23,42,.08)',
        glow: '0 0 0 4px rgba(245,158,11,.25)',
      },
      borderRadius: { xl2: '1.125rem' },
      keyframes: {
        'spark': { '0%,100%': { opacity: '1' }, '50%': { opacity: '.55' } },
        'slide-in': { from: { transform: 'translateX(-100%)' }, to: { transform: 'translateX(0)' } },
      },
      animation: { spark: 'spark 2.4s ease-in-out infinite' },
    },
  },
  plugins: [require('@tailwindcss/container-queries')],
};
