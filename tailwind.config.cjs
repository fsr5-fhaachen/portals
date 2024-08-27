/** @type {import('tailwindcss').Config} */

const defaultTheme = require("tailwindcss/defaultTheme");

module.exports = {
  content: [
    "./resources/**/*.js",
    "./resources/**/*.ts",
    "./resources/**/*.vue",
    "./resources/**/*.blade.php",
    "./database/seeders/*.php",
  ],
  darkMode: "class",
  theme: {
    extend: {
      keyframes: {
        wiggle: {
          "0%, 100%": {
            transform: "rotate(-15deg)",
          },
          "50%": {
            transform: "rotate(15deg)",
          },
        },
        fly: {
          from: {
            transform: "translateY(100vh)",
          },
          to: {
            transform: "translateY(-100%)",
          },
        },
      },
      animation: {
        wiggle: "wiggle 2s ease-in-out infinite",
        fly: "fly 75s infinite linear alternate",
      },
      colors: {
        fhac: {
          mint: "#00b5ad",
          "mint-dark": "#22948C",
        },
      },
      fontFamily: {
        sans: ["Inter var", ...defaultTheme.fontFamily.sans],
        "eighty-miles": ['"EIGHTY MILES"'],
      },
    },
  },
  plugins: [
    require("@tailwindcss/forms"),
    require("@formkit/themes/tailwindcss"),
    require("@tailwindcss/typography"),
  ],
};
