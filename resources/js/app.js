import './bootstrap';
import { createApp } from 'vue';
import CalculatorApp from './components/CalculatorApp.vue';

const root = document.getElementById('app');

if (root) {
    createApp(CalculatorApp).mount(root);
}
