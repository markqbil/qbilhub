import { createApp } from 'vue';
import '../css/app.scss';

// Import Vue components
import InboxTable from './components/InboxTable.vue';
import SplitViewMapping from './components/SplitViewMapping.vue';

// Initialize Vue apps where needed
document.addEventListener('DOMContentLoaded', () => {
    // Initialize Inbox app
    const inboxApp = document.getElementById('inbox-app');
    if (inboxApp) {
        const app = createApp({});
        app.component('inbox-table', InboxTable);
        app.mount('#inbox-app');
    }

    // Initialize Mapping app
    const mappingApp = document.getElementById('mapping-app');
    if (mappingApp) {
        const app = createApp({});
        app.component('split-view-mapping', SplitViewMapping);
        app.mount('#mapping-app');
    }

    // Initialize real-time badge updates
    initializeInboxBadge();
});

function initializeInboxBadge() {
    const badge = document.getElementById('inbox-badge');
    if (!badge) return;

    // Fetch initial unread count
    fetch('/hub/inbox/unread-count')
        .then(response => response.json())
        .then(data => {
            updateBadge(data.unreadCount);
        });

    // Listen for Mercure updates
    window.addEventListener('mercure-update', (event) => {
        if (event.detail.type === 'document_ready') {
            // Refresh unread count
            fetch('/hub/inbox/unread-count')
                .then(response => response.json())
                .then(data => {
                    updateBadge(data.unreadCount);
                });
        }
    });
}

function updateBadge(count) {
    const badge = document.getElementById('inbox-badge');
    if (badge) {
        if (count > 0) {
            badge.textContent = count;
            badge.classList.remove('hidden');
        } else {
            badge.classList.add('hidden');
        }
    }
}
