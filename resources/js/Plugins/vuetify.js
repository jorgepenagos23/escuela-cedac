import '@mdi/font/css/materialdesignicons.css'
import 'vuetify/styles'
import { createVuetify } from 'vuetify'
import * as components from 'vuetify/components'
import * as labsComponents from 'vuetify/labs/components'
import * as directives from 'vuetify/directives'
import { aliases, mdi } from 'vuetify/iconsets/mdi'

const vuetify = createVuetify({
  components: {
    ...components,
    ...labsComponents,
  },
  directives,

  theme: {
    dark: true,
    themes: {
      dark: {
        primary: '#3f51b5',
        secondary: '#ff4081',
        accent: '#8c9eff',
        error: '#b71c1c',
        warning: '#ffb300',
        info: '#2196f3',
        success: '#4caf50'
      },
    },
  },
  icons: {
    defaultSet: 'mdi',
    aliases,
    sets: {
      mdi,
    },
  },
})

export default vuetify
