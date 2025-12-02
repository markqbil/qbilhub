<template>
  <div class="inbox-table-container">
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
          :class="{ 'unread': !doc.isRead }"
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
      filter: 'my'
    };
  },
  mounted() {
    this.loadDocuments();
    this.setupMercureListener();
    this.setupFilterListener();
  },
  methods: {
    async loadDocuments() {
      this.loading = true;
      try {
        const response = await axios.get(`/hub/inbox/documents?filter=${this.filter}`);
        this.documents = response.data.documents;
      } catch (error) {
        console.error('Failed to load documents:', error);
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
    setupMercureListener() {
      window.addEventListener('mercure-update', (event) => {
        if (event.detail.type === 'document_ready') {
          this.loadDocuments();
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
    formatStatus(status) {
      const statusMap = {
        'new': 'New',
        'extracting_schema': 'Extracting',
        'resolving_entities': 'Resolving',
        'mapping': 'Ready for Mapping',
        'processed': 'Processed',
        'error': 'Error'
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
</style>
