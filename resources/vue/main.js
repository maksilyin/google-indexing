import Vue from 'vue'
import App from './App.vue'
import loader from "vue-ui-preloader";
import SlideUpDown from 'vue-slide-up-down'
import styles from './assets/sass/styles.sass'

Vue.use(loader);
Vue.component('slide-up-down', SlideUpDown)

new Vue({
    el: '#app',
    components:{
        loader:loader,
    },
    render: h => h(App)
})
