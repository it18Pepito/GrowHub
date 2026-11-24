<template>
  <div class="page admin-mail-page">
    <!-- Header -->
    <div class="page-header">
      <div>
        <div class="header-badge mb-1">
          <Mail :size="14" />
          <span>Administration Console</span>
        </div>
        <h1 class="page-title">Mail &amp; SMTP Settings</h1>
        <p class="page-subtitle">
          Kelola konfigurasi protokol SMTP dan email pengirim untuk notifikasi sistem serta pengiriman kode OTP verifikasi akun di GrowHub.
        </p>
      </div>

      <div class="header-actions">
        <button
          type="button"
          class="btn btn-outline"
          @click="fetchSettings"
          :disabled="loading || saving || testing"
        >
          <RotateCcw :size="16" />
          <span>Muat Ulang</span>
        </button>
      </div>
    </div>

    <!-- Summary Stats Grid -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon-wrapper bg-blue-soft">
          <Server :size="20" class="text-blue" />
        </div>
        <div class="stat-meta">
          <span class="stat-label">Driver &amp; Protocol</span>
          <span v-if="loading" class="skeleton sk-stat-val"></span>
          <span v-else class="stat-val">
            {{ form.mail_mailer || 'smtp' }} <span v-if="form.mail_scheme">({{ form.mail_scheme }})</span>
          </span>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon-wrapper bg-green-soft">
          <Globe :size="20" class="text-primary" />
        </div>
        <div class="stat-meta">
          <span class="stat-label">SMTP Host &amp; Port</span>
          <span v-if="loading" class="skeleton sk-stat-val"></span>
          <span v-else class="stat-val" :title="`${form.mail_host}:${form.mail_port}`">
            {{ form.mail_host || 'Belum diatur' }}:{{ form.mail_port || 465 }}
          </span>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon-wrapper bg-purple-soft">
          <Send :size="20" style="color: #8b5cf6;" />
        </div>
        <div class="stat-meta">
          <span class="stat-label">Default From Address</span>
          <span v-if="loading" class="skeleton sk-stat-val"></span>
          <span v-else class="stat-val" :title="form.mail_from_address">
            {{ form.mail_from_address || 'Belum diatur' }}
          </span>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon-wrapper bg-amber-soft">
          <KeyRound :size="20" class="text-amber" />
        </div>
        <div class="stat-meta">
          <span class="stat-label">Status Kredensial</span>
          <span v-if="loading" class="skeleton sk-stat-val"></span>
          <span v-else class="stat-val text-primary">
            {{ hasPassword ? 'Password Tersimpan' : 'Password Kosong' }}
          </span>
        </div>
      </div>
    </div>

    <!-- Main Content Grid -->
    <div class="mail-settings-grid">
      <!-- Left: Form Configuration -->
      <div class="card card-pad">
        <div class="row-between mb-4">
          <div>
            <h2 class="card-title">Konfigurasi SMTP Server &amp; Pengirim</h2>
            <p class="small text-2">Konfigurasi ini akan otomatis disimpan di database dan file konfigurasi <code>.env</code> backend.</p>
          </div>
        </div>

        <div class="info-callout">
          <Info :size="18" />
          <div>
            Perubahan konfigurasi email membutuhkan kredensial SMTP yang valid dari penyedia layanan email atau server perusahaan (misal: <code>smtp-1.rcoid.com</code> port <code>465</code> dengan SSL/SMTPS).
          </div>
        </div>

        <form @submit.prevent="saveSettings">
          <div class="stack" style="gap: 16px;">
            <div class="grid-2-col">
              <div class="field">
                <label class="label">Mail Driver / Mailer <span class="text-red">*</span></label>
                <input
                  v-model="form.mail_mailer"
                  type="text"
                  class="input"
                  placeholder="smtp"
                  required
                />
                <span class="hint">Umumnya diisi <code>smtp</code></span>
              </div>

              <div class="field">
                <label class="label">Mail Scheme / Enkripsi</label>
                <input
                  v-model="form.mail_scheme"
                  type="text"
                  class="input"
                  placeholder="smtps (atau kosong untuk default)"
                />
                <span class="hint">Gunakan <code>smtps</code> untuk SSL port 465, atau kosongkan</span>
              </div>
            </div>

            <div class="grid-2-col">
              <div class="field">
                <label class="label">SMTP Host Server <span class="text-red">*</span></label>
                <input
                  v-model="form.mail_host"
                  type="text"
                  class="input"
                  placeholder="Contoh: smtp-1.rcoid.com"
                  required
                />
              </div>

              <div class="field">
                <label class="label">SMTP Port <span class="text-red">*</span></label>
                <input
                  v-model.number="form.mail_port"
                  type="number"
                  class="input"
                  placeholder="465"
                  required
                />
                <span class="hint">Port 465 (SSL/SMTPS) atau 587 (TLS/STARTTLS)</span>
              </div>
            </div>

            <div class="field">
              <label class="label">SMTP Username <span class="text-red">*</span></label>
              <input
                v-model="form.mail_username"
                type="text"
                class="input"
                placeholder="Contoh: noreply.pepito@rcoid.com"
                required
              />
            </div>

            <div class="field">
              <label class="label">SMTP Password</label>
              <div class="input-with-action">
                <input
                  v-model="form.mail_password"
                  :type="showPassword ? 'text' : 'password'"
                  class="input"
                  :placeholder="hasPassword ? '•••••••••••• (Kosongkan jika tidak ingin mengubah)' : 'Masukkan password SMTP'"
                />
                <button
                  type="button"
                  class="action-addon-btn"
                  @click="showPassword = !showPassword"
                  title="Tampilkan / Sembunyikan Password"
                >
                  <EyeOff v-if="showPassword" :size="16" />
                  <Eye v-else :size="16" />
                </button>
              </div>
              <span class="hint">
                {{ hasPassword ? 'Password saat ini sudah tersimpan di sistem. Isi kolom ini hanya jika Anda ingin menggantinya.' : 'Password belum diatur.' }}
              </span>
            </div>

            <div class="grid-2-col">
              <div class="field">
                <label class="label">Email Pengirim (From Address) <span class="text-red">*</span></label>
                <input
                  v-model="form.mail_from_address"
                  type="email"
                  class="input"
                  placeholder="Contoh: noreply.pepito@rcoid.com"
                  required
                />
              </div>

              <div class="field">
                <label class="label">Nama Pengirim (From Name) <span class="text-red">*</span></label>
                <input
                  v-model="form.mail_from_name"
                  type="text"
                  class="input"
                  placeholder="Contoh: GrowHub- ${APP_NAME}"
                  required
                />
              </div>
            </div>

            <div class="row" style="justify-content: flex-end; margin-top: 12px; gap: 10px;">
              <button
                type="submit"
                class="btn btn-primary"
                :disabled="saving || loading"
              >
                <Save :size="16" />
                <span>{{ saving ? 'Menyimpan...' : 'Simpan Pengaturan SMTP' }}</span>
              </button>
            </div>
          </div>
        </form>
      </div>

      <!-- Right: Test SMTP Connection & Email Sender -->
      <div class="card card-pad test-box-card">
        <div class="mb-4">
          <h2 class="card-title">Uji Coba Pengiriman Email</h2>
          <p class="small text-2">Kirim email pengujian untuk memverifikasi koneksi SMTP server, port, dan autentikasi kredensial.</p>
        </div>

        <form @submit.prevent="sendTestEmail">
          <div class="stack" style="gap: 14px;">
            <div class="field">
              <label class="label">Alamat Email Penerima Uji Coba <span class="text-red">*</span></label>
              <input
                v-model="testEmail"
                type="email"
                class="input"
                placeholder="nama.karyawan@rcoid.com"
                required
              />
              <span class="hint">Email uji coba akan dikirimkan langsung ke alamat ini.</span>
            </div>

            <button
              type="submit"
              class="btn btn-primary"
              style="width: 100%; justify-content: center;"
              :disabled="testing || loading || !testEmail"
            >
              <Send :size="16" />
              <span>{{ testing ? 'Mengirim Email Uji Coba...' : 'Kirim Email Uji Coba' }}</span>
            </button>
          </div>
        </form>

        <!-- Test Result Alert Banner -->
        <div v-if="testResult" :class="['test-result-box', testResult.success ? 'test-result-success' : 'test-result-error']">
          <CheckCircle2 v-if="testResult.success" :size="18" />
          <AlertTriangle v-else :size="18" />
          <div>
            <strong>{{ testResult.success ? 'Berhasil Terhubung & Terkirim!' : 'Koneksi SMTP Gagal!' }}</strong>
            <p style="margin: 4px 0 0;">{{ testResult.message }}</p>
          </div>
        </div>

        <div class="info-callout" style="margin-top: 20px; margin-bottom: 0;">
          <HelpCircle :size="18" />
          <div class="small">
            <strong>Tips Diagnosa Masalah:</strong>
            <ul style="padding-left: 16px; margin: 4px 0 0;">
              <li>Periksa apakah host <code>{{ form.mail_host || 'smtp server' }}</code> dapat dijangkau dari server backend.</li>
              <li>Pastikan port <code>{{ form.mail_port || 465 }}</code> diizinkan oleh firewall jaringan.</li>
              <li>Pastikan format username dan password akun email sudah sesuai.</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import {
  Mail, RotateCcw, Server, Globe, Send, KeyRound, Info,
  Eye, EyeOff, Save, CheckCircle2, AlertTriangle, HelpCircle
} from 'lucide-vue-next'
import api from '@/api/client'
import { useToastStore } from '@/stores/toast'
import { useAuthStore } from '@/stores/auth'

