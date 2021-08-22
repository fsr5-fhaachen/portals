require('./bootstrap');
import Vue from 'vue';
import VueRouter from 'vue-router';
import App from './App.vue';
import BootstrapVue from 'bootstrap-vue';
import Home from './views/Home.vue';

Vue.use(BootstrapVue);

Vue.use(VueRouter);

const routes = [
  {
    path: '/',
    component: Home,
    name: 'home',
  },
];

const router = new VueRouter({
  routes,
});

const app = new Vue({
  el: '#app',
  router,
  render: h => h(App),
});