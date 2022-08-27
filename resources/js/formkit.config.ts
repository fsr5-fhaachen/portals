import { de } from '@formkit/i18n';
import { generateClasses } from '@formkit/themes';
import { DefaultConfigOptions } from '@formkit/vue';

import theme from './formkit.theme.js';

const config: DefaultConfigOptions = {
  classes: generateClasses(theme),
  locales: { de },
  locale: "de",
};

export default config;
