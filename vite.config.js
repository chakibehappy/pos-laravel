// import { defineConfig } from 'vite';
// import laravel from 'laravel-vite-plugin';
// import tailwindcss from '@tailwindcss/vite';
// import vue from '@vitejs/plugin-vue'

// export default defineConfig({
//     base: '/pos/build/',
//     plugins: [
//         laravel({
//             input: ['resources/css/app.css', 'resources/js/app.js'],
//             refresh: true,
//         }),
//         vue(),
//         tailwindcss(),
//     ],
//     server: {
//         watch: {
//             ignored: ['**/storage/framework/views/**'],
//         },
//     },
// });
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue'

export default defineConfig({
    base: '/pos/build/',
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        vue(),
        tailwindcss(),
    ],
    server: {
        // TAMBAHKAN INI
        host: '0.0.0.0', 
        hmr: {
            host: '192.168.1.184',
        },
        // ---
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});