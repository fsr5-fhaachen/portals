import { defineConfig, loadEnv } from "vite";
import laravel from "laravel-vite-plugin";
import vue from "@vitejs/plugin-vue";
import Components from "unplugin-vue-components/vite";
import AutoImport from "unplugin-auto-import/vite";

export default defineConfig(({ mode }) => {
  // the dev server is reached under the host of APP_URL, so other devices in the network can load the assets
  const appUrl = loadEnv(mode, process.cwd(), "").APP_URL;
  const hmrHost = URL.canParse(appUrl) ? new URL(appUrl).hostname : "localhost";

  return {
    build: {
      target: "esnext",
    },
    optimizeDeps: {
      esbuildOptions: {
        target: "esnext",
      },
    },
    resolve: {
      alias: {
        "@": "/resources/js",
      },
    },
    server: {
      host: "0.0.0.0",
      hmr: {
        host: hmrHost,
      },
    },
    plugins: [
      laravel({
        input: ["resources/js/app.js"],
        refresh: true,
      }),
      vue({
        template: {
          transformAssetUrls: {
            base: null,
            includeAbsolute: false,
          },
        },
      }),
      AutoImport({
        dirs: ["./resources/js/composables"],
        dts: "./resources/js/types/auto-imports.d.ts",
        directoryAsNamespace: true,
      }),
      Components({
        dirs: ["./resources/js/components", "./resources/js/layouts"],
        dts: "./resources/js/types/components.d.ts",
        directoryAsNamespace: true,
      }),
    ],
    define: {
      __PACKAGE_NAME__: JSON.stringify(process.env.npm_package_name),
      __PACKAGE_REPOSITORY_URL__: JSON.stringify(
        "https://github.com/fsr5-fhaachen/portals/",
      ),
    },
  };
});
