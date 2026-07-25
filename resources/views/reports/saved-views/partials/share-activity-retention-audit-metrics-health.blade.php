<section
    id="retention-audit-metrics-health"
    class="retention-audit-metrics-health-panel is-loading"
    aria-labelledby="retention-audit-metrics-health-heading"
    data-health-state="loading"
    data-url="{{ route(
        'reports.saved-view-share-activity-retention.'
        . 'summary-cache-diagnostics.audit-metrics-health'
    ) }}"
>
    <h3 id="retention-audit-metrics-health-heading">
        Audit Metrics Health
    </h3>

    <p
        id="retention-audit-metrics-health-status"
        role="status"
        aria-live="polite"
    >
        Loading health status...
    </p>

    <p
        id="retention-audit-metrics-health-indicator"
        class="retention-audit-metrics-health-indicator is-loading"
        aria-hidden="true"
    >
        Loading
    </p>

    <p>
        Last checked:
        <time
            id="retention-audit-metrics-health-updated-at"
            aria-live="off"
        >
            Not updated yet
        </time>
    </p>

    <p>
        Last request duration:
        <span
            id="retention-audit-metrics-health-request-duration"
            aria-live="off"
        >
            Not measured yet
        </span>
    </p>

    <p>
        Last response:
        <span
            id="retention-audit-metrics-health-response-status"
            aria-live="off"
        >
            Not received yet
        </span>
    </p>

    <button
        id="retention-audit-metrics-health-refresh"
        type="button"
    >
        Refresh health status
    </button>

    <table>
        <thead>
            <tr>
                <th scope="col">Health check</th>
                <th scope="col">Current value</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <th scope="row">Listener discovered</th>
                <td id="retention-health-listener-discovered">Loading...</td>
            </tr>
            <tr>
                <th scope="row">Listener count</th>
                <td id="retention-health-listener-count">Loading...</td>
            </tr>
            <tr>
                <th scope="row">Channel configured</th>
                <td id="retention-health-channel-configured">Loading...</td>
            </tr>
            <tr>
                <th scope="row">Channel driver</th>
                <td id="retention-health-channel-driver">Loading...</td>
            </tr>
            <tr>
                <th scope="row">Channel level</th>
                <td id="retention-health-channel-level">Loading...</td>
            </tr>
            <tr>
                <th scope="row">Channel retention days</th>
                <td id="retention-health-channel-retention-days">Loading...</td>
            </tr>
            <tr>
                <th scope="row">Channel path matches</th>
                <td id="retention-health-channel-path-matches">Loading...</td>
            </tr>
            <tr>
                <th scope="row">Overall health</th>
                <td id="retention-health-healthy">Loading...</td>
            </tr>
        </tbody>
    </table>

    <p>
        This read-only panel displays operational health only. It does not
        expose raw logs, cache keys, request context, or exception details.
    </p>
</section>

