import http from 'k6/http';
import { check } from 'k6';
import { buildSummary } from './lib/summary.js';

const BASE_URL = (__ENV.BASE_URL || 'http://127.0.0.1').replace(/\/+$/, '');
const JOBS = Number.parseInt(__ENV.JOBS || '10', 10);
const PHOTO_ID = Number.parseInt(__ENV.PHOTO_ID || '', 10);
const INTERNAL_KEY = __ENV.K6_INTERNAL_KEY || '';
const CONFIRM_TARGET = __ENV.CONFIRM_TARGET || '';
const SUMMARY_JSON = __ENV.SUMMARY_JSON || 'storage/logs/k6-ai-queue-summary.json';
const SUMMARY_MARKDOWN = __ENV.SUMMARY_MARKDOWN || 'storage/logs/k6-ai-queue-summary.md';

if (!Number.isInteger(JOBS) || JOBS < 1 || JOBS > 25) {
    throw new Error('JOBS must be an integer from 1 to 25. Start with the default of 10.');
}

if (!Number.isInteger(PHOTO_ID) || PHOTO_ID < 1) {
    throw new Error('PHOTO_ID must reference a synthetic/demo photo that already has a face encoding.');
}

if (!INTERNAL_KEY) {
    throw new Error('K6_INTERNAL_KEY is required. Export it in the shell; do not place it in this file.');
}

const isLoopback = /^https?:\/\/(localhost|127\.0\.0\.1)(:\d+)?$/i.test(BASE_URL);

if (!isLoopback && CONFIRM_TARGET !== 'D-05') {
    throw new Error('Refusing a non-local queue test. Pass -e CONFIRM_TARGET=D-05 after confirming authorization.');
}

export const options = {
    scenarios: {
        enqueue_face_match: {
            executor: 'per-vu-iterations',
            exec: 'enqueueFaceMatch',
            vus: JOBS,
            iterations: 1,
            maxDuration: '30s',
            gracefulStop: '5s',
        },
    },
    thresholds: {
        'http_reqs{phase:load}': [`count==${JOBS}`],
        'http_req_failed{phase:load}': ['rate==0'],
        'checks{phase:load}': ['rate==1'],
        'http_req_duration{phase:load,endpoint:ai-queue}': ['p(95)<1000'],
    },
    userAgent: 'TimTre-D05-k6-AI-Queue/1.0',
    summaryTrendStats: ['avg', 'min', 'med', 'max', 'p(90)', 'p(95)', 'p(99)'],
};

export function enqueueFaceMatch() {
    const response = http.post(
        `${BASE_URL}/api/internal/face-match/trigger`,
        JSON.stringify({ photo_id: PHOTO_ID }),
        {
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Internal-Key': INTERNAL_KEY,
            },
            redirects: 0,
            timeout: '10s',
            tags: { endpoint: 'ai-queue', phase: 'load' },
        },
    );

    check(response, {
        'AI job enqueue returns 200': (result) => result.status === 200,
        'AI job is assigned to face-match queue': (result) => {
            try {
                const payload = result.json();

                return payload.queued === true && payload.queue === 'face-match';
            } catch (_) {
                return false;
            }
        },
    }, { endpoint: 'ai-queue', phase: 'load' });
}

export function handleSummary(data) {
    return buildSummary(data, {
        title: 'D-05 AI queue burst test',
        target: BASE_URL,
        requestsMetric: 'http_reqs{phase:load}',
        errorsMetric: 'http_req_failed{phase:load}',
        checksMetric: 'checks{phase:load}',
        expectedRequests: `${JOBS} exactly`,
        expectedVus: `${JOBS}`,
        jsonPath: SUMMARY_JSON,
        markdownPath: SUMMARY_MARKDOWN,
        durations: [
            {
                label: 'AI enqueue API',
                metric: 'http_req_duration{phase:load,endpoint:ai-queue}',
                acceptance: '< 1000 ms',
            },
        ],
        extraRows: [
            ['Horizon face-match queue drain', 'Record manually', 'No failed jobs / no OOM'],
        ],
    });
}