const toastStore = useToastStore()
const authStore = useAuthStore()

const loading = ref(true)
const saving = ref(false)
const testing = ref(false)
const showPassword = ref(false)
const hasPassword = ref(false)

const form = reactive({
  mail_mailer: 'smtp',
  mail_scheme: 'smtps',
  mail_host: 'smtp-1.rcoid.com',
  mail_port: 465,
  mail_username: 'noreply.pepito@rcoid.com',
  mail_password: '',
  mail_from_address: 'noreply.pepito@rcoid.com',
  mail_from_name: 'GrowHub- ${APP_NAME}',
})

const testEmail = ref('')
const testResult = ref(null)

const applySettings = (data) => {
  if (!data) return
  const s = data.setting || data
  form.mail_mailer = s.mail_mailer ?? 'smtp'
  form.mail_scheme = s.mail_scheme ?? 'smtps'
  form.mail_host = s.mail_host ?? 'smtp-1.rcoid.com'
  form.mail_port = s.mail_port ?? 465
  form.mail_username = s.mail_username ?? 'noreply.pepito@rcoid.com'
  form.mail_password = ''
  form.mail_from_address = s.mail_from_address ?? 'noreply.pepito@rcoid.com'
  form.mail_from_name = s.mail_from_name ?? 'GrowHub- ${APP_NAME}'
  hasPassword.value = !!s.has_password
}

