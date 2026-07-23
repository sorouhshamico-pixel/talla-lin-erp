<section
    id="retention-audit-metrics-health"
    aria-labelledby="retention-audit-metrics-health-heading"
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

        const booleanLabel = (value) => value ? 'Yes' : 'No';

        const displayValue = (key, value) => {
            if ([
                'listener_discovered',
                'channel_configured',
                'channel_path_matches',
                'healthy',
            ].includes(key)) {
                return booleanLabel(Boolean(value));
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
        };

        const hasExpectedShape = (payload) => {
            if (
                payload === null
                || typeof payload !== 'object'
                || Array.isArray(payload)
            ) {
                return false;
            }

            return Object.keys(fields).every(
                (key) => Object.prototype.hasOwnProperty.call(
                    payload,
                    key
                )
            );
        };

        const loadHealth = async () => {
            if (requestInFlight) {
                return;
            }

            requestInFlight = true;
            refresh.disabled = true;
            status.textContent = 'Loading health status...';

            try {
                const response = await fetch(panel.dataset.url, {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                    },
                });

                if (!response.ok) {
                    throw new Error('Health request failed');
                }

                const payload = await response.json();

                if (!hasExpectedShape(payload)) {
                    throw new Error('Unexpected health payload');
                }

                setFields(payload);
                status.textContent = payload.healthy
                    ? 'Audit metrics pipeline is healthy.'
                    : 'Audit metrics pipeline requires attention.';
            } catch (error) {
                setUnavailable();
            } finally {
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
