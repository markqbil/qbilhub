<template>
  <div class="inbox-table-container">
    <!-- Notification Toast -->
    <div v-if="notification" :class="['notification-toast', notification.type]">
      <span class="notification-icon">{{ notification.icon }}</span>
      <div class="notification-content">
        <strong>{{ notification.title }}</strong>
        <p>{{ notification.message }}</p>
      </div>
      <button class="notification-close" @click="notification = null">&times;</button>
    </div>

    <div v-if="loading" class="loading">Loading documents...</div>

    <div v-else-if="documents.length === 0" class="empty-state">
      <p>No documents in your inbox</p>
    </div>

    <table v-else class="inbox-table">
      <thead>
        <tr>
          <th>Status</th>
          <th>Source</th>
          <th>Document Type</th>
          <th>Received</th>
          <th>Processed By</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr
          v-for="doc in documents"
          :key="doc.id"
          :class="{ 'unread': !doc.isRead, 'has-error': doc.status === 'error' }"
        >
          <td>
            <span :class="['status-badge', `status-${doc.status}`]">
              {{ formatStatus(doc.status) }}
            </span>
          </td>
          <td>
            <div class="source-cell">
              <img
                v-if="doc.sourceTenant.logoUrl"
                :src="doc.sourceTenant.logoUrl"
                :alt="doc.sourceTenant.name"
                class="tenant-logo"
              />
              <span>{{ doc.sourceTenant.name }}</span>
            </div>
          </td>
          <td>{{ doc.documentType }}</td>
          <td>{{ formatDate(doc.receivedAt) }}</td>
          <td>{{ doc.processedBy || '-' }}</td>
          <td>
            <div class="actions">
              <a
                v-if="doc.documentUrl"
                :href="doc.documentUrl"
                target="_blank"
                class="btn-icon"
                title="View Document"
              >
                📄
              </a>
              <a
                v-if="doc.status === 'mapping'"
                :href="`/hub/mapping/document/${doc.id}`"
                class="btn-primary"
                @click="markAsRead(doc.id)"
              >
                Process
              </a>
              <button
                v-if="doc.status === 'error'"
                class="btn-retry"
                @click="retryDocument(doc.id)"
              >
                Retry
              </button>
              <span
                v-if="doc.status === 'queued'"
                class="status-queued-info"
                title="Document is queued for processing"
              >
                ⏳ Queued
              </span>
            </div>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'InboxTable',
  props: {
    initialUnreadCount: {
      type: Number,
      default: 0
    }
  },
  data() {
    return {
      documents: [],
      loading: true,
      filter: 'my',
      notification: null,
      notificationTimeout: null
    };
  },
  mounted() {
    this.loadDocuments();
    this.setupMercureListener();
    this.setupFilterListener();
  },
  beforeUnmount() {
    if (this.notificationTimeout) {
      clearTimeout(this.notificationTimeout);
    }
  },
  methods: {
    async loadDocuments() {
      this.loading = true;
      try {
        const response = await axios.get(`/hub/inbox/documents?filter=${this.filter}`);
        this.documents = response.data.documents;
      } catch (error) {
        console.error('Failed to load documents:', error);
        this.showNotification('error', 'Error', 'Failed to load documents. Please refresh the page.');
      } finally {
        this.loading = false;
      }
    },
    async markAsRead(documentId) {
      try {
        await axios.post(`/hub/inbox/document/${documentId}/mark-read`);
      } catch (error) {
        console.error('Failed to mark document as read:', error);
      }
    },
    async retryDocument(documentId) {
      try {
        await axios.post(`/hub/inbox/document/${documentId}/retry`);
        this.showNotification('success', 'Retry Initiated', 'Document has been queued for reprocessing.');
        this.loadDocuments();
      } catch (error) {
        console.error('Failed to retry document:', error);
        this.showNotification('error', 'Retry Failed', 'Could not retry document. Please try again.');
      }
    },
    setupMercureListener() {
      window.addEventListener('mercure-update', (event) => {
        const data = event.detail;

        switch (data.type) {
          case 'document_ready':
            this.loadDocuments();
            this.showNotification('success', 'Document Ready', 'A document is ready for mapping.');
            break;

          case 'document_error':
            this.loadDocuments();
            this.showNotification('error', 'Processing Error', data.errorMessage || 'An error occurred while processing a document.');
            break;

          case 'service_unavailable':
            this.showNotification('warning', 'Service Temporarily Unavailable', data.message || 'Your document has been queued and will be processed when the service is restored.');
            this.loadDocuments();
            break;

          case 'processing_started':
            // Update UI to show processing state
            this.loadDocuments();
            break;

          case 'processing_delayed':
            this.showNotification('warning', 'Processing Delayed', data.message || 'Document processing has been delayed.');
            break;
        }
      });
    },
    setupFilterListener() {
      const filterDropdown = document.getElementById('inbox-filter');
      if (filterDropdown) {
        filterDropdown.addEventListener('change', (e) => {
          this.filter = e.target.value;
          this.loadDocuments();
        });
      }
    },
    showNotification(type, title, message) {
      const icons = {
        success: '✓',
        error: '✕',
        warning: '⚠',
        info: 'ℹ'
      };

      this.notification = {
        type,
        title,
        message,
        icon: icons[type] || 'ℹ'
      };

      // Auto-dismiss after 5 seconds
      if (this.notificationTimeout) {
        clearTimeout(this.notificationTimeout);
      }
      this.notificationTimeout = setTimeout(() => {
        this.notification = null;
      }, 5000);
    },
    formatStatus(status) {
      const statusMap = {
        'new': 'New',
        'extracting_schema': 'Extracting',
        'resolving_entities': 'Resolving',
        'mapping': 'Ready for Mapping',
        'processed': 'Processed',
        'error': 'Error',
        'queued': 'Queued'
      };
      return statusMap[status] || status;
    },
    formatDate(dateString) {
      const date = new Date(dateString);
      return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }
  }
};
</script>

