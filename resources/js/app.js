require('./bootstrap');
import Vue from 'vue';
import IconsPlugin from 'bootstrap-vue';
import App from './App.vue';
import BootstrapVue from 'bootstrap-vue';
import 'bootstrap/dist/css/bootstrap.css';
import 'bootstrap-vue/dist/bootstrap-vue.css';
import '../scss/app.scss';
import { createInertiaApp } from '@inertiajs/inertia-vue';
import { InertiaProgress } from '@inertiajs/progress'

Vue.use(BootstrapVue);

// Optionally install the BootstrapVue icon components plugin
Vue.use(IconsPlugin)

// const routes = [
//     {
//         path: '/',
//         component: Home,
//         name: 'home',
//     },
//     {
//         path: '/Tutor',
//         component: HomeTutor,
//         name: 'HomeTutor',
//     },
//     {
//         path: '/CreateGroup',
//         component: CreateGroup,
//         name: 'CreateGroup',
//     },
//     {
//         path: '/FinishGroup',
//         component: FinishGroup,
//         name: 'FinishGroup',
//     },
//     {
//         path: '/TutorView',
//         component: TutorView,
//         name: 'TutorView',
//     },
//     {
//         path: '/Groups',
//         component: Groups,
//         name: 'Groups',
//     }
// ];

InertiaProgress.init()

createInertiaApp({
  resolve: name => require(`./Pages/${name}`),
  setup({ el, App, props }) {
    new Vue({
      render: h => h(App, props),
    }).$mount(el)
  },
})