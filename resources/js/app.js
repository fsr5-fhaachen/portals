import { createApp, h } from "vue";
import "../css/app.css";
import { createInertiaApp } from "@inertiajs/inertia-vue3";
import { InertiaProgress } from "@inertiajs/progress";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";
import { library } from "@fortawesome/fontawesome-svg-core";
import { faHandLizard } from "@fortawesome/free-regular-svg-icons";
import {
  faBars,
  faDoorOpen,
  faCircleInfo,
  faPlay,
  faX,
} from "@fortawesome/free-solid-svg-icons";
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import {
  plugin as formkitPlugin,
  defaultConfig as formkitDefaultConfig,
} from "@formkit/vue";
import formkitConfig from "./formkit.config.ts";
import DefaultLayout from "./layouts/DefaultLayout.vue";
import DashboardLayout from "./layouts/DashboardLayout.vue";

InertiaProgress.init({
  color: "#22948C",
});
library.add(faBars, faDoorOpen, faCircleInfo, faPlay, faX, faHandLizard);

createInertiaApp({
  resolve: (name) => {
    const page = resolvePageComponent(
      `./pages/${name}.vue`,
      import.meta.glob("./pages/**/*.vue")
    );

    page.then((module) => {
      // apply layout based on folder structure
      if (name.startsWith("Dashboard/")) {
        module.default.layout = module.default.layout || DashboardLayout;
      } else {
        module.default.layout = module.default.layout || DefaultLayout;
      }
    });

    return page;
  },
  setup({ el, App, props, plugin }) {
    createApp({ render: () => h(App, props) })
      .use(plugin)
      .use(formkitPlugin, formkitDefaultConfig(formkitConfig))
      .component("FontAwesomeIcon", FontAwesomeIcon)
      .mount(el);
  },
});
