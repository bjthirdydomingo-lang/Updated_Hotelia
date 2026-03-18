/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./**/*.{php,html,js}",
  ],
  darkMode: 'class',
  theme: {
    extend: {
      colors: {
        /* =======================================
           SHARED BRAND COLORS
        ======================================= */
        brand: {
          teal:       '#008080', // ✅ Main brand color – primary buttons, key highlights, links, prices
          tealHover:  '#00A0A0', // ✅ Hover state for primary teal buttons/links
          tealActive: '#006666', // ✅ Active/pressed teal state
          gold:       '#D3AF37', // ✅ Gold accent – call-to-action highlights, VIP badges
          goldHover:  '#EBD698', // ✅ Gold hover/active accents
          goldActive: '#B89417', // ✅ Stronger gold for pressed buttons
        },

        /* =======================================
           LIGHT MODE COLORS
           Use on light theme pages and surfaces
        ======================================= */
        light: {
          /* ----- Surfaces & Layouts ----- */
          page:    '#FAF9F6', // ✅ Main page background – booking dashboard, POS background
          surface: '#FDFDFC', // ✅ Card and modal backgrounds – forms, popovers, small panels
          menu:    '#FFFFFF', // ✅ Dropdown menus, tooltips, navigation popovers
          hover:   '#F3F4F6', // ✅ Hover background for cards, table rows, buttons
          border:  '#E5E7EB', // ✅ Default border for cards, inputs, dividers

          /* ----- Text ----- */
          text: {
            heading:   '#111827', // ✅ Page titles, modal headers, section headings
            primary:   '#1F2937', // ✅ Main body text, input values, table content
            secondary: '#4B5563', // ✅ Sub-labels, helper descriptions, POS small notes
            muted:     '#9CA3AF', // ✅ Placeholder text, disabled labels, subtle metadata
          },

         /* ----- Status & Alerts ----- */
          success: {
            text:   '#FFFFFF',       // ✅ White text
            bg:     '#1ebf62',       // ✅ Success green
            border: '#0ea54c',       // ✅ Darker green border
          },
          warning: {
            text:   '#7c2d12',       // ✅ Dark amber text
            bg:     '#FFD700',       // ✅ Warning gold
            border: '#e6c100',       // ✅ Darker gold border
          },
          error: {
            text:   '#FFFFFF',       // ✅ White text
            bg:     '#FB3545',       // ✅ Error red
            border: '#e01f30',       // ✅ Darker red border
          },
          info: {
            text:   '#FFFFFF',       // ✅ White text
            bg:     '#3B82F6',       // ✅ Info blue (vibrant alternative)
            border: '#2563EB',       // ✅ Darker blue border
          },
        },

      
        /* =======================================
           DARK MODE COLORS
           Use on dark theme dashboards & modals
        ======================================= */
        dark: {
          /* ----- Surfaces & Layouts ----- */
          page:    '#111827', // ✅ Full-page background – POS night mode, booking dark theme
          surface: '#1F2937', // ✅ Card and modal backgrounds – receipts, panels
          menu:    '#374151', // ✅ Dropdowns, side navs, floating menus
          hover:   '#4B5563', // ✅ Hover background for list items, table rows
          border:  '#4B5563', // ✅ Default border for inputs, cards, separators

          /* ----- Text ----- */
          text: {
            heading:   '#FFFFFF', // ✅ Page titles, section headers in dark mode
            primary:   '#F9FAFB', // ✅ Main body text, POS item names
            secondary: '#D1D5DB', // ✅ Sub-labels, helper text
            muted:     '#9CA3AF', // ✅ Placeholder, disabled, subtle metadata
          },

          /* ----- Status & Alerts ----- */
          success: {
            text:   '#34D399', // ✅ Success text on dark backgrounds
            bg:     '#065F46', // ✅ Success badge/alert background
            border: '#047857', // ✅ Success border
          },
          warning: {
            text:   '#FBBF24', // ✅ Warning text – low stock, booking issues
            bg:     '#78350F', // ✅ Warning badge/alert background
            border: '#92400E', // ✅ Warning border
          },
          error: {
            text:   '#F87171', // ✅ Error text – failed actions
            bg:     '#7F1D1D', // ✅ Error badge/alert background
            border: '#B91C1C', // ✅ Error border
          },
          info: {
            text:   '#60A5FA', // ✅ Informational text – POS tips, booking notices
            bg:     '#1E3A8A', // ✅ Info badge/alert background
            border: '#1D4ED8', // ✅ Info border
          },
        },
      },

      fontFamily: {
        arima: ['Arima'],
        mulish: ['Mulish'],
      },
    },
  },
  plugins: [require('daisyui')],

};
