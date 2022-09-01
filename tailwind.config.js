/** @type {import('tailwindcss').Config} */
const defaultTheme = require("tailwindcss/defaultTheme");

module.exports = {
  content: [
    "./resources/**/*.js",
    "./resources/**/*.ts",
    "./resources/**/*.vue",
    "./resources/**/*.blade.php",
  ],
  theme: {
    extend: {
      colors: {
        fhac: {
          mint: "#00b5ad",
          "mint-dark": "#22948C",
        },
      },
      fontFamily: {
        sans: ["Inter var", ...defaultTheme.fontFamily.sans],
      },
    },
  },
  plugins: [
    require("@tailwindcss/forms"),
    require("@formkit/themes/tailwindcss"),
  ],
};
