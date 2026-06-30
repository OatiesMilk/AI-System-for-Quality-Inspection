import './bootstrap';

import Alpine from 'alpinejs';
import { Chart, ArcElement, DoughnutController, Tooltip, Legend } from 'chart.js';

Chart.register(ArcElement, DoughnutController, Tooltip, Legend);
window.Chart = Chart;

window.Alpine = Alpine;

Alpine.start();
