<style data-testid="report-saved-view-list-styles">
    .saved-views-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .saved-view-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 10px 12px;
        border: 1px solid rgba(148, 163, 184, 0.35);
        border-radius: 8px;
    }

    .saved-view-row.active-saved-view-row {
        border-color: rgba(37, 99, 235, 0.55);
        background: rgba(37, 99, 235, 0.08);
    }

    .saved-view-link {
        font-weight: 600;
        text-decoration: none;
    }

    .saved-view-badges {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
    }

    .saved-view-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 24px;
        padding: 2px 8px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
        line-height: 1.5;
        white-space: nowrap;
    }

    .saved-view-badge-active {
        background: rgba(37, 99, 235, 0.12);
        color: #1d4ed8;
    }

    .saved-view-badge-default {
        background: rgba(22, 163, 74, 0.12);
        color: #15803d;
    }
</style>
