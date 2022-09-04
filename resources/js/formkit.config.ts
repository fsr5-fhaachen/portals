import { createAutoAnimatePlugin } from '@formkit/addons';
import { de } from '@formkit/i18n';
import { generateClasses } from '@formkit/themes';
import { DefaultConfigOptions } from '@formkit/vue';

import theme from './formkit.theme.ts';

const config: DefaultConfigOptions = {
  locales: { de },
  locale: "de",
  config: {
    classes: generateClasses(theme),
  },
  plugins: [createAutoAnimatePlugin()],
};

export default config;
