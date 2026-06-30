import './bootstrap';

import Alpine from 'alpinejs';
import {
    Chart,
    ArcElement,
    DoughnutController,
    BarElement,
    BarController,
    CategoryScale,
    LinearScale,
    Tooltip,
    Legend,
} from 'chart.js';

Chart.register(ArcElement, DoughnutController, BarElement, BarController, CategoryScale, LinearScale, Tooltip, Legend);
window.Chart = Chart;

window.Alpine = Alpine;

Alpine.start();
