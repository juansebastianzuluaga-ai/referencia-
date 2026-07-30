import { createApp } from 'vue';
import { createPinia } from 'pinia';
import ElementPlus from 'element-plus';
import es from 'element-plus/es/locale/lang/es';
import 'element-plus/dist/index.css';
import VueApexCharts from 'vue3-apexcharts';
import { MotionPlugin } from '@vueuse/motion';
import '../css/tailwind.css';
import '../css/app.scss';
import App from './App.vue';

import router from './router';
import PermissionDirective from './directives/permission';

const app = createApp(App);
const pinia = createPinia();

app.use(pinia);
app.use(router);
app.use(ElementPlus, {
  locale: es,
});
app.use(MotionPlugin);
app.directive('permission', PermissionDirective);
app.component('apexchart', VueApexCharts);

app.mount('#app');
