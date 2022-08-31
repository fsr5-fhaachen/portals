/** @type {import('tailwindcss').Config} */
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
    },
  },
  plugins: [
    require("@tailwindcss/forms"),
    require("@formkit/themes/tailwindcss"),
  ],
};