<script>
(() => {
    const initializeAuditMetricsHealth = () => {
        const panel = document.getElementById(
            'retention-audit-metrics-health'
        );

        if (!panel || panel.dataset.initialized === 'true') {
            return;
        }

        panel.dataset.initialized = 'true';

        const status = document.getElementById(
            'retention-audit-metrics-health-status'
        );
        const refresh = document.getElementById(
            'retention-audit-metrics-health-refresh'
        );
        const indicator = document.getElementById(
            'retention-audit-metrics-health-indicator'
        );
        const updatedAt = document.getElementById(
            'retention-audit-metrics-health-updated-at'
        );
        const requestDuration = document.getElementById(
            'retention-audit-metrics-health-request-duration'
        );
        const responseStatus = document.getElementById(
            'retention-audit-metrics-health-response-status'
        );

        const formatResponseStatus = (response) => {
            if (
                !response
                || !Number.isInteger(response.status)
                || response.status < 100
                || response.status > 599
            ) {
                return 'Not received yet';
            }

            const statusText = typeof response.statusText === 'string'
                ? response.statusText.trim()
                : '';

            return statusText === ''
                ? String(response.status)
                : `${response.status} ${statusText}`;
        };

        const millisecondFormatter = typeof Intl !== 'undefined'
            && typeof Intl.NumberFormat === 'function'
            ? new Intl.NumberFormat(undefined, {
                maximumFractionDigits: 0,
            })
            : null;

        const secondFormatter = typeof Intl !== 'undefined'
            && typeof Intl.NumberFormat === 'function'
            ? new Intl.NumberFormat(undefined, {
                maximumFractionDigits: 2,
            })
            : null;

        const formatRequestDuration = (durationMilliseconds) => {
            if (
                !Number.isFinite(durationMilliseconds)
                || durationMilliseconds < 0
            ) {
                return 'Not measured yet';
            }

            if (durationMilliseconds < 1000) {
                const milliseconds = millisecondFormatter
                    ? millisecondFormatter.format(durationMilliseconds)
                    : durationMilliseconds.toFixed(0);

                return `${milliseconds} ms`;
            }

            const seconds = durationMilliseconds / 1000;
            const formattedSeconds = secondFormatter
                ? secondFormatter.format(seconds)
                : seconds.toFixed(2);

            return `${formattedSeconds} s`;
        };

        const timestampFormatter = typeof Intl !== 'undefined'
            && typeof Intl.DateTimeFormat === 'function'
            ? new Intl.DateTimeFormat(undefined, {
                dateStyle: 'medium',
                timeStyle: 'medium',
            })
            : null;

        const updateTimestamp = () => {
            const completedAt = new Date();

            if (Number.isNaN(completedAt.getTime())) {
                updatedAt.textContent = 'Not updated yet';

                return;
            }

            updatedAt.dateTime = completedAt.toISOString();
            updatedAt.textContent = timestampFormatter
                ? timestampFormatter.format(completedAt)
                : completedAt.toLocaleString();
        };

        const stateClasses = [
            'is-loading',
            'is-healthy',
            'is-unhealthy',
            'is-unavailable',
        ];

        const indicatorLabels = {
            loading: 'Loading',
            healthy: 'Healthy',
            unhealthy: 'Requires attention',
            unavailable: 'Unavailable',
        };

        const applyVisualState = (state) => {
            const stateClass = `is-${state}`;

            panel.dataset.healthState = state;
            panel.classList.remove(...stateClasses);
            panel.classList.add(stateClass);

            indicator.classList.remove(...stateClasses);
            indicator.classList.add(stateClass);
            indicator.textContent = indicatorLabels[state];
        };

        const fields = {
            listener_discovered:
                'retention-health-listener-discovered',
            listener_count:
                'retention-health-listener-count',
            channel_configured:
                'retention-health-channel-configured',
            channel_driver:
                'retention-health-channel-driver',
            channel_level:
                'retention-health-channel-level',
            channel_retention_days:
                'retention-health-channel-retention-days',
            channel_path_matches:
                'retention-health-channel-path-matches',
            healthy:
                'retention-health-healthy',
        };

        let requestInFlight = false;

        const booleanFields = [
            'listener_discovered',
            'channel_configured',
            'channel_path_matches',
            'healthy',
        ];

        const isNonNegativeInteger = (value) => (
            Number.isInteger(value) && value >= 0
        );

        const isNullableString = (value) => (
            value === null || typeof value === 'string'
        );

        const isValidPayload = (payload) => {
            if (
                payload === null
                || typeof payload !== 'object'
                || Array.isArray(payload)
            ) {
                return false;
            }

            if (!Object.keys(fields).every(
                (key) => Object.prototype.hasOwnProperty.call(
                    payload,
                    key
                )
            )) {
                return false;
            }

            if (!booleanFields.every(
                (key) => typeof payload[key] === 'boolean'
            )) {
                return false;
            }

            if (!isNonNegativeInteger(payload.listener_count)) {
                return false;
            }

            if (
                payload.channel_retention_days !== null
                && !isNonNegativeInteger(
                    payload.channel_retention_days
                )
            ) {
                return false;
            }

            return (
                isNullableString(payload.channel_driver)
                && isNullableString(payload.channel_level)
            );
        };

        const displayValue = (key, value) => {
            if (booleanFields.includes(key)) {
                return value ? 'Yes' : 'No';
            }

            if (value === null || value === '') {
                return 'Not available';
            }

            return String(value);
        };

        const setFields = (payload) => {
            Object.entries(fields).forEach(([key, id]) => {
                const element = document.getElementById(id);

                if (element) {
                    element.textContent = displayValue(
                        key,
                        payload[key]
                    );
                }
            });
        };

        const setUnavailable = () => {
            Object.values(fields).forEach((id) => {
                const element = document.getElementById(id);

                if (element) {
                    element.textContent = 'Unavailable';
                }
            });

            status.textContent =
                'Audit metrics health status is unavailable.';
            applyVisualState('unavailable');
        };

        const loadHealth = async () => {
            if (requestInFlight) {
                return;
            }

            requestInFlight = true;
            refresh.disabled = true;
            status.textContent = 'Loading health status...';
            applyVisualState('loading');

            const requestStartedAt = performance['now']();
            let responseReceived = false;

            try {
                const response = await fetch(panel.dataset.url, {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                    },
                });

                responseReceived = true;
                responseStatus.textContent = formatResponseStatus(response);

                if (!response.ok) {
                    throw new Error('Health request failed');
                }

                const payload = await response.json();

                if (!isValidPayload(payload)) {
                    throw new Error('Unexpected health payload');
                }

                setFields(payload);
                status.textContent = payload.healthy
                    ? 'Audit metrics pipeline is healthy.'
                    : 'Audit metrics pipeline requires attention.';
                applyVisualState(
                    payload.healthy ? 'healthy' : 'unhealthy'
                );
            } catch (error) {
                if (!responseReceived) {
                    responseStatus.textContent = 'Network error';
                }

                setUnavailable();
            } finally {
                const requestCompletedAt = performance['now']();
                const durationMilliseconds = Math.max(
                    0,
                    requestCompletedAt - requestStartedAt
                );

                requestDuration.textContent = formatRequestDuration(
                    durationMilliseconds
                );
                updateTimestamp();
                requestInFlight = false;
                refresh.disabled = false;
            }
        };

        refresh.addEventListener('click', loadHealth);
        loadHealth();
    };

    if (document.readyState === 'loading') {
        document.addEventListener(
            'DOMContentLoaded',
            initializeAuditMetricsHealth,
            { once: true }
        );
    } else {
        initializeAuditMetricsHealth();
    }
})();
</script>
