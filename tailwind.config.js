/** @type {import('tailwindcss').Config} */
module.exports = {
    content: ["./app/Views/**/*.php", "./public_html/**/*.php"],
    theme: {
        extend: {
            colors: {
                'fezadan-red': '#6D2323',
                'fezadan-beige': '#FEF9E1',
            },
        },
    },
    plugins: [],
}