const fetchSettings = async () => {
  loading.value = true
  testResult.value = null
  try {
    const res = await api.get('/admin/mail-settings')
    applySettings(res.data)
    if (!testEmail.value && authStore.user?.email) {
      testEmail.value = authStore.user.email
    }
  } catch (err) {
    toastStore.error(err.response?.data?.message || 'Gagal memuat konfigurasi email.')
  } finally {
    loading.value = false
  }
}

const saveSettings = async () => {
  saving.value = true
  try {
    const payload = {
      mail_mailer: form.mail_mailer,
      mail_scheme: form.mail_scheme,
      mail_host: form.mail_host,
      mail_port: form.mail_port,
      mail_username: form.mail_username,
      mail_from_address: form.mail_from_address,
      mail_from_name: form.mail_from_name,
    }

    if (form.mail_password) {
      payload.mail_password = form.mail_password
    }

    const res = await api.put('/admin/mail-settings', payload)
    applySettings(res.data)
    toastStore.success(res.data.message || 'Konfigurasi email & SMTP berhasil disimpan.')
  } catch (err) {
    toastStore.error(err.response?.data?.message || 'Gagal menyimpan konfigurasi email.')
  } finally {
    saving.value = false
  }
}

const sendTestEmail = async () => {
  if (!testEmail.value) {
    toastStore.error('Masukkan alamat email penerima uji coba.')
    return
  }

  testing.value = true
  testResult.value = null

  try {
    const res = await api.post('/admin/mail-settings/test', {
      recipient_email: testEmail.value,
    })

    testResult.value = {
      success: true,
      message: res.data.message || 'Email uji coba berhasil dikirim ke ' + testEmail.value,
    }
    toastStore.success(res.data.message || 'Email uji coba berhasil dikirim.')
  } catch (err) {
    const errMsg = err.response?.data?.message || err.response?.data?.error || 'Gagal mengirim email uji coba.'
    testResult.value = {
      success: false,
      message: errMsg,
    }
    toastStore.error(errMsg)
  } finally {
    testing.value = false
  }
}

onMounted(() => {
  fetchSettings()
})
</script>

<style scoped src="@/styles/features/admin-mail-setting.css"></style>
