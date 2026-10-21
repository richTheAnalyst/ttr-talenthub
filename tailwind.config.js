/** @type {import('tailwindcss').Config} */
export default {
    content: ['./resources/views/**/*.blade.php', './resources/js/**/*.js', './public/js/**/*.js'],
    theme: {
        extend: {
            colors: {
                // Ink: a blue-leaning neutral used for text, borders and the page itself.
                ink: {
                    50: '#f4f7fb', 100: '#e9eef6', 200: '#d6deec', 300: '#b9c5db', 400: '#8d9bbb',
                    500: '#66749a', 600: '#4a5779', 700: '#323f60', 800: '#1d2848', 900: '#101b3a',
                },
                // Cobalt: the one action colour.
                cobalt: {
                    50: '#eef2ff', 100: '#dde6ff', 200: '#bccdff', 300: '#8fa9ff', 400: '#5f7ef5',
                    500: '#3a5ce5', 600: '#2347d6', 700: '#1c39ad', 800: '#1b3189', 900: '#1a2c6b',
                },
                // Gold: reserved for the "you are here" marker and the primary conversion CTA.
                gold: {
                    100: '#fdf1c6', 300: '#f8d557', 400: '#f5c630', 500: '#f2b705', 600: '#c99200',
                },
                  // Coral: warm accent for the public home page.
                coral: {
                    50: '#fff1f3', 100: '#ffe0e5', 400: '#ff6b85', 500: '#ff385c', 600: '#e31c5f', 700: '#bd1a52',
                },
            },
            fontFamily: {
                display: ['"Bricolage Grotesque Variable"', 'ui-sans-serif', 'system-ui', 'sans-serif'],
            },
        },
    },
    plugins: [],
};
