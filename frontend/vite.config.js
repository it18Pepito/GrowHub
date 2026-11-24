import { defineConfig, loadEnv } from 'vite'
import vue from '@vitejs/plugin-vue'
import { fileURLToPath, URL } from 'node:url'

function createBackendProxy(target) {
  return {
    target,
    changeOrigin: true,
    configure: (proxy) => {
      proxy.on('error', (_err, _req, res) => {
        if (!res || typeof res.writeHead !== 'function') {
          return
        }
        if (!res.headersSent) {
          res.writeHead(502, { 'Content-Type': 'application/json' })
        }
        if (!res.writableEnded) {
          res.end(
            JSON.stringify({
              message: `Bad Gateway (502): Unable to connect to upstream API server at ${target}.`,
            }),
          )
        }
      })
    },
  }
}

export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, fileURLToPath(new URL('.', import.meta.url)), '')
  const backendTarget = env.VITE_BACKEND_URL || 'http://127.0.0.1:8000'

  return {
    plugins: [vue()],
    resolve: {
      alias: {
        '@': fileURLToPath(new URL('./src', import.meta.url)),
      },
    },
    server: {
      port: 5174,
      proxy: {
        '/api': createBackendProxy(backendTarget),
        '/storage': createBackendProxy(backendTarget),
      },
    },
  }
})
