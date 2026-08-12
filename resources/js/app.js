import './bootstrap';
import './reverb-echo';
import './notifications';
import './nav';
import './gps-realtime';
import './kyc-image-capture';

import { createCaseHeatmap } from './map-heatmap';
import { createMapRadiusFilter } from './map-radius-filter';

window.createCaseHeatmap = createCaseHeatmap;
window.createMapRadiusFilter = createMapRadiusFilter;
