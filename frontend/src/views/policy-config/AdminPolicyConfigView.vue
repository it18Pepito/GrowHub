<template>
  <div class="page admin-policy-config-page">
    <!-- Header -->
    <div class="page-header">
      <div>
        <div class="header-badge mb-1">
          <ShieldAlert :size="14" />
          <span>Administration Console</span>
        </div>
        <h1 class="page-title">Policy and Validation Config</h1>
        <p class="page-subtitle">
          Kelola daftar pelanggaran etika komunikasi yang tampil pada pop-up modal login serta tentukan besaran punishment pengurangan poin yang fleksibel sesuai jenis dan tingkat keparahan pelanggaran.
        </p>
      </div>

      <div class="header-actions">
        <button
          type="button"
          class="btn btn-outline"
          @click="isPreviewOpen = true"
          :disabled="loading"
        >
          <Eye :size="16" />
          <span>Pratinjau Pop-up</span>
        </button>

        <button
          type="button"
          class="btn btn-outline"
          @click="openCreateModal"
          :disabled="loading || saving"
        >
          <Plus :size="16" />
          <span>Tambah Pelanggaran</span>
        </button>

        <button
          type="button"
          class="btn btn-primary"
          @click="saveAllPolicies"
          :disabled="loading || saving || modifiedCount === 0"
        >
          <Check :size="16" />
          <span>{{ saving ? 'Menyimpan...' : `Simpan Perubahan (${modifiedCount})` }}</span>
        </button>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon-wrapper bg-green-soft">
          <FileWarning :size="20" class="text-primary" />
        </div>
        <div class="stat-meta">
          <span class="stat-label">Total Aturan Pelanggaran</span>
          <span v-if="loading" class="skeleton sk-stat-val"></span>
          <span v-else class="stat-val">{{ stats.total_policies || 0 }} Item</span>
        </div>
      </div>


    </div>

    <!-- Policy List Editor -->
    <div class="card policy-editor-card">
      <div class="policy-card-header">
        <div>
          <h2 class="card-title">Daftar Pelanggaran &amp; Konfigurasi Punishment Poin</h2>
          <p class="small muted">
            Sesuaikan judul pelanggaran, tingkat keparahan, punishment pengurangan poin, atau urutan di bawah ini lalu klik <strong>Simpan Perubahan</strong>.
          </p>
        </div>
        <span v-if="modifiedCount > 0" class="badge badge-amber">
          {{ modifiedCount }} Perubahan Belum Disimpan
        </span>
      </div>

      <div v-if="loading" class="policy-loading" aria-label="Loading communication policies">
        <div v-for="n in 3" :key="`sk-pol-${n}`" class="policy-row-card">
          <div class="policy-row-top">
            <div class="skeleton" style="height: 40px;"></div>
            <div class="skeleton" style="height: 40px;"></div>
            <div class="skeleton" style="height: 40px;"></div>
            <div class="skeleton" style="height: 40px;"></div>
            <div class="skeleton sk-btn" style="width: 40px; height: 40px;"></div>
          </div>
          <div class="skeleton" style="height: 62px;"></div>
        </div>
      </div>

      <div v-else-if="policies.length === 0" class="policy-empty">
        <ShieldAlert :size="32" class="muted" />
        <h3>Belum ada aturan pelanggaran</h3>
        <p class="small muted">Klik Tambah Pelanggaran untuk menambahkan daftar etika baru.</p>
      </div>

      <div v-else class="policy-items-list">
        <div
          v-for="item in policies"
          :key="item.id"
          class="policy-row-card"
          :class="{
            'is-modified': hasChanged(item),
          }"
        >
          <div class="policy-row-top">
            <!-- Order -->
            <div class="policy-order-group">
              <label class="small-label">Urutan</label>
              <input
                v-model.number="item.sort_order"
                type="number"
                min="0"
                max="1000"
                class="input input-order"
              />
            </div>

            <!-- Title -->
            <div class="policy-title-group">
              <label class="small-label">Judul / Jenis Pelanggaran</label>
              <input
                v-model="item.title"
                type="text"
                maxlength="160"
                class="input"
                placeholder="Nama pelanggaran..."
              />
            </div>

            <!-- Severity -->
            <div class="policy-severity-group">
              <label class="small-label">Tingkat</label>
              <select v-model="item.severity" class="input select-severity">
                <option value="low">Low (Ringan)</option>
                <option value="medium">Medium (Sedang)</option>
                <option value="high">High (Berat)</option>
              </select>
            </div>

            <!-- Point Punishment Deduction -->
            <div class="policy-deduction-group">
              <label class="small-label">Punishment Poin</label>
              <div class="deduction-input-wrap">
                <span class="deduction-prefix">-</span>
                <input
                  v-model.number="item.point_deduction"
                  type="number"
                  min="1"
                  max="1000"
                  class="input input-deduction"
                  placeholder="1"
                />
                <span class="deduction-suffix">Pts</span>
              </div>
            </div>

            <!-- Delete Action -->
            <div class="policy-actions-group">
              <button
                type="button"
                class="action-btn delete-btn"
                :disabled="policies.length <= 1"
                @click="confirmDelete(item)"
                title="Hapus aturan"
              >
                <Trash2 :size="15" />
              </button>
            </div>
          </div>

          <div class="policy-row-desc">
            <label class="small-label">Penjelasan Pelanggaran</label>
            <textarea
              v-model="item.description"
              rows="2"
              maxlength="1000"
              class="textarea"
              placeholder="Jelaskan tindakan yang tidak diperbolehkan di GrowHub..."
            ></textarea>
          </div>
        </div>
      </div>
    </div>

    <!-- Create Modal -->
    <div v-if="createModalOpen" class="modal-backdrop" @click.self="createModalOpen = false">
      <div class="modal">
        <div class="modal-head">
          <div class="card-title">
            <Plus :size="18" class="text-primary" />
            <span>Tambah Daftar Pelanggaran Baru</span>
          </div>
          <button type="button" class="btn btn-ghost btn-icon btn-sm" @click="createModalOpen = false">
            <X :size="18" />
          </button>
        </div>

        <form @submit.prevent="submitCreate">
          <div class="modal-body stack">
            <div class="field">
              <label class="label">Judul / Jenis Pelanggaran <span class="text-red">*</span></label>
              <input
                v-model="createForm.title"
                type="text"
                class="input"
                maxlength="160"
                placeholder="Contoh: Penyalahgunaan Akun Rekan Kerja"
                required
              />
            </div>

            <div class="grid-2-col">
              <div class="field">
                <label class="label">Tingkat Keparahan</label>
                <select v-model="createForm.severity" class="input">
                  <option value="low">Low (Ringan)</option>
                  <option value="medium">Medium (Sedang)</option>
                  <option value="high">High (Berat)</option>
                </select>
              </div>

              <div class="field">
                <label class="label">Punishment Poin (Pengurangan) <span class="text-red">*</span></label>
                <div class="deduction-input-wrap">
                  <span class="deduction-prefix">-</span>
                  <input
                    v-model.number="createForm.point_deduction"
                    type="number"
                    min="1"
                    max="1000"
                    class="input input-deduction"
                    placeholder="Contoh: 3"
                    required
                  />
                  <span class="deduction-suffix">Pts</span>
                </div>
              </div>
            </div>

            <div class="field">
              <label class="label">Penjelasan Pelanggaran <span class="text-red">*</span></label>
              <textarea
                v-model="createForm.description"
                rows="3"
                class="textarea"
                maxlength="1000"
                placeholder="Jelaskan detail tindakan yang dilarang beserta dampaknya..."
                required
              ></textarea>
            </div>
          </div>

          <div class="modal-foot">
            <button type="button" class="btn btn-outline" @click="createModalOpen = false">Batal</button>
            <button type="submit" class="btn btn-primary" :disabled="saving">
              <span v-if="saving" class="spinner"></span>
              <span v-else>Simpan Aturan</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div v-if="deleteTarget" class="modal-backdrop" @click.self="deleteTarget = null">
      <div class="modal" style="max-width: 440px;">
        <div class="modal-head">
          <div class="card-title">
            <Trash2 :size="18" class="text-red" />
            <span>Hapus Aturan Pelanggaran</span>
          </div>
          <button type="button" class="btn btn-ghost btn-icon btn-sm" @click="deleteTarget = null">
            <X :size="18" />
          </button>
        </div>
        <div class="modal-body">
          <p class="small text-2">
            Yakin ingin menghapus aturan <strong>"{{ deleteTarget.title }}"</strong> dari daftar pemberitahuan?
          </p>
        </div>
        <div class="modal-foot">
          <button type="button" class="btn btn-outline" @click="deleteTarget = null">Batal</button>
          <button type="button" class="btn btn-danger" :disabled="saving" @click="executeDelete">
            Hapus Aturan
          </button>
        </div>
      </div>
    </div>

    <!-- Live Preview of Login Popup Modal -->
    <EthicsNoticeModal
      :is-open="isPreviewOpen"
      :preview-policies="previewActivePolicies"
      @acknowledge="isPreviewOpen = false"
    />
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import {
  ShieldAlert, Eye, Plus, Check, FileWarning,
  TrendingDown, Trash2, X
} from 'lucide-vue-next'
import api from '@/api/client'
import { getApiErrorMessage } from '@/utils/httpError'
import { useToastStore } from '@/stores/toast'
import EthicsNoticeModal from '@/components/common/EthicsNoticeModal.vue'