<style scoped>
.inbox-table-container {
  padding: 20px;
  position: relative;
}

/* Notification Toast */
.notification-toast {
  position: fixed;
  top: 20px;
  right: 20px;
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 16px 20px;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  z-index: 1000;
  max-width: 400px;
  animation: slideIn 0.3s ease;
}

@keyframes slideIn {
  from {
    transform: translateX(100%);
    opacity: 0;
  }
  to {
    transform: translateX(0);
    opacity: 1;
  }
}

.notification-toast.success {
  background: #d4edda;
  border-left: 4px solid #28a745;
}

.notification-toast.error {
  background: #f8d7da;
  border-left: 4px solid #dc3545;
}

.notification-toast.warning {
  background: #fff3cd;
  border-left: 4px solid #ffc107;
}

.notification-toast.info {
  background: #d1ecf1;
  border-left: 4px solid #17a2b8;
}

.notification-icon {
  font-size: 20px;
  font-weight: bold;
}

.notification-toast.success .notification-icon { color: #28a745; }
.notification-toast.error .notification-icon { color: #dc3545; }
.notification-toast.warning .notification-icon { color: #856404; }
.notification-toast.info .notification-icon { color: #0c5460; }

.notification-content {
  flex: 1;
}

.notification-content strong {
  display: block;
  margin-bottom: 4px;
}

.notification-content p {
  margin: 0;
  font-size: 14px;
  opacity: 0.9;
}

.notification-close {
  background: none;
  border: none;
  font-size: 20px;
  cursor: pointer;
  opacity: 0.5;
  padding: 0;
  line-height: 1;
}

.notification-close:hover {
  opacity: 1;
}

.loading, .empty-state {
  text-align: center;
  padding: 40px;
  color: #666;
}

.inbox-table {
  width: 100%;
  border-collapse: collapse;
  background: white;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.inbox-table thead {
  background: #f8f9fa;
}

.inbox-table th,
.inbox-table td {
  padding: 12px 16px;
  text-align: left;
  border-bottom: 1px solid #e9ecef;
}

.inbox-table tbody tr.unread {
  background: #f0f7ff;
  font-weight: 600;
}

.inbox-table tbody tr.has-error {
  background: #fff5f5;
}

.inbox-table tbody tr:hover {
  background: #f8f9fa;
}

.status-badge {
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
}

.status-new {
  background: #ffeaa7;
  color: #d63031;
}

.status-mapping {
  background: #55efc4;
  color: #00b894;
}

.status-processed {
  background: #dfe6e9;
  color: #2d3436;
}

.status-error {
  background: #fab1a0;
  color: #d63031;
}

.status-queued {
  background: #ffeaa7;
  color: #6c5ce7;
}

.status-extracting_schema,
.status-resolving_entities {
  background: #81ecec;
  color: #00b894;
}

.source-cell {
  display: flex;
  align-items: center;
  gap: 8px;
}

.tenant-logo {
  width: 24px;
  height: 24px;
  border-radius: 4px;
  object-fit: contain;
}

.actions {
  display: flex;
  gap: 8px;
  align-items: center;
}

.btn-icon {
  padding: 4px 8px;
  text-decoration: none;
  cursor: pointer;
}

.btn-primary {
  padding: 6px 16px;
  background: #0984e3;
  color: white;
  text-decoration: none;
  border-radius: 4px;
  font-size: 14px;
  transition: background 0.2s;
}

.btn-primary:hover {
  background: #0770c9;
}

.btn-retry {
  padding: 6px 12px;
  background: #fdcb6e;
  color: #2d3436;
  border: none;
  border-radius: 4px;
  font-size: 13px;
  cursor: pointer;
  transition: background 0.2s;
}

.btn-retry:hover {
  background: #ffeaa7;
}

.status-queued-info {
  font-size: 13px;
  color: #6c5ce7;
}
</style>
