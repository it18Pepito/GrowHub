<template>
  <div class="page admin-helpers-page">
    <!-- Header -->
    <div class="page-header">
      <div>
        <div class="header-badge mb-1">
          <Headphones :size="14" />
          <span>Administration Console</span>
        </div>
        <h1 class="page-title">Helper &amp; Support Contacts</h1>
        <p class="page-subtitle">
          Kelola kontak helper yang ditampilkan pada Halaman Login dan Halaman Help &amp; Support bagi karyawan yang memerlukan bantuan teknis, akun, atau layanan GrowHub.
        </p>
      </div>

      <div class="header-actions">
        <button
          type="button"
          class="btn btn-outline"
          @click="confirmReset"
          :disabled="loading || saving"
        >
          <RotateCcw :size="16" />
          <span>Reset Default</span>
        </button>

        <button
          type="button"
          class="btn btn-primary"
          @click="openCreateModal"
          :disabled="loading || saving"
        >
          <Plus :size="16" />
          <span>Tambah Kontak Helper</span>
        </button>
      </div>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon-wrapper bg-green-soft">
          <Headphones :size="20" class="text-primary" />
        </div>
        <div class="stat-meta">
          <span class="stat-label">Total Kontak Helper</span>
          <span v-if="loading" class="skeleton sk-stat-val"></span>
          <span v-else class="stat-val">{{ stats.total_helpers || 0 }} Kontak</span>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon-wrapper bg-blue-soft">
          <CheckCircle2 :size="20" class="text-blue" />
        </div>
        <div class="stat-meta">
          <span class="stat-label">Kontak Aktif</span>
          <span v-if="loading" class="skeleton sk-stat-val"></span>
          <span v-else class="stat-val text-primary">{{ stats.active_helpers || 0 }} Aktif</span>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon-wrapper bg-amber-soft">
          <Clock :size="20" class="text-amber" />
        </div>
        <div class="stat-meta">
          <span class="stat-label">Kontak Nonaktif</span>
          <span v-if="loading" class="skeleton sk-stat-val"></span>
          <span v-else class="stat-val text-amber">{{ stats.inactive_helpers || 0 }} Nonaktif</span>
        </div>
      </div>
    </div>

    <!-- Contacts List Card -->
    <div class="card card-pad">
      <div class="row-between mb-4">
        <div>
          <h2 class="card-title">Daftar Kontak Helper &amp; Support</h2>
          <p class="small text-2">Kontak aktif otomatis muncul di halaman login dan help &amp; support.</p>
        </div>
      </div>

      <div v-if="loading" class="helpers-admin-list stack" style="gap: 12px;">
        <div v-for="n in 3" :key="`sk-hlp-${n}`" class="skeleton" style="height: 100px; border-radius: 14px;"></div>
      </div>

      <div v-else-if="helpers.length === 0" class="empty py-6 text-center">
        <Headphones :size="36" class="text-3 mb-2" />
        <h3 class="bold text-dark">Belum ada kontak helper</h3>
        <p class="small text-2 mb-3">Tambahkan kontak PIC atau Helpdesk untuk membantu karyawan.</p>
        <button type="button" class="btn btn-primary btn-sm" @click="openCreateModal">
          <Plus :size="15" />
          <span>Tambah Kontak Pertama</span>
        </button>
      </div>

      <div v-else class="helpers-admin-list stack" style="gap: 14px;">
        <div
          v-for="c in helpers"
          :key="c.id"
          class="helper-admin-card"
          :class="{ 'is-inactive': !c.is_active }"
        >
          <div class="helper-admin-left">
            <div class="helper-channel-icon" :class="`channel-${c.channel}`">
              <MessageCircle v-if="c.channel === 'whatsapp'" :size="22" />
              <Mail v-else-if="c.channel === 'email'" :size="22" />
              <PhoneCall v-else-if="c.channel === 'phone'" :size="22" />
              <Send v-else-if="c.channel === 'telegram'" :size="22" />
              <Headphones v-else :size="22" />
            </div>

            <div class="helper-admin-info">
              <div class="helper-admin-name-row">
                <span class="helper-admin-name">{{ c.name }}</span>
                <span class="badge" :class="`badge-${c.channel}`">
                  {{ getChannelLabel(c.channel) }}
                </span>
                <span v-if="!c.is_active" class="badge badge-gray">Nonaktif</span>
              </div>

              <div class="helper-admin-meta">
                <span v-if="c.role_or_title" class="helper-meta-role">{{ c.role_or_title }}</span>
                <span v-if="c.available_hours" class="helper-meta-hours">
                  <Clock :size="12" />
                  <span>{{ c.available_hours }}</span>
                </span>
              </div>

              <div class="helper-admin-val">
                <strong class="text-dark">{{ c.contact_value }}</strong>
              </div>

              <p v-if="c.description" class="helper-admin-desc">{{ c.description }}</p>
            </div>
          </div>

          <div class="helper-admin-actions">
            <!-- Toggle Active -->
            <button
              type="button"
              class="btn btn-sm"
              :class="c.is_active ? 'btn-outline' : 'btn-primary'"
              @click="toggleActive(c)"
              :disabled="saving"
            >
              {{ c.is_active ? 'Nonaktifkan' : 'Aktifkan' }}
            </button>

            <!-- Edit -->
            <button
              type="button"
              class="action-btn edit-btn"
              @click="openEditModal(c)"
              :disabled="saving"
              title="Edit kontak"
            >
              <Pencil :size="15" />
            </button>

            <!-- Delete -->
            <button
              type="button"
              class="action-btn delete-btn"
              @click="confirmDelete(c)"
              :disabled="saving"
              title="Hapus kontak"
            >
              <Trash2 :size="15" />
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Create / Edit Modal -->
    <div v-if="modalOpen" class="modal-backdrop" @click.self="modalOpen = false">
      <div class="modal" style="max-width: 520px;">
        <div class="modal-head">
          <div class="card-title">
            <Headphones :size="18" class="text-primary" />
            <span>{{ isEditing ? 'Edit Kontak Helper' : 'Tambah Kontak Helper Baru' }}</span>
          </div>
          <button type="button" class="btn btn-ghost btn-icon btn-sm" @click="modalOpen = false">
            <X :size="18" />
          </button>
        </div>

        <form @submit.prevent="submitForm">
          <div class="modal-body stack" style="gap: 14px;">
            <div class="field">
              <label class="label">Nama Layanan / PIC <span class="text-red">*</span></label>
              <input
                v-model="form.name"
                type="text"
                class="input"
                maxlength="120"
                placeholder="Contoh: IT Helpdesk Support"
                required
              />
            </div>

            <div class="field">
              <label class="label">Peran / Jabatan / Tim</label>
              <input
                v-model="form.role_or_title"
                type="text"
                class="input"
                maxlength="120"
                placeholder="Contoh: Administrator Sistem GrowHub"
              />
            </div>

            <div class="grid-2-col">
              <div class="field">
                <label class="label">Saluran Komunikasi <span class="text-red">*</span></label>
                <select v-model="form.channel" class="input" required>
                  <option value="whatsapp">WhatsApp</option>
                  <option value="email">Email Support</option>
                  <option value="phone">Hotline Telepon</option>
                  <option value="telegram">Telegram</option>
                  <option value="other">Lainnya</option>
                </select>
              </div>

              <div class="field">
                <label class="label">Urutan Tampilan</label>
                <input
                  v-model.number="form.sort_order"
                  type="number"
                  min="0"
                  max="1000"
                  class="input"
                  placeholder="1"
                />
              </div>
            </div>

            <div class="field">
              <label class="label">Nilai Kontak (Nomor / Email / Link) <span class="text-red">*</span></label>
              <input
                v-model="form.contact_value"
                type="text"
                class="input"
                maxlength="160"
                :placeholder="getContactPlaceholder(form.channel)"
                required
              />
              <span class="hint">
                {{ getContactHint(form.channel) }}
              </span>
            </div>

            <div class="field">
              <label class="label">Jam Operasional Layanan</label>
              <input
                v-model="form.available_hours"
                type="text"
                class="input"
                maxlength="120"
                placeholder="Contoh: Senin - Jumat, 08:00 - 17:00 WIB"
              />
            </div>

            <div class="field">
              <label class="label">Deskripsi Layanan / Bantuan</label>
              <textarea
                v-model="form.description"
                rows="2"
                class="textarea"
                maxlength="1000"
                placeholder="Jelaskan jenis kendala yang dapat dibantu oleh helper ini..."
              ></textarea>
            </div>

            <div class="checkbox-field">
              <label class="checkbox-label">
                <input type="checkbox" v-model="form.is_active" />
                <span class="bold small">Tampilkan dan aktifkan kontak helper ini</span>
              </label>
            </div>
          </div>

          <div class="modal-foot">
            <button type="button" class="btn btn-outline" @click="modalOpen = false">Batal</button>
            <button type="submit" class="btn btn-primary" :disabled="saving">
              <span v-if="saving" class="spinner"></span>
              <span v-else>{{ isEditing ? 'Simpan Perubahan' : 'Tambah Kontak' }}</span>
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
            <span>Hapus Kontak Helper</span>
          </div>
          <button type="button" class="btn btn-ghost btn-icon btn-sm" @click="deleteTarget = null">
            <X :size="18" />
          </button>
        </div>
        <div class="modal-body">
          <p class="small text-2">
            Yakin ingin menghapus kontak <strong>"{{ deleteTarget.name }}"</strong>? Kontak ini tidak akan muncul lagi di halaman login dan support.
          </p>
        </div>
        <div class="modal-foot">
          <button type="button" class="btn btn-outline" @click="deleteTarget = null">Batal</button>
          <button type="button" class="btn btn-danger" :disabled="saving" @click="executeDelete">
            Hapus Kontak
          </button>
        </div>
      </div>
    </div>

    <!-- Reset Defaults Confirmation Modal -->
    <div v-if="resetModalOpen" class="modal-backdrop" @click.self="resetModalOpen = false">
      <div class="modal" style="max-width: 440px;">
        <div class="modal-head">
          <div class="card-title">
            <RotateCcw :size="18" class="text-amber" />
            <span>Reset ke Kontak Default</span>
          </div>
          <button type="button" class="btn btn-ghost btn-icon btn-sm" @click="resetModalOpen = false">
            <X :size="18" />
          </button>
        </div>
        <div class="modal-body">
          <p class="small text-2">
            Tindakan ini akan mengembalikan daftar kontak helper ke 3 kontak bawaan sistem GrowHub (IT Helpdesk WhatsApp, Human Capital Email, dan Hotline Telepon). Lanjutkan?
          </p>
        </div>
        <div class="modal-foot">
          <button type="button" class="btn btn-outline" @click="resetModalOpen = false">Batal</button>
          <button type="button" class="btn btn-primary" :disabled="saving" @click="executeReset">
            Ya, Reset Default
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import {
  Headphones, Plus, RotateCcw, CheckCircle2, Clock,
  MessageCircle, Mail, PhoneCall, Send, Pencil, Trash2, X
} from 'lucide-vue-next'
import api from '@/api/client'
import { getApiErrorMessage } from '@/utils/httpError'
import { useToastStore } from '@/stores/toast'

