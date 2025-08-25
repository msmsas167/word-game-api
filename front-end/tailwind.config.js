/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{vue,js,ts,jsx,tsx}", // This is the important line
  ],
  theme: {
    extend: {},
  },
  plugins: [],
}