import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import path from 'path'

export default defineConfig({
  plugins: [vue()],

  // Replace process.env.NODE_ENV in browser UMD builds (webpack did this automatically).
  define: {
    'process.env.NODE_ENV': JSON.stringify('production')
  },

  resolve: {
    alias: {
      '@': path.resolve(__dirname, 'src')
    },
    extensions: ['.mjs', '.js', '.json', '.vue']
  },

  css: {
    preprocessorOptions: {
      scss: {
        additionalData: (content, filepath) => {
          if (filepath.includes('src/scss')) return content
          return `@import "${path.resolve(__dirname, 'src/scss/global.scss')}";\n${content}`
        },
        includePaths: [path.resolve(__dirname, 'node_modules')]
      }
    }
  },

  build: {
    lib: {
      entry: path.resolve(__dirname, 'src/main.js'),
      name: 'activity_finder_4',
      formats: ['umd'],
      fileName: () => 'activity_finder_4.umd.min.js'
    },
    rollupOptions: {
      // W5-P6: axios removed — all HTTP via native fetch, zero external deps.
      // W9-P0: vue externalized to openy_system/vue3 (window.Vue global,
      // vue.global.prod.min.js — full runtime+compiler build, so the root
      // component's innerHTML/DOM-template mount still compiles at runtime).
      external: ['vue'],
      output: {
        globals: {
          vue: 'Vue'
        },
        assetFileNames: (assetInfo) => {
          if (assetInfo.name === 'style.css') return 'activity_finder_4.css'
          return assetInfo.name
        }
      }
    }
  }
})