const toastStore = useToastStore()

const loading = ref(true)
const saving = ref(false)
const helpers = ref([])
const stats = ref({})

const modalOpen = ref(false)
const isEditing = ref(false)
const editId = ref(null)
const deleteTarget = ref(null)
const resetModalOpen = ref(false)

const form = reactive({
  name: '',
  role_or_title: '',
  channel: 'whatsapp',
  contact_value: '',
  available_hours: 'Senin - Jumat, 08:00 - 17:00 WIB',
  description: '',
  is_active: true,
  sort_order: 1,
})

const getChannelLabel = (channel) => {
  switch (channel) {
    case 'whatsapp': return 'WhatsApp'
    case 'email': return 'Email'
    case 'phone': return 'Telepon'
    case 'telegram': return 'Telegram'
    default: return 'Lainnya'
  }
}

const getContactPlaceholder = (channel) => {
  switch (channel) {
    case 'whatsapp': return 'Contoh: +62 812-8899-7700'
    case 'email': return 'Contoh: it-support@rcoid.com'
    case 'phone': return 'Contoh: (021) 5566-7788'
    case 'telegram': return 'Contoh: @growhub_support'
    default: return 'Nomor / Alamat / Link kontak'
  }
}

const getContactHint = (channel) => {
  switch (channel) {
    case 'whatsapp': return 'Untuk WhatsApp, nomor akan otomatis disambungkan ke link wa.me'
    case 'email': return 'Alamat email yang dapat menerima pertanyaan login/akun'
    case 'phone': return 'Nomor telepon kantor / hotline yang dapat dihubungi'
    case 'telegram': return 'Username akun atau bot Telegram support'
    default: return 'Informasi kontak yang jelas untuk karyawan'
  }
}

