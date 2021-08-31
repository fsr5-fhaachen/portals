require('./bootstrap');
import Vue from 'vue';
import IconsPlugin from 'bootstrap-vue';
import VueRouter from 'vue-router';
import App from './App.vue';
import BootstrapVue from 'bootstrap-vue';
import Home from './views/Home.vue';
import HomeTutor from './views/HomeTutor.vue';
import TutorView from './views/TutorView.vue';
import Groups from './views/Groups.vue';
import CreateGroup from './views/CreateGroup.vue';
import FinishGroup from './views/FinishGroup.vue';
import 'bootstrap/dist/css/bootstrap.css';
import 'bootstrap-vue/dist/bootstrap-vue.css';
import '../scss/app.scss'

Vue.use(BootstrapVue);

Vue.use(VueRouter);

// Optionally install the BootstrapVue icon components plugin
Vue.use(IconsPlugin)

const routes = [
    {
        path: '/',
        component: Home,
        name: 'home',
    },
    {
        path: '/Tutor',
        component: HomeTutor,
        name: 'HomeTutor',
    },
    {
        path: '/CreateGroup',
        component: CreateGroup,
        name: 'CreateGroup',
    },
    {
        path: '/FinishGroup',
        component: FinishGroup,
        name: 'FinishGroup',
    },
    {
        path: '/TutorView',
        component: TutorView,
        name: 'TutorView',
    },
    {
        path: '/Groups',
        component: Groups,
        name: 'Groups',
    }
];

const router = new VueRouter({
    routes,
    mode: "history"
});

const app = new Vue({
    el: '#app',
    router,
    render: h => h(App),
});
