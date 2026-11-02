/** @type {import('tailwindcss').Config} */
/*
 * "ضوء — Showroom" design system.
 * Calm off-white surfaces, product renders as the hero, ink for actions,
 * one electric yellow (the light) used sparingly, WhatsApp green only for ordering.
 */
module.exports = {
  content: [
    './*.php',
    './inc/**/*.php',
    './template-parts/**/*.php',
    './templates/**/*.php',
    './assets/js/src/**/*.js',
  ],
  theme: {
    container: { center: true, padding: { DEFAULT: '1rem', sm: '1.25rem', lg: '2rem' }, screens: { '2xl': '1280px' } },
    extend: {
      colors: {
        ink: { DEFAULT: '#15171A', 2: '#3A3D42' },
        muted: '#6F7278',
        bg: '#FAF9F6',
        stage: '#F1EFEA',
        line: '#E7E4DE',
        glow: { DEFAULT: '#FFC629', soft: '#FFF3C4', ink: '#7A5800' },
        wa: { DEFAULT: '#1FAF5A', ink: '#15873F' },
      },
      fontFamily: {
        sans: ['"IBM Plex Sans Arabic"', 'Tahoma', 'system-ui', 'sans-serif'],
        display: ['Alexandria', '"IBM Plex Sans Arabic"', 'sans-serif'],
      },
      borderRadius: { '4xl': '2rem' },
      boxShadow: {
        soft: '0 1px 2px rgba(21,23,26,.04), 0 8px 24px -12px rgba(21,23,26,.12)',
        lift: '0 24px 48px -24px rgba(21,23,26,.28)',
      },
    },
  },
  plugins: [],
};