const applyPayload = (data) => {
  if (!data || typeof data !== 'object') return
  helpers.value = Array.isArray(data.helpers) ? data.helpers : []
  stats.value = (data.stats && typeof data.stats === 'object') ? data.stats : {}
}

const fetchHelpers = async () => {
  loading.value = true
  try {
    const res = await api.get('/admin/helper-contacts')
    if (res.data && typeof res.data === 'object') {
      applyPayload(res.data)
    }
  } catch (err) {
    toastStore.error(getApiErrorMessage(err, 'Gagal memuat daftar kontak helper.'))
  } finally {
    loading.value = false
  }
}

const openCreateModal = () => {
  isEditing.value = false
  editId.value = null
  form.name = ''
  form.role_or_title = ''
  form.channel = 'whatsapp'
  form.contact_value = ''
  form.available_hours = 'Senin - Jumat, 08:00 - 17:00 WIB'
  form.description = ''
  form.is_active = true
  form.sort_order = helpers.value.length + 1
  modalOpen.value = true
}

const openEditModal = (c) => {
  isEditing.value = true
  editId.value = c.id
  form.name = c.name
  form.role_or_title = c.role_or_title || ''
  form.channel = c.channel
  form.contact_value = c.contact_value
  form.available_hours = c.available_hours || ''
  form.description = c.description || ''
  form.is_active = Boolean(c.is_active)
  form.sort_order = c.sort_order || 0
  modalOpen.value = true
}

