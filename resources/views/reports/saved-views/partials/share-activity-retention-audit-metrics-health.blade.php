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

    <p>
        Consecutive failures:
        <span
            id="retention-audit-metrics-health-consecutive-failures"
            aria-live="off"
        >
            0
        </span>
    </p>

    <p>
        Last successful check:
        <time
            id="retention-audit-metrics-health-last-successful-check"
            aria-live="off"
        >
            No successful check yet
        </time>
    </p>

    <p>
        Successful check age:
        <span
            id="retention-audit-metrics-health-successful-check-age"
            aria-live="off"
        >
            Not available
        </span>
    </p>

    <p>
        Successful check freshness:
        <span
            id="retention-audit-metrics-health-successful-check-freshness"
            data-freshness-state="unavailable"
            aria-live="off"
        >
            Unavailable
        </span>
    </p>

    <p>
        Manual refresh attempts:
        <span
            id="retention-audit-metrics-health-manual-refresh-attempts"
            aria-live="off"
        >
            0
        </span>
    </p>

    <p>
        Manual refresh successes:
        <span
            id="retention-audit-metrics-health-manual-refresh-successes"
            aria-live="off"
        >
            0
        </span>
    </p>

    <p>
        Manual refresh failures:
        <span
            id="retention-audit-metrics-health-manual-refresh-failures"
            aria-live="off"
        >
            0
        </span>
    </p>

    <p>
        Manual refresh success rate:
        <span
            id="retention-audit-metrics-health-manual-refresh-success-rate"
            aria-live="off"
        >
            Not available
        </span>
    </p>

    <p>
        Last manual refresh outcome:
        <span
            id="retention-audit-metrics-health-manual-refresh-last-outcome"
            data-outcome-state="unavailable"
            aria-live="polite"
        >
            Not available
        </span>
    </p>

    <p>
        Last manual refresh outcome at:
        <time
            id="retention-audit-metrics-health-manual-refresh-last-outcome-at"
            aria-live="off"
        >
            Not available
        </time>
    </p>

    <p>
        Last manual refresh outcome age:
        <span
            id="retention-audit-metrics-health-manual-refresh-last-outcome-age"
            aria-live="off"
        >
            Not available
        </span>
    </p>

    <p>
        Last manual refresh outcome freshness:
        <span
            id="retention-audit-metrics-health-manual-refresh-last-outcome-freshness"
            data-freshness-state="unavailable"
            aria-live="off"
        >
            Unavailable
        </span>
    </p>

    <p
        id="retention-audit-metrics-health-manual-refresh-outcome-summary"
        data-summary-state="unavailable"
        aria-live="polite"
    >
        Manual refresh outcome summary:
        <span>Not available</span>
    </p>

    <button
        id="retention-audit-metrics-health-manual-refresh-outcome-summary-copy"
        type="button"
        aria-live="off"
        disabled
    >
        Copy summary
    </button>

    <span
        id="retention-audit-metrics-health-manual-refresh-outcome-summary-copy-status"
        aria-live="polite"
    ></span>

    <span
        id="retention-audit-metrics-health-manual-refresh-outcome-summary-copy-availability"
        data-copy-availability="unavailable"
        aria-live="polite"
    >
        Copy unavailable until a manual refresh completes.
    </span>

    <span
        id="retention-audit-metrics-health-manual-refresh-outcome-summary-copy-attempts"
        aria-live="polite"
    >
        Copy attempts: <span>0</span>
    </span>

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
        const consecutiveFailureCounter = document.getElementById(
            'retention-audit-metrics-health-consecutive-failures'
        );
        const lastSuccessfulCheck = document.getElementById(
            'retention-audit-metrics-health-last-successful-check'
        );
        const successfulCheckAge = document.getElementById(
            'retention-audit-metrics-health-successful-check-age'
        );
        const successfulCheckFreshness = document.getElementById(
            'retention-audit-metrics-health-successful-check-freshness'
        );
        const manualRefreshAttemptCounter = document.getElementById(
            'retention-audit-metrics-health-manual-refresh-attempts'
        );
        const manualRefreshSuccessCounter = document.getElementById(
            'retention-audit-metrics-health-manual-refresh-successes'
        );
        const manualRefreshFailureCounter = document.getElementById(
            'retention-audit-metrics-health-manual-refresh-failures'
        );
        const manualRefreshSuccessRate = document.getElementById(
            'retention-audit-metrics-health-manual-refresh-success-rate'
        );
        const manualRefreshLastOutcome = document.getElementById(
            'retention-audit-metrics-health-manual-refresh-last-outcome'
        );
        const manualRefreshLastOutcomeAt = document.getElementById(
            'retention-audit-metrics-health-manual-refresh-last-outcome-at'
        );
        const manualRefreshLastOutcomeAge = document.getElementById(
            'retention-audit-metrics-health-manual-refresh-last-outcome-age'
        );
        const manualRefreshLastOutcomeFreshness =
            document.getElementById(
                'retention-audit-metrics-health-manual-refresh-last-outcome-freshness'
            );
        const manualRefreshOutcomeSummary = document.getElementById(
            'retention-audit-metrics-health-manual-refresh-outcome-summary'
        );
        const manualRefreshOutcomeSummaryValue =
            manualRefreshOutcomeSummary.querySelector('span');
        const manualRefreshOutcomeSummaryCopy =
            document.getElementById(
                'retention-audit-metrics-health-manual-refresh-outcome-summary-copy'
            );
        const manualRefreshOutcomeSummaryCopyStatus =
            document.getElementById(
                'retention-audit-metrics-health-manual-refresh-outcome-summary-copy-status'
            );
        const manualRefreshOutcomeSummaryCopyAvailability =
            document.getElementById(
                'retention-audit-metrics-health-manual-refresh-outcome-summary-copy-availability'
            );
        const manualRefreshOutcomeSummaryCopyAttempts =
            document.getElementById(
                'retention-audit-metrics-health-manual-refresh-outcome-summary-copy-attempts'
            ).querySelector('span');

        let consecutiveFailures = 0;
        let lastSuccessfulCheckAt = null;
        let manualRefreshAttempts = 0;
        let manualRefreshSuccesses = 0;
        let manualRefreshFailures = 0;
        let manualRefreshOutcomeSummaryCopyAttemptCount = 0;
        let manualRefreshRequested = false;
        let lastManualRefreshOutcome = 'unavailable';
        let lastManualRefreshOutcomeAt = null;

        const manualRefreshOutcomeLabels = {
            unavailable: 'Not available',
            healthy: 'Healthy',
            unhealthy: 'Requires attention',
            failed: 'Failed',
        };

        const renderLastManualRefreshOutcome = () => {
            const safeState = Object.prototype.hasOwnProperty.call(
                manualRefreshOutcomeLabels,
                lastManualRefreshOutcome
            )
                ? lastManualRefreshOutcome
                : 'unavailable';

            lastManualRefreshOutcome = safeState;
            manualRefreshLastOutcome.dataset.outcomeState = safeState;
            manualRefreshLastOutcome.textContent =
                manualRefreshOutcomeLabels[safeState];
        };

        const manualRefreshOutcomeTimestampFormatter =
            typeof Intl !== 'undefined'
            && typeof Intl.DateTimeFormat === 'function'
                ? new Intl.DateTimeFormat(undefined, {
                    dateStyle: 'medium',
                    timeStyle: 'medium',
                })
                : null;

        const formatLastManualRefreshOutcomeTimestamp = (outcomeAt) => {
            if (
                !(outcomeAt instanceof Date)
                || Number.isNaN(outcomeAt.getTime())
            ) {
                return 'Not available';
            }

            if (manualRefreshOutcomeTimestampFormatter) {
                return manualRefreshOutcomeTimestampFormatter.format(
                    outcomeAt
                );
            }

            if (outcomeAt === lastManualRefreshOutcomeAt) {
                return lastManualRefreshOutcomeAt.toLocaleString();
            }

            return outcomeAt.toLocaleString();
        };

        const renderLastManualRefreshOutcomeTimestamp = () => {
            if (
                !(lastManualRefreshOutcomeAt instanceof Date)
                || Number.isNaN(lastManualRefreshOutcomeAt.getTime())
            ) {
                manualRefreshLastOutcomeAt.removeAttribute('datetime');
                manualRefreshLastOutcomeAt.textContent = 'Not available';
                return;
            }

            manualRefreshLastOutcomeAt.dateTime =
                lastManualRefreshOutcomeAt.toISOString();
            manualRefreshLastOutcomeAt.textContent =
                formatLastManualRefreshOutcomeTimestamp(
                    lastManualRefreshOutcomeAt
                );
        };

        const formatLastManualRefreshOutcomeAge = (
            outcomeAt,
            currentTime
        ) => {
            if (
                !(outcomeAt instanceof Date)
                || Number.isNaN(outcomeAt.getTime())
                || !(currentTime instanceof Date)
                || Number.isNaN(currentTime.getTime())
            ) {
                return 'Not available';
            }

            const ageMilliseconds = Math.max(
                0,
                currentTime.getTime() - outcomeAt.getTime()
            );
            const ageMinutes = Math.floor(ageMilliseconds / 60000);

            if (ageMinutes < 1) {
                return 'Less than 1 minute';
            }

            if (ageMinutes < 60) {
                return `${Math.min(ageMinutes, 999)} minutes`;
            }

            if (ageMinutes < 1440) {
                const ageHours = Math.floor(ageMinutes / 60);

                return `${Math.min(ageHours, 999)} hours`;
            }

            const ageDays = Math.floor(ageMinutes / 1440);

            return `${Math.min(ageDays, 999)} days`;
        };

        const renderLastManualRefreshOutcomeAge = (currentTime) => {
            manualRefreshLastOutcomeAge.textContent =
                formatLastManualRefreshOutcomeAge(
                    lastManualRefreshOutcomeAt,
                    currentTime
                );
        };

        const formatLastManualRefreshOutcomeFreshness = (
            outcomeAt,
            currentTime
        ) => {
            if (
                !(outcomeAt instanceof Date)
                || Number.isNaN(outcomeAt.getTime())
                || !(currentTime instanceof Date)
                || Number.isNaN(currentTime.getTime())
            ) {
                return {
                    state: 'unavailable',
                    text: 'Unavailable',
                };
            }

            const ageMinutes = Math.floor(
                Math.max(
                    0,
                    currentTime.getTime() - outcomeAt.getTime()
                ) / 60000
            );

            return ageMinutes <= 14
                ? {
                    state: 'fresh',
                    text: 'Fresh',
                }
                : {
                    state: 'stale',
                    text: 'Stale',
                };
        };

        const renderLastManualRefreshOutcomeFreshness = (
            currentTime
        ) => {
            const freshness =
                formatLastManualRefreshOutcomeFreshness(
                    lastManualRefreshOutcomeAt,
                    currentTime
                );

            manualRefreshLastOutcomeFreshness.dataset.freshnessState =
                freshness.state;
            manualRefreshLastOutcomeFreshness.textContent =
                freshness.text;
        };

        const formatManualRefreshOutcomeSummary = (
            outcome,
            outcomeAt,
            currentTime
        ) => {
            if (
                !Object.prototype.hasOwnProperty.call(
                    manualRefreshOutcomeLabels,
                    outcome
                )
                || outcome === 'unavailable'
                || !(outcomeAt instanceof Date)
                || Number.isNaN(outcomeAt.getTime())
            ) {
                return { state: 'unavailable', text: 'Not available' };
            }

            const timestamp =
                formatLastManualRefreshOutcomeTimestamp(outcomeAt);
            const age = formatLastManualRefreshOutcomeAge(
                outcomeAt,
                currentTime
            );
            const freshness =
                formatLastManualRefreshOutcomeFreshness(
                    outcomeAt,
                    currentTime
                );

            if (
                timestamp === 'Not available'
                || age === 'Not available'
                || freshness.state === 'unavailable'
            ) {
                return { state: 'unavailable', text: 'Not available' };
            }

            return {
                state: outcome,
                text: [
                    manualRefreshOutcomeLabels[outcome],
                    timestamp,
                    age,
                    freshness.text,
                ].join(' · '),
            };
        };

        const setManualRefreshOutcomeSummaryCopyStatus = (
            status
        ) => {
            manualRefreshOutcomeSummaryCopyStatus.textContent =
                status;
        };

        const resetManualRefreshOutcomeSummaryCopyStatus = () => {
            const availabilityState =
                manualRefreshOutcomeSummaryCopyAvailability
                    .dataset.copyAvailability;

            setManualRefreshOutcomeSummaryCopyStatus(
                availabilityState === 'available'
                    ? ''
                    : 'Summary unavailable'
            );
        };

        const renderManualRefreshOutcomeSummaryCopyAttempts = () => {
            const safeValue = Number.isInteger(
                manualRefreshOutcomeSummaryCopyAttemptCount
            ) && manualRefreshOutcomeSummaryCopyAttemptCount >= 0
                ? Math.min(
                    manualRefreshOutcomeSummaryCopyAttemptCount,
                    999
                )
                : 0;

            manualRefreshOutcomeSummaryCopyAttemptCount = safeValue;
            manualRefreshOutcomeSummaryCopyAttempts.textContent =
                String(safeValue);
        };

        const recordManualRefreshOutcomeSummaryCopyAttempt = () => {
            manualRefreshOutcomeSummaryCopyAttemptCount = Math.min(
                manualRefreshOutcomeSummaryCopyAttemptCount + 1,
                999
            );
            renderManualRefreshOutcomeSummaryCopyAttempts();
        };

        const formatManualRefreshOutcomeSummaryCopyAvailability = () => {
            const summaryState =
                manualRefreshOutcomeSummary.dataset.summaryState;
            const summaryText =
                manualRefreshOutcomeSummaryValue.textContent.trim();
            const summaryAvailable =
                summaryState !== 'unavailable'
                && summaryText !== ''
                && summaryText !== 'Not available';
            const clipboardSupported =
                window.isSecureContext
                && navigator.clipboard
                && typeof navigator.clipboard.writeText === 'function';

            if (!summaryAvailable) {
                return {
                    state: 'unavailable',
                    text: 'Copy unavailable until a manual refresh completes.',
                    disabled: true,
                };
            }

            if (!clipboardSupported) {
                return {
                    state: 'unsupported',
                    text: 'Clipboard access is unavailable in this browser context.',
                    disabled: true,
                };
            }

            return {
                state: 'available',
                text: 'Summary ready to copy.',
                disabled: false,
            };
        };

        const renderManualRefreshOutcomeSummaryCopyAvailabilityFeedback = () => {
            const availability =
                formatManualRefreshOutcomeSummaryCopyAvailability();

            manualRefreshOutcomeSummaryCopyAvailability.dataset.copyAvailability =
                availability.state;
            manualRefreshOutcomeSummaryCopyAvailability.textContent =
                availability.text;
            manualRefreshOutcomeSummaryCopy.disabled =
                availability.disabled;
        };

        const renderManualRefreshOutcomeSummaryCopyAvailability = () => {
            renderManualRefreshOutcomeSummaryCopyAvailabilityFeedback();
            resetManualRefreshOutcomeSummaryCopyStatus();
        };

        const renderManualRefreshOutcomeSummary = (currentTime) => {
            const summary = formatManualRefreshOutcomeSummary(
                lastManualRefreshOutcome,
                lastManualRefreshOutcomeAt,
                currentTime
            );

            manualRefreshOutcomeSummary.dataset.summaryState =
                summary.state;
            manualRefreshOutcomeSummaryValue.textContent = summary.text;
            renderManualRefreshOutcomeSummaryCopyAvailability();
        };

        const copyManualRefreshOutcomeSummary = async () => {
            resetManualRefreshOutcomeSummaryCopyStatus();

            const summaryState =
                manualRefreshOutcomeSummary.dataset.summaryState;
            const summaryText =
                manualRefreshOutcomeSummaryValue.textContent.trim();

            if (
                summaryState === 'unavailable'
                || summaryText === ''
                || summaryText === 'Not available'
            ) {
                setManualRefreshOutcomeSummaryCopyStatus(
                    'Summary unavailable'
                );
                return;
            }

            if (
                !window.isSecureContext
                || !navigator.clipboard
                || typeof navigator.clipboard.writeText !== 'function'
            ) {
                setManualRefreshOutcomeSummaryCopyStatus(
                    'Copy failed'
                );
                return;
            }

            recordManualRefreshOutcomeSummaryCopyAttempt();

            await navigator.clipboard.writeText(summaryText).then(
                () => {
                    setManualRefreshOutcomeSummaryCopyStatus('Copied');
                },
                () => {
                    setManualRefreshOutcomeSummaryCopyStatus(
                        'Copy failed'
                    );
                }
            );
        };

        const setLastManualRefreshOutcomeTimestamp = () => {
            const completedAt = new Date();

            lastManualRefreshOutcomeAt = completedAt;
            renderLastManualRefreshOutcomeTimestamp();
            renderLastManualRefreshOutcomeAge(completedAt);
            renderLastManualRefreshOutcomeFreshness(completedAt);
            renderManualRefreshOutcomeSummary(completedAt);
        };

        const setLastManualRefreshOutcome = (outcome) => {
            lastManualRefreshOutcome = outcome;
            renderLastManualRefreshOutcome();
            setLastManualRefreshOutcomeTimestamp();
        };

        const renderConsecutiveFailures = () => {
            const safeValue = Number.isInteger(consecutiveFailures)
                && consecutiveFailures >= 0
                ? Math.min(consecutiveFailures, 999)
                : 0;

            consecutiveFailures = safeValue;
            consecutiveFailureCounter.textContent = String(safeValue);
        };

        const renderManualRefreshAttempts = () => {
            const safeValue = Number.isInteger(manualRefreshAttempts)
                && manualRefreshAttempts >= 0
                ? Math.min(manualRefreshAttempts, 999)
                : 0;

            manualRefreshAttempts = safeValue;
            manualRefreshAttemptCounter.textContent = String(safeValue);
        };

        const recordManualRefreshAttempt = () => {
            manualRefreshAttempts = Math.min(
                manualRefreshAttempts + 1,
                999
            );
            renderManualRefreshAttempts();
            renderManualRefreshSuccessRate();
        };

        const renderManualRefreshSuccesses = () => {
            const safeValue = Number.isInteger(manualRefreshSuccesses)
                && manualRefreshSuccesses >= 0
                ? Math.min(manualRefreshSuccesses, 999)
                : 0;

            manualRefreshSuccesses = safeValue;
            manualRefreshSuccessCounter.textContent = String(safeValue);
        };

        const recordManualRefreshSuccess = () => {
            manualRefreshSuccesses = Math.min(
                manualRefreshSuccesses + 1,
                999
            );
            renderManualRefreshSuccesses();
            renderManualRefreshSuccessRate();
        };

        const renderManualRefreshFailures = () => {
            const safeValue = Number.isInteger(manualRefreshFailures)
                && manualRefreshFailures >= 0
                ? Math.min(manualRefreshFailures, 999)
                : 0;

            manualRefreshFailures = safeValue;
            manualRefreshFailureCounter.textContent = String(safeValue);
        };

        const manualRefreshRateFormatter =
            typeof Intl !== 'undefined'
            && typeof Intl.NumberFormat === 'function'
                ? new Intl.NumberFormat(undefined, {
                    maximumFractionDigits: 1,
                })
                : null;

        const renderManualRefreshSuccessRate = () => {
            if (
                !Number.isInteger(manualRefreshAttempts)
                || manualRefreshAttempts <= 0
                || !Number.isInteger(manualRefreshSuccesses)
                || manualRefreshSuccesses < 0
            ) {
                manualRefreshSuccessRate.textContent = 'Not available';
                return;
            }

            const percentage = Math.min(
                Math.max(
                    (manualRefreshSuccesses / manualRefreshAttempts) * 100,
                    0
                ),
                100
            );
            const formattedPercentage = manualRefreshRateFormatter
                ? manualRefreshRateFormatter.format(percentage)
                : percentage.toFixed(1).replace(/\.0$/, '');

            manualRefreshSuccessRate.textContent = `${formattedPercentage}%`;
        };

        const recordManualRefreshFailure = () => {
            manualRefreshFailures = Math.min(
                manualRefreshFailures + 1,
                999
            );
            renderManualRefreshFailures();
            renderManualRefreshSuccessRate();
        };

        const recordSuccessfulRequest = () => {
            consecutiveFailures = 0;
            renderConsecutiveFailures();
        };

        const recordFailedRequest = () => {
            consecutiveFailures = Math.min(
                consecutiveFailures + 1,
                999
            );
            renderConsecutiveFailures();
        };

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

        const formatSuccessfulCheckAge = (
            successfulCheckAt,
            currentTime
        ) => {
            if (
                !(successfulCheckAt instanceof Date)
                || Number.isNaN(successfulCheckAt.getTime())
                || !(currentTime instanceof Date)
                || Number.isNaN(currentTime.getTime())
            ) {
                return 'Not available';
            }

            const ageMilliseconds = Math.max(
                0,
                currentTime.getTime() - successfulCheckAt.getTime()
            );
            const ageMinutes = Math.floor(ageMilliseconds / 60000);

            if (ageMinutes < 1) {
                return 'Less than 1 minute';
            }

            if (ageMinutes < 60) {
                return `${Math.min(ageMinutes, 999)} minutes`;
            }

            if (ageMinutes < 1440) {
                const ageHours = Math.floor(ageMinutes / 60);

                return `${Math.min(ageHours, 999)} hours`;
            }

            const ageDays = Math.floor(ageMinutes / 1440);

            return `${Math.min(ageDays, 999)} days`;
        };

        const updateSuccessfulCheckAge = (currentTime) => {
            successfulCheckAge.textContent = formatSuccessfulCheckAge(
                lastSuccessfulCheckAt,
                currentTime
            );
        };

        const formatSuccessfulCheckFreshness = (
            successfulCheckAt,
            currentTime
        ) => {
            if (
                !(successfulCheckAt instanceof Date)
                || Number.isNaN(successfulCheckAt.getTime())
                || !(currentTime instanceof Date)
                || Number.isNaN(currentTime.getTime())
            ) {
                return {
                    state: 'unavailable',
                    text: 'Unavailable',
                };
            }

            const ageMinutes = Math.floor(
                Math.max(
                    0,
                    currentTime.getTime() - successfulCheckAt.getTime()
                ) / 60000
            );

            return ageMinutes <= 14
                ? {
                    state: 'fresh',
                    text: 'Fresh',
                }
                : {
                    state: 'stale',
                    text: 'Stale',
                };
        };

        const updateSuccessfulCheckFreshness = (currentTime) => {
            const freshness = formatSuccessfulCheckFreshness(
                lastSuccessfulCheckAt,
                currentTime
            );

            successfulCheckFreshness.dataset.freshnessState =
                freshness.state;
            successfulCheckFreshness.textContent = freshness.text;
        };

        const updateLastSuccessfulCheck = () => {
            const completedAt = new Date();

            if (Number.isNaN(completedAt.getTime())) {
                lastSuccessfulCheck.removeAttribute('datetime');
                lastSuccessfulCheck.textContent =
                    'No successful check yet';

                return;
            }

            lastSuccessfulCheckAt = completedAt;
            lastSuccessfulCheck.dateTime = completedAt.toISOString();
            lastSuccessfulCheck.textContent = timestampFormatter
                ? timestampFormatter.format(completedAt)
                : completedAt.toLocaleString();
            updateSuccessfulCheckAge(completedAt);
            updateSuccessfulCheckFreshness(completedAt);
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
            const isManualRefresh = manualRefreshRequested;

            manualRefreshRequested = false;

            if (requestInFlight) {
                return;
            }

            if (isManualRefresh) {
                recordManualRefreshAttempt();
            }

            requestInFlight = true;
            refresh.disabled = true;
            status.textContent = 'Loading health status...';
            applyVisualState('loading');

            const requestStartedAt = performance['now']();
            let responseReceived = false;
            let requestSucceeded = false;

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

                if (isManualRefresh) {
                    recordManualRefreshSuccess();
                }

                setFields(payload);
                status.textContent = payload.healthy
                    ? 'Audit metrics pipeline is healthy.'
                    : 'Audit metrics pipeline requires attention.';
                applyVisualState(
                    payload.healthy ? 'healthy' : 'unhealthy'
                );

                if (isManualRefresh) {
                    setLastManualRefreshOutcome(
                        payload.healthy ? 'healthy' : 'unhealthy'
                    );
                }

                if (payload.healthy) {
                    updateLastSuccessfulCheck();
                }

                requestSucceeded = true;
            } catch (error) {
                if (isManualRefresh) {
                    recordManualRefreshFailure();
                    setLastManualRefreshOutcome('failed');
                }

                if (!responseReceived) {
                    responseStatus.textContent = 'Network error';
                }

                setUnavailable();
            } finally {
                if (requestSucceeded) {
                    recordSuccessfulRequest();
                } else {
                    recordFailedRequest();
                }

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

        manualRefreshOutcomeSummaryCopy.addEventListener(
            'click',
            copyManualRefreshOutcomeSummary
        );
        refresh.addEventListener('click', () => {
            manualRefreshRequested = true;
        });
        refresh.addEventListener('click', loadHealth);
        renderManualRefreshOutcomeSummaryCopyAttempts();
        renderManualRefreshOutcomeSummaryCopyAvailability();
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
