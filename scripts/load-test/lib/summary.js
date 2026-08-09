function metricValue(data, metricName, valueName) {
    const metric = data.metrics[metricName];

    return metric && metric.values ? metric.values[valueName] : null;
}

function number(value, digits = 2) {
    return value === null || value === undefined
        ? 'n/a'
        : Number(value).toFixed(digits);
}

function percentage(value) {
    return value === null || value === undefined
        ? 'n/a'
        : `${number(Number(value) * 100, 2)}%`;
}

function escapeCell(value) {
    return String(value).replaceAll('|', '\\|').replaceAll('\n', ' ');
}

export function buildSummary(data, config) {
    const requestCount = metricValue(data, config.requestsMetric, 'count');
    const requestRate = metricValue(data, config.requestsMetric, 'rate');
    const errorRate = metricValue(data, config.errorsMetric, 'rate');
    const checksRate = metricValue(data, config.checksMetric, 'rate');
    const maxVus = metricValue(data, 'vus_max', 'max');
    const generatedAt = new Date().toISOString();

    const rows = [
        ['Load requests', number(requestCount, 0), config.expectedRequests],
        ['Throughput', `${number(requestRate)} req/s`, 'Record result'],
        ['HTTP error rate', percentage(errorRate), '< 1%'],
        ['Checks passed', percentage(checksRate), '100%'],
        ['Maximum VUs', number(maxVus, 0), config.expectedVus],
    ];

    for (const duration of config.durations) {
        rows.push([
            `${duration.label} p95`,
            `${number(metricValue(data, duration.metric, 'p(95)'))} ms`,
            duration.acceptance,
        ]);
    }

    for (const row of config.extraRows || []) {
        rows.push(row);
    }

    const markdownRows = rows
        .map((row) => `| ${row.map(escapeCell).join(' | ')} |`)
        .join('\n');

    const markdown = [
        `# ${config.title}`,
        '',
        `- Generated: ${generatedAt}`,
        `- Target: ${config.target}`,
        `- k6 summary JSON: \`${config.jsonPath}\``,
        '',
        '| Metric | Result | Acceptance |',
        '|---|---:|---:|',
        markdownRows,
        '',
    ].join('\n');

    const stdout = [
        '',
        `${config.title} (${config.target})`,
        `requests=${number(requestCount, 0)}, rps=${number(requestRate)}, errors=${percentage(errorRate)}, checks=${percentage(checksRate)}`,
        ...config.durations.map((duration) =>
            `${duration.label}_p95=${number(metricValue(data, duration.metric, 'p(95)'))}ms`
        ),
        `raw summary: ${config.jsonPath}`,
        `markdown summary: ${config.markdownPath}`,
        '',
    ].join('\n');

    return {
        stdout,
        [config.jsonPath]: JSON.stringify(data, null, 2),
        [config.markdownPath]: markdown,
    };
}
