import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig(({ mode }) => {
    // Cargar variables de entorno (incluyendo APP_URL)
    const env = loadEnv(mode, process.cwd(), '');
    
    // Obtener el host (IP o localhost) desde APP_URL
    let host = 'localhost';
    try {
        if (env.APP_URL) {
            host = new URL(env.APP_URL).hostname;
        }
    } catch (e) {
        console.error('Error parsing APP_URL from .env:', e);
    }

    return {
        plugins: [
            laravel({
                input: [
                    'resources/css/app.css', 
                    'resources/js/app.js', 
                    'resources/css/estilos.css',
                    'resources/js/scripts.js',
                    'resources/css/ficha-municipal-v4.css',
                    'resources/js/ficha-municipal-v4.js'
                ],
                refresh: true,
            }),
        ],
        server: {
            host: '0.0.0.0',      // Escuchar en todas las interfaces de red
            port: 5174,            // Puerto fijo para este proyecto (evita conflictos con el 5173 por defecto)
            strictPort: true,      // Si el puerto está ocupado, da un error en lugar de cambiar a otro silenciosamente
            hmr: {
                host: host,        // Envía los updates de HMR (Vite dev) a la IP correcta
            },
        },
    };
});

