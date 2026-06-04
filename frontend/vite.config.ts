import path from 'node:path'
import { fileURLToPath } from 'node:url'
import tailwindcss from '@tailwindcss/vite'
import react from '@vitejs/plugin-react'
import { defineConfig, loadEnv } from 'vite'

const dirname = path.dirname(fileURLToPath(import.meta.url))

export default defineConfig(({ mode }) => {
  // Keep the dev proxy in sync with the project-root .env app port without
  // duplicating the value (one level up from frontend/).
  const projectEnv = loadEnv(mode, path.resolve(dirname, '..'), '')
  const appPort = projectEnv['APP_PORT'] ?? '8910'
  const target = `http://localhost:${appPort}`

  return {
    plugins: [react(), tailwindcss()],
    resolve: {
      alias: {
        '@': path.resolve(dirname, './src'),
        '@tests': path.resolve(dirname, './tests'),
      },
    },
    server: {
      // Fixed, family-unique dev port (NeNe Serve). strictPort avoids silent
      // fallback into a sibling's range. See AGENTS.md "Local dev ports".
      port: 5189,
      strictPort: true,
      proxy: {
        '/admin': { target, changeOrigin: true },
        '/api': { target, changeOrigin: true },
        '/public': { target, changeOrigin: true },
        '/health': { target, changeOrigin: true },
      },
    },
  }
})
