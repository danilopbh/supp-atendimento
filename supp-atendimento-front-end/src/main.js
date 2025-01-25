import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import vuetify from './plugins/vuetify'
import '@mdi/font/css/materialdesignicons.css'  // Add this line

const app = createApp(App)
app.use(router)
app.use(vuetify)  // Esta linha é crucial
app.mount('#app')