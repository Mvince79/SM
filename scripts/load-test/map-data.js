import http from 'k6/http';
import { check } from 'k6';
import { buildSummary } from './lib/summary.js';

const BASE_URL = (__ENV.BASE_URL || 'http://127.0.0.1:8000').replace(/\/+$/, '');
const TOTAL_VUS = Number.parseInt(__ENV.TOTAL_VUS || '50', 10);
const SEARCH_TERM = __ENV.SEARCH_TERM || 'Nguyễn';
const CONFIRM_TARGET = __ENV.CONFIRM_TARGET || '';
const SUMMARY_JSON = __ENV.SUMMARY_JSON || 'storage/logs/k6-public-summary.json';
const SUMMARY_MARKDOWN = __ENV.SUMMARY_MARKDOWN || 'storage/logs/k6-public-summary.md';

if (!Number.isInteger(TOTAL_VUS) || TOTAL_VUS < 3 || TOTAL_VUS > 50) {
    throw new Error('TOTAL_VUS must be an integer from 3 to 50. D-05 acceptance uses exactly 50.');
}

const isLoopback = /^https?:\/\/(localhost|127\.0\.0\.1)(:\d+)?$/i.test(BASE_URL);

if (!isLoopback && CONFIRM_TARGET !== 'D-05') {
    throw new Error('Refusing a non-local load test. Pass -e CONFIRM_TARGET=D-05 after confirming authorization.');
}

const listVus = Math.max(1, Math.floor(TOTAL_VUS * 0.4));
const mapVus = Math.max(1, Math.floor(TOTAL_VUS * 0.4));
const searchVus = TOTAL_VUS - listVus - mapVus;

const urls = {
    listPage: `${BASE_URL}/missing`,
    mapData: `${BASE_URL}/api/v1/map-data`,
    searchApi: `${BASE_URL}/api/v1/missing-children?q=${encodeURIComponent(SEARCH_TERM)}`,
};

export const options = {
    scenarios: {
        list_page: {
            executor: 'per-vu-iterations',
            exec: 'listPage',
            vus: listVus,
            iterations: 1,
            maxDuration: '30s',
            gracefulStop: '5s',
        },
        map_data: {
            executor: 'per-vu-iterations',
            exec: 'mapData',
            vus: mapVus,
            iterations: 1,
            maxDuration: '30s',
            gracefulStop: '5s',
        },
        search_api: {
            executor: 'per-vu-iterations',
            exec: 'searchApi',
            vus: searchVus,
            iterations: 1,
            maxDuration: '30s',
            gracefulStop: '5s',
        },
    },
    thresholds: {
        'http_reqs{phase:load}': [`count==${TOTAL_VUS}`],
        'http_req_failed{phase:load}': ['rate<0.01'],
        'checks{phase:load}': ['rate==1'],
        'http_req_duration{phase:load,endpoint:list-page}': ['p(95)<1000'],
        'http_req_duration{phase:load,endpoint:map-data}': ['p(95)<1000'],
        'http_req_duration{phase:load,endpoint:search-api}': ['p(95)<1000'],
    },
    userAgent: 'TimTre-D05-k6/1.0',
    summaryTrendStats: ['avg', 'min', 'med', 'max', 'p(90)', 'p(95)', 'p(99)'],
};

function requestParams(endpoint, phase = 'load', accept = 'application/json') {
    return {
        headers: { Accept: accept },
        redirects: 0,
        timeout: '10s',
        tags: { endpoint, phase },
    };
}

function validJson(response, validator) {
    try {
        return validator(response.json());
    } catch (_) {
        return false;
    }
}

export function setup() {
    const warmups = [
        http.get(urls.listPage, requestParams('list-page', 'warmup', 'text/html')),
        http.get(urls.mapData, requestParams('map-data', 'warmup')),
        http.get(urls.searchApi, requestParams('search-api', 'warmup')),
    ];

    warmups.forEach((response, index) => {
        if (response.status !== 200) {
            throw new Error(`Warm-up request ${index + 1} returned HTTP ${response.status}; load phase aborted.`);
        }
    });
}

export function listPage() {
    const response = http.get(urls.listPage, requestParams('list-page', 'load', 'text/html'));

    check(response, {
        'list page returns 200': (result) => result.status === 200,
        'list page returns HTML': (result) =>
            (result.headers['Content-Type'] || '').includes('text/html'),
    }, { endpoint: 'list-page', phase: 'load' });
}

export function mapData() {
    const response = http.get(urls.mapData, requestParams('map-data'));

    check(response, {
        'map API returns 200': (result) => result.status === 200,
        'map API has marker collection': (result) => validJson(
            result,
            (payload) => payload.status === 'success' && Array.isArray(payload.data),
        ),
    }, { endpoint: 'map-data', phase: 'load' });
}

export function searchApi() {
    const response = http.get(urls.searchApi, requestParams('search-api'));

    check(response, {
        'search API returns 200': (result) => result.status === 200,
        'search API has paginated data': (result) => validJson(
            result,
            (payload) => Array.isArray(payload.data) && typeof payload.pagination === 'object',
        ),
    }, { endpoint: 'search-api', phase: 'load' });
}

export function handleSummary(data) {
    return buildSummary(data, {
        title: 'D-05 public endpoints load test',
        target: BASE_URL,
        requestsMetric: 'http_reqs{phase:load}',
        errorsMetric: 'http_req_failed{phase:load}',
        checksMetric: 'checks{phase:load}',
        expectedRequests: `${TOTAL_VUS} exactly`,
        expectedVus: `${TOTAL_VUS}`,
        jsonPath: SUMMARY_JSON,
        markdownPath: SUMMARY_MARKDOWN,
        durations: [
            {
                label: 'List page',
                metric: 'http_req_duration{phase:load,endpoint:list-page}',
                acceptance: '< 1000 ms',
            },
            {
                label: 'Map API',
                metric: 'http_req_duration{phase:load,endpoint:map-data}',
                acceptance: '< 1000 ms',
            },
            {
                label: 'Search API',
                metric: 'http_req_duration{phase:load,endpoint:search-api}',
                acceptance: '< 1000 ms',
            },
        ],
    });
}