const submitForm = async () => {
  if (!form.name.trim() || !form.contact_value.trim()) {
    toastStore.error('Nama PIC/layanan dan nilai kontak wajib diisi.')
    return
  }

  saving.value = true
  try {
    const payload = {
      name: form.name.trim(),
      role_or_title: form.role_or_title.trim() || null,
      channel: form.channel,
      contact_value: form.contact_value.trim(),
      available_hours: form.available_hours.trim() || null,
      description: form.description.trim() || null,
      is_active: form.is_active,
      sort_order: Number(form.sort_order) || 0,
    }

    if (isEditing.value) {
      const res = await api.put(`/admin/helper-contacts/${editId.value}`, payload)
      if (res.data && typeof res.data === 'object') applyPayload(res.data)
      toastStore.success(res.data?.message || 'Kontak berhasil diperbarui.')
    } else {
      const res = await api.post('/admin/helper-contacts', payload)
      if (res.data && typeof res.data === 'object') applyPayload(res.data)
      toastStore.success(res.data?.message || 'Kontak baru ditambahkan.')
    }
    modalOpen.value = false
  } catch (err) {
    toastStore.error(getApiErrorMessage(err, 'Gagal menyimpan kontak helper.'))
  } finally {
    saving.value = false
  }
}

const toggleActive = async (c) => {
  saving.value = true
  try {
    const res = await api.post(`/admin/helper-contacts/${c.id}/toggle`)
    if (res.data && typeof res.data === 'object') applyPayload(res.data)
    toastStore.success(res.data?.message || 'Status kontak diperbarui.')
  } catch (err) {
    toastStore.error(getApiErrorMessage(err, 'Gagal mengubah status kontak.'))
  } finally {
    saving.value = false
  }
}

const confirmDelete = (c) => {
  deleteTarget.value = c
}

const executeDelete = async () => {
  if (!deleteTarget.value) return
  saving.value = true
  try {
    const res = await api.delete(`/admin/helper-contacts/${deleteTarget.value.id}`)
    if (res.data && typeof res.data === 'object') applyPayload(res.data)
    deleteTarget.value = null
    toastStore.success(res.data?.message || 'Kontak dihapus.')
  } catch (err) {
    toastStore.error(getApiErrorMessage(err, 'Gagal menghapus kontak.'))
  } finally {
    saving.value = false
  }
}

const confirmReset = () => {
  resetModalOpen.value = true
}

const executeReset = async () => {
  saving.value = true
  try {
    const res = await api.post('/admin/helper-contacts/reset-defaults')
    if (res.data && typeof res.data === 'object') applyPayload(res.data)
    resetModalOpen.value = false
    toastStore.success(res.data?.message || 'Kontak direset ke default.')
  } catch (err) {
    toastStore.error(getApiErrorMessage(err, 'Gagal mereset kontak helper.'))
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  fetchHelpers()
})
</script>

<style scoped src="@/styles/features/admin-helper-contacts.css"></style>
