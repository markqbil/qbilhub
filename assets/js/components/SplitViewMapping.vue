<template>
  <div class="split-view-container">
    <div v-if="loading" class="loading">Loading document data...</div>

    <div v-else class="split-view">
      <!-- Left Panel: Source Document -->
      <div class="left-panel">
        <div class="panel-header">
          <h3>Source Document</h3>
        </div>
        <div class="panel-content">
          <!-- PDF Viewer or JSON Tree -->
          <div v-if="documentData.documentUrl" class="pdf-viewer">
            <iframe
              :src="documentData.documentUrl"
              width="100%"
              height="100%"
              frameborder="0"
            ></iframe>
          </div>
          <div v-else class="json-tree">
            <pre>{{ JSON.stringify(documentData.rawData, null, 2) }}</pre>
          </div>
        </div>
      </div>

      <!-- Right Panel: Target Contract Form -->
      <div class="right-panel">
        <div class="panel-header">
          <h3>Purchase Contract</h3>
        </div>
        <div class="panel-content">
          <form @submit.prevent="saveContract" class="contract-form">
            <div class="form-group">
              <label for="contractNumber">Contract Number *</label>
              <input
                id="contractNumber"
                v-model="formData.contractNumber"
                type="text"
                required
                :class="getFieldClass('contractNumber')"
                @input="handleFieldChange('contractNumber')"
              />
              <span v-if="getConfidence('contractNumber')" class="confidence-badge" :class="getConfidenceClass('contractNumber')">
                {{ getConfidence('contractNumber') }}% confidence
              </span>
            </div>

            <div class="form-group">
              <label for="supplier">Supplier *</label>
              <input
                id="supplier"
                v-model="formData.supplier"
                type="text"
                required
                :class="getFieldClass('supplier')"
                @input="handleFieldChange('supplier')"
              />
              <span v-if="getConfidence('supplier')" class="confidence-badge" :class="getConfidenceClass('supplier')">
                {{ getConfidence('supplier') }}% confidence
              </span>
            </div>

            <div class="form-group">
              <label for="product">Product *</label>
              <input
                id="product"
                v-model="formData.product"
                type="text"
                required
                :class="getFieldClass('product')"
                @input="handleFieldChange('product')"
              />
              <span v-if="getConfidence('product')" class="confidence-badge" :class="getConfidenceClass('product')">
                {{ getConfidence('product') }}% confidence
              </span>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label for="quantity">Quantity *</label>
                <input
                  id="quantity"
                  v-model="formData.quantity"
                  type="number"
                  step="0.01"
                  required
                  :class="getFieldClass('quantity')"
                  @input="handleFieldChange('quantity')"
                />
                <span v-if="getConfidence('quantity')" class="confidence-badge" :class="getConfidenceClass('quantity')">
                  {{ getConfidence('quantity') }}% confidence
                </span>
              </div>

              <div class="form-group">
                <label for="unit">Unit *</label>
                <input
                  id="unit"
                  v-model="formData.unit"
                  type="text"
                  required
                  :class="getFieldClass('unit')"
                  @input="handleFieldChange('unit')"
                />
                <span v-if="getConfidence('unit')" class="confidence-badge" :class="getConfidenceClass('unit')">
                  {{ getConfidence('unit') }}% confidence
                </span>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label for="pricePerUnit">Price per Unit *</label>
                <input
                  id="pricePerUnit"
                  v-model="formData.pricePerUnit"
                  type="number"
                  step="0.01"
                  required
                  :class="getFieldClass('pricePerUnit')"
                  @input="handleFieldChange('pricePerUnit')"
                />
                <span v-if="getConfidence('pricePerUnit')" class="confidence-badge" :class="getConfidenceClass('pricePerUnit')">
                  {{ getConfidence('pricePerUnit') }}% confidence
                </span>
              </div>

              <div class="form-group">
                <label for="currency">Currency *</label>
                <input
                  id="currency"
                  v-model="formData.currency"
                  type="text"
                  required
                  :class="getFieldClass('currency')"
                  @input="handleFieldChange('currency')"
                />
                <span v-if="getConfidence('currency')" class="confidence-badge" :class="getConfidenceClass('currency')">
                  {{ getConfidence('currency') }}% confidence
                </span>
              </div>
            </div>

            <div class="form-group">
              <label for="deliveryDate">Delivery Date *</label>
              <input
                id="deliveryDate"
                v-model="formData.deliveryDate"
                type="date"
                required
                :class="getFieldClass('deliveryDate')"
                @input="handleFieldChange('deliveryDate')"
              />
              <span v-if="getConfidence('deliveryDate')" class="confidence-badge" :class="getConfidenceClass('deliveryDate')">
                {{ getConfidence('deliveryDate') }}% confidence
              </span>
            </div>

            <div class="form-group">
              <label for="deliveryLocation">Delivery Location</label>
              <input
                id="deliveryLocation"
                v-model="formData.deliveryLocation"
                type="text"
                :class="getFieldClass('deliveryLocation')"
                @input="handleFieldChange('deliveryLocation')"
              />
              <span v-if="getConfidence('deliveryLocation')" class="confidence-badge" :class="getConfidenceClass('deliveryLocation')">
                {{ getConfidence('deliveryLocation') }}% confidence
              </span>
            </div>

            <div class="form-actions">
              <button type="submit" class="btn-primary" :disabled="saving">
                {{ saving ? 'Saving...' : 'Save Contract' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'SplitViewMapping',
  props: {
    documentId: {
      type: Number,
      required: true
    }
  },
  data() {
    return {
      loading: true,
      saving: false,
      documentData: null,
      formData: {
        contractNumber: '',
        supplier: '',
        product: '',
        quantity: '',
        unit: '',
        pricePerUnit: '',
        currency: '',
        deliveryDate: '',
        deliveryLocation: '',
        additionalTerms: null
      },
      originalMappedData: {},
      corrections: []
    };
  },
  mounted() {
    this.loadDocumentData();
  },
  methods: {
    async loadDocumentData() {
      this.loading = true;
      try {
        const response = await axios.get(`/hub/mapping/document/${this.documentId}/data`);
        this.documentData = response.data;

        // Pre-fill form with mapped data
        if (this.documentData.mappedData) {
          this.formData = { ...this.formData, ...this.documentData.mappedData };
          this.originalMappedData = { ...this.documentData.mappedData };
        }
      } catch (error) {
        console.error('Failed to load document data:', error);
        alert('Failed to load document data. Please try again.');
      } finally {
        this.loading = false;
      }
    },
    async saveContract() {
      this.saving = true;
      try {
        const response = await axios.post(`/hub/mapping/document/${this.documentId}/save`, {
          ...this.formData,
          corrections: this.corrections
        });

        if (response.data.success) {
          alert('Contract saved successfully!');
          window.location.href = '/hub/inbox';
        }
      } catch (error) {
        console.error('Failed to save contract:', error);
        alert('Failed to save contract. Please try again.');
      } finally {
        this.saving = false;
      }
    },
    handleFieldChange(fieldName) {
      // Track corrections for active learning
      if (this.originalMappedData[fieldName] &&
          this.originalMappedData[fieldName] !== this.formData[fieldName]) {

        // Remove previous correction for this field if exists
        this.corrections = this.corrections.filter(c => c.targetField !== fieldName);

        // Add new correction
        this.corrections.push({
          sourceField: this.getSourceFieldName(fieldName),
          sourceValue: this.getSourceValue(fieldName),
          targetField: fieldName,
          correctedValue: this.formData[fieldName]
        });
      }
    },
    getConfidence(fieldName) {
      if (this.documentData?.confidenceScores && this.documentData.confidenceScores[fieldName]) {
        return Math.round(this.documentData.confidenceScores[fieldName] * 100);
      }
      return null;
    },
    getConfidenceClass(fieldName) {
      const confidence = this.getConfidence(fieldName);
      if (!confidence) return '';
      return confidence >= 90 ? 'high-confidence' : 'low-confidence';
    },
    getFieldClass(fieldName) {
      const confidence = this.getConfidence(fieldName);
      if (!confidence) return '';
      return confidence >= 90 ? 'field-high-confidence' : 'field-low-confidence';
    },
    getSourceFieldName(fieldName) {
      // Map target field to source field (this should come from extractedSchema)
      if (this.documentData?.extractedSchema) {
        return this.documentData.extractedSchema[fieldName]?.sourceField || fieldName;
      }
      return fieldName;
    },
    getSourceValue(fieldName) {
      if (this.documentData?.rawData) {
        const sourceField = this.getSourceFieldName(fieldName);
        return this.documentData.rawData[sourceField] || '';
      }
      return '';
    }
  }
};
</script>

<style scoped>
.split-view-container {
  height: calc(100vh - 100px);
  padding: 20px;
}

.loading {
  text-align: center;
  padding: 40px;
  color: #666;
}

.split-view {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
  height: 100%;
}

.left-panel,
.right-panel {
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.panel-header {
  padding: 16px 20px;
  background: #f8f9fa;
  border-bottom: 1px solid #e9ecef;
}

.panel-header h3 {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
}

.panel-content {
  flex: 1;
  overflow: auto;
  padding: 20px;
}

.pdf-viewer iframe {
  min-height: 600px;
}

.json-tree pre {
  background: #f8f9fa;
  padding: 16px;
  border-radius: 4px;
  overflow: auto;
  font-size: 12px;
}

.contract-form {
  max-width: 600px;
}

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  font-weight: 500;
  color: #2d3436;
}

.form-group input {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #dfe6e9;
  border-radius: 4px;
  font-size: 14px;
  transition: border-color 0.2s;
}

.form-group input:focus {
  outline: none;
  border-color: #0984e3;
}

.field-high-confidence {
  border-color: #00b894;
  background: #f0fff4;
}

.field-low-confidence {
  border-color: #fdcb6e;
  background: #fffbf0;
}

.confidence-badge {
  display: inline-block;
  margin-top: 4px;
  font-size: 11px;
  padding: 2px 8px;
  border-radius: 4px;
  font-weight: 600;
}

.high-confidence {
  background: #55efc4;
  color: #00b894;
}

.low-confidence {
  background: #ffeaa7;
  color: #d63031;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}

.form-actions {
  margin-top: 32px;
  padding-top: 20px;
  border-top: 1px solid #e9ecef;
}

.btn-primary {
  padding: 12px 32px;
  background: #0984e3;
  color: white;
  border: none;
  border-radius: 4px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s;
}

.btn-primary:hover:not(:disabled) {
  background: #0770c9;
}

.btn-primary:disabled {
  background: #b2bec3;
  cursor: not-allowed;
}
</style>
