import { createAutoAnimatePlugin } from "@formkit/addons";
import { de } from "@formkit/i18n";
import { generateClasses } from "@formkit/themes";
import { DefaultConfigOptions } from "@formkit/vue";
import { faXmark, faUpload } from "@fortawesome/free-solid-svg-icons";

import theme from "./formkit.theme.ts";

const faSvg = ({ icon: [w, h, , , path] }) =>
  `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${w} ${h}"><path fill="currentColor" d="${path}"/></svg>`;

const config: DefaultConfigOptions = {
  locales: { de },
  locale: "de",
  config: {
    classes: generateClasses(theme),
  },
  plugins: [createAutoAnimatePlugin()],
  icons: {
    close: faSvg(faXmark),
    fileDoc: faSvg(faUpload), // Standard-Icon von noFilesIcon und fileItemIcon
  },
};

export default config;
