import { createApp, h } from "vue";
import "../css/app.css";
import { createInertiaApp } from "@inertiajs/inertia-vue3";
import { InertiaProgress } from "@inertiajs/progress";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";
import { library } from "@fortawesome/fontawesome-svg-core";
import { faHandLizard } from "@fortawesome/free-regular-svg-icons";
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import {
  plugin as formkitPlugin,
  defaultConfig as formkitDefaultConfig,
} from "@formkit/vue";
import formkitConfig from "./formkit.config.ts";

InertiaProgress.init();
library.add(faHandLizard);

createInertiaApp({
  resolve: (name) =>
    resolvePageComponent(
      `./pages/${name}.vue`,
      import.meta.glob("./pages/**/*.vue")
    ),
  setup({ el, App, props, plugin }) {
    createApp({ render: () => h(App, props) })
      .use(plugin)
      .use(formkitPlugin, formkitDefaultConfig(formkitConfig))
      .component("font-awesome-icon", FontAwesomeIcon)
      .mount(el);
  },
});