const toastStore = useToastStore()

const loading = ref(true)
const saving = ref(false)
const policies = ref([])
const originalSnapshots = ref({})
const stats = ref({})

const createModalOpen = ref(false)
const isPreviewOpen = ref(false)
const deleteTarget = ref(null)

const createForm = reactive({
  title: '',
  description: '',
  point_deduction: 2,
  severity: 'medium',
})

const snapshotMap = (list) => {
  const map = {}
  for (const p of list) {
    map[p.id] = JSON.stringify({
      title: p.title,
      description: p.description,
      sort_order: Number(p.sort_order),
      point_deduction: Number(p.point_deduction || 1),
      severity: p.severity || 'medium',
    })
  }
  return map
}

const applyPayload = (data) => {
  policies.value = (data.policies || []).map((p) => ({
    ...p,
    point_deduction: Number(p.point_deduction || 1),
    severity: p.severity || 'medium',
  }))
  originalSnapshots.value = snapshotMap(policies.value)
  stats.value = data.stats || {}
}

const hasChanged = (item) => {
  const curr = JSON.stringify({
    title: item.title,
    description: item.description,
    sort_order: Number(item.sort_order),
    point_deduction: Number(item.point_deduction || 1),
    severity: item.severity || 'medium',
  })
  return originalSnapshots.value[item.id] !== curr
}

