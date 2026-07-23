import './bootstrap';

import Alpine from 'alpinejs';
import {
    Chart,
    ArcElement,
    DoughnutController,
    BarElement,
    BarController,
    LineElement,
    LineController,
    PointElement,
    CategoryScale,
    LinearScale,
    Filler,
    Tooltip,
    Legend,
} from 'chart.js';

Chart.register(ArcElement, DoughnutController, BarElement, BarController, LineElement, LineController, PointElement, CategoryScale, LinearScale, Filler, Tooltip, Legend);
window.Chart = Chart;

window.Alpine = Alpine;

Alpine.start();