const modifiedCount = computed(() => policies.value.filter(hasChanged).length)

const previewActivePolicies = computed(() => {
  return [...policies.value]
    .sort((a, b) => (Number(a.sort_order) || 0) - (Number(b.sort_order) || 0))
})

const fetchPolicies = async () => {
  loading.value = true
  try {
    const res = await api.get('/admin/policy-configs')
    if (res.data && typeof res.data === 'object') {
      applyPayload(res.data)
    }
  } catch (err) {
    toastStore.error(getApiErrorMessage(err, 'Gagal memuat konfigurasi Policy & Validation.'))
  } finally {
    loading.value = false
  }
}

const saveAllPolicies = async () => {
  for (const p of policies.value) {
    if (!p.title?.trim() || !p.description?.trim()) {
      toastStore.error('Judul dan penjelasan setiap aturan wajib diisi.')
      return
    }
    if (!p.point_deduction || Number(p.point_deduction) < 1) {
      toastStore.error('Punishment poin minimal adalah 1 Pts.')
      return
    }
  }

  saving.value = true
  try {
    const res = await api.put('/admin/policy-configs/batch', {
      policies: policies.value.map((p) => ({
        id: p.id,
        title: p.title.trim(),
        description: p.description.trim(),
        sort_order: Math.max(0, Number(p.sort_order) || 0),
        point_deduction: Math.max(1, Number(p.point_deduction) || 1),
        severity: p.severity || 'medium',
      })),
    })
    applyPayload(res.data)
    toastStore.success(res.data.message || 'Konfigurasi Policy & Sanksi Poin disimpan!')
  } catch (err) {
    toastStore.error(err.response?.data?.message || 'Gagal menyimpan perubahan.')
  } finally {
    saving.value = false
  }
}

const openCreateModal = () => {
  createForm.title = ''
  createForm.description = ''
  createForm.point_deduction = 2
  createForm.severity = 'medium'
  createModalOpen.value = true
}

const submitCreate = async () => {
  if (!createForm.title.trim() || !createForm.description.trim()) {
    toastStore.error('Judul dan penjelasan wajib diisi.')
    return
  }
  if (!createForm.point_deduction || Number(createForm.point_deduction) < 1) {
    toastStore.error('Punishment poin minimal adalah 1 Pts.')
    return
  }

  saving.value = true
  try {
    const res = await api.post('/admin/policy-configs', {
      title: createForm.title.trim(),
      description: createForm.description.trim(),
      point_deduction: Math.max(1, Number(createForm.point_deduction) || 1),
      severity: createForm.severity || 'medium',
    })
    applyPayload(res.data)
    createModalOpen.value = false
    toastStore.success(res.data.message || 'Aturan baru ditambahkan.')
  } catch (err) {
    toastStore.error(err.response?.data?.message || 'Gagal menambahkan aturan baru.')
  } finally {
    saving.value = false
  }
}

const confirmDelete = (item) => {
  deleteTarget.value = item
}

const executeDelete = async () => {
  if (!deleteTarget.value) return
  saving.value = true
  try {
    const res = await api.delete(`/admin/policy-configs/${deleteTarget.value.id}`)
    applyPayload(res.data)
    deleteTarget.value = null
    toastStore.success(res.data.message || 'Aturan dihapus.')
  } catch (err) {
    toastStore.error(err.response?.data?.message || 'Gagal menghapus aturan.')
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  fetchPolicies()
})
</script>

<style scoped src="@/styles/features/admin-policy-config.css"></style>

