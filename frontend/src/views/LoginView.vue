<template>
  <div class="login-page">
    <div class="login-wrapper">
      <!-- Left side: Branding & Gamification Showpiece -->
      <div class="login-hero">
        <div class="hero-top">
          <div class="brand-badge">
            <Sprout :size="24" class="text-white" />
            <span class="brand-badge-name">Grow<span class="text-green-light">Hub</span></span>
          </div>
          <span class="hero-tagline">Knowledge & Engagement System</span>
        </div>

        <div class="hero-center">
          <h1 class="hero-heading">
            Grow Together,<br />
            Share Knowledge.
          </h1>
          <p class="hero-subtext">
            Collaboration and gamification platform where your ideas, best practices, and experiences are rewarded with points, streaks, and recognition.
          </p>

          <!-- Gamification features list -->
          <div class="hero-features">
            <div class="feature-item">
              <div class="feature-icon bg-amber">
                <Sparkles :size="18" />
              </div>
              <div class="feature-text">
                <span class="bold">Points & Rewards</span>
                <span class="small opacity-80">Earn points from every content post, discussion, and course</span>
              </div>
            </div>

            <div class="feature-item">
              <div class="feature-icon bg-orange">
                <Flame :size="18" />
              </div>
              <div class="feature-text">
                <span class="bold">Daily Streak</span>
                <span class="small opacity-80">Maintain daily consistency to unlock streak bonuses</span>
              </div>
            </div>

            <div class="feature-item">
              <div class="feature-icon bg-green">
                <Award :size="18" />
              </div>
              <div class="feature-text">
                <span class="bold">Levels & Leaderboard</span>
                <span class="small opacity-80">Advance from Seedling to Evergreen and lead company rankings</span>
              </div>
            </div>
          </div>
        </div>

        <div class="hero-bottom">
          <div class="security-badge">
            <ShieldCheck :size="16" />
            <span>Authorized Employees System</span>
          </div>
        </div>
      </div>

      <!-- Right side: Login Form / First-Time Password Setup Form -->
      <div class="login-form-container">
        <!-- FIRST LOGIN PASSWORD SETUP -->
        <div v-if="isFirstLogin" class="first-login-wrapper">
          <div class="form-header">
            <div class="welcome-label label-security">
              <KeyRound :size="13" />
              <span>Security & Activation</span>
            </div>
            <h2 class="form-title">
              {{ otpStep === 'verify' ? 'Verifikasi OTP Email' : 'Set New Password' }}
            </h2>
            <p class="form-subtitle">
              {{
                otpStep === 'verify'
                  ? 'Untuk keamanan akun Anda, masukkan kode OTP 6 digit yang dikirim ke email terdaftar sebelum membuat password baru.'
                  : 'Verifikasi berhasil. Silakan buat password baru Anda sebelum melanjutkan ke GrowHub.'
              }}
            </p>
          </div>

          <!-- 2-Step Indicator -->
          <div class="otp-stepper mb-3">
            <div class="otp-step" :class="{ active: otpStep === 'verify', done: otpStep === 'password' }">
              <span class="step-num">
                <CheckCircle2 v-if="otpStep === 'password'" :size="14" />
                <span v-else>1</span>
              </span>
              <span class="step-label">Verifikasi OTP</span>
            </div>
            <div class="step-connector" :class="{ done: otpStep === 'password' }"></div>
            <div class="otp-step" :class="{ active: otpStep === 'password' }">
              <span class="step-num">2</span>
              <span class="step-label">Password Baru</span>
            </div>
          </div>

          <!-- Logged in User Identification Card -->
          <div class="user-pill-card mb-3">
            <div class="user-pill-avatar">
              <User :size="18" />
            </div>
            <div class="user-pill-info">
              <div class="user-pill-name bold">{{ authStore.user?.name }}</div>
              <div class="user-pill-meta small muted">
                NIP: {{ authStore.user?.nip }} • <span class="capitalize">{{ authStore.user?.role || 'employee' }}</span>
              </div>
              <div v-if="authStore.user?.email" class="user-pill-meta small muted">
                Email: <strong>{{ maskedEmail || authStore.user?.masked_email || authStore.user?.email }}</strong>
              </div>
            </div>
          </div>

          <!-- NO REGISTERED EMAIL BLOCK -->
          <div v-if="!hasRegisteredEmail" class="stack">
            <div class="error-alert">
              <AlertCircle :size="18" class="text-red flex-shrink-0" />
              <span>
                Akun Anda belum memiliki alamat email terdaftar. Silakan hubungi Administrator untuk mendaftarkan email Anda sebelum mengganti password.
              </span>
            </div>

            <button
              type="button"
              class="btn btn-outline btn-block mt-2"
              @click="cancelFirstLogin"
            >
              Kembali ke Login
            </button>
          </div>

          <!-- STEP 1: OTP VERIFICATION -->
          <form v-else-if="otpStep === 'verify'" @submit.prevent="handleVerifyOtp" class="login-form stack">
            <!-- Security / Email Delivery Info -->
            <div class="otp-info-box">
              <Mail :size="16" class="text-primary flex-shrink-0" />
              <div class="small">
                <span v-if="otpSending">Mengirim kode OTP ke email terdaftar Anda...</span>
                <span v-else-if="otpSent">
                  Kode OTP 6 digit telah dikirim ke <strong>{{ maskedEmail || authStore.user?.masked_email || authStore.user?.email }}</strong>. Berlaku 1 menit.
                </span>
                <span v-else>
                  Klik <strong>Kirim Kode OTP</strong> untuk menerima kode verifikasi di email <strong>{{ maskedEmail || authStore.user?.masked_email || authStore.user?.email }}</strong>.
                </span>
              </div>
            </div>

            <!-- Error alert -->
            <div v-if="otpErrorMessage" class="error-alert">
              <AlertCircle :size="18" class="text-red flex-shrink-0" />
              <span>{{ otpErrorMessage }}</span>
            </div>

            <!-- OTP Input -->
            <div class="field">
              <label class="label" for="otp-code-input">Kode OTP (6 Digit) <span class="text-red">*</span></label>
              <div class="input-icon">
                <ShieldCheck :size="18" />
                <input
                  id="otp-code-input"
                  v-model="otpCode"
                  @input="sanitizeOtp"
                  type="text"
                  inputmode="numeric"
                  autocomplete="one-time-code"
                  maxlength="6"
                  placeholder="• • • • • •"
                  class="input otp-code-input"
                  required
                />
              </div>
              <span class="hint">Periksa kotak masuk (Inbox) atau folder Spam email terdaftar Anda</span>
            </div>

            <!-- Resend Row -->
            <div class="otp-resend-row">
              <span class="small muted">Tidak menerima kode?</span>
              <button
                type="button"
                class="btn-link-resend"
                :disabled="otpSending || resendCountdown > 0"
                @click="sendOtp"
              >
                <RefreshCw :size="13" :class="{ spin: otpSending }" />
                <span v-if="otpSending">Mengirim...</span>
                <span v-else-if="resendCountdown > 0">Kirim ulang ({{ resendCountdown }}s)</span>
                <span v-else>{{ otpSent ? 'Kirim Ulang OTP' : 'Kirim Kode OTP' }}</span>
              </button>
            </div>

            <!-- Action Buttons -->
            <div class="stack mt-2">
              <button
                type="submit"
                class="btn btn-primary btn-lg btn-block"
                :disabled="verifyLoading || otpCode.length !== 6"
              >
                <span v-if="verifyLoading" class="spinner"></span>
                <span v-else>Verifikasi Kode OTP</span>
                <ArrowRight v-if="!verifyLoading" :size="18" />
              </button>

              <button
                type="button"
                class="btn btn-outline btn-block"
                :disabled="verifyLoading || otpSending"
                @click="cancelFirstLogin"
              >
                Batal / Ganti Akun
              </button>

              <button
                type="button"
                class="btn-helper-link text-center"
                @click="isHelperOpen = true"
              >
                <Headphones :size="14" />
                <span>Kendala kode OTP? Hubungi Kontak Helper</span>
              </button>
            </div>
          </form>

          <!-- STEP 2: SET NEW PASSWORD -->
          <div v-else>
            <!-- Verified & Security Policy Note -->
            <div class="otp-verified-box mb-3">
              <CheckCircle2 :size="16" class="text-primary flex-shrink-0" />
              <span class="small">
                Verifikasi OTP berhasil. Setelah password baru dibuat, reset password berikutnya <strong>hanya dapat dilakukan oleh Administrator</strong>.
              </span>
            </div>

            <form @submit.prevent="handleFirstChangePassword" class="login-form stack">
              <!-- Error alert -->
              <div v-if="changeErrorMessage" class="error-alert">
                <AlertCircle :size="18" class="text-red flex-shrink-0" />
                <span>{{ changeErrorMessage }}</span>
              </div>

              <!-- New Password -->
              <div class="field">
                <label class="label">Password Baru <span class="text-red">*</span></label>
                <div class="input-icon">
                  <Lock :size="18" />
                  <input
                    v-model="newPassword"
                    :type="showNewPassword ? 'text' : 'password'"
                    placeholder="Minimal 8 karakter"
                    class="input"
                    required
                    minlength="8"
                    autocomplete="new-password"
                  />
                  <button
                    type="button"
                    class="btn-eye"
                    @click="showNewPassword = !showNewPassword"
                    aria-label="Toggle password"
                  >
                    <EyeOff v-if="showNewPassword" :size="18" />
                    <Eye v-else :size="18" />
                  </button>
                </div>
                <span class="hint">Minimal 8 karakter & tidak boleh sama dengan NIP default</span>
              </div>

              <!-- Confirm Password -->
              <div class="field">
                <label class="label">Konfirmasi Password Baru <span class="text-red">*</span></label>
                <div class="input-icon">
                  <Lock :size="18" />
                  <input
                    v-model="confirmPassword"
                    :type="showConfirmPassword ? 'text' : 'password'"
                    placeholder="Ulangi password baru"
                    class="input"
                    required
                    minlength="8"
                    autocomplete="new-password"
                  />
                  <button
                    type="button"
                    class="btn-eye"
                    @click="showConfirmPassword = !showConfirmPassword"
                    aria-label="Toggle password"
                  >
                    <EyeOff v-if="showConfirmPassword" :size="18" />
                    <Eye v-else :size="18" />
                  </button>
                </div>
              </div>

              <!-- Action Buttons -->
              <div class="stack mt-2">
                <button type="submit" class="btn btn-primary btn-lg btn-block" :disabled="changeLoading">
                  <span v-if="changeLoading" class="spinner"></span>
                  <span v-else>Simpan & Masuk ke GrowHub</span>
                  <ArrowRight v-if="!changeLoading" :size="18" />
                </button>

                <button
                  type="button"
                  class="btn btn-outline btn-block"
                  :disabled="changeLoading"
                  @click="cancelFirstLogin"
                >
                  Batal / Ganti Akun
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- STANDARD SIGN IN FORM -->
        <div v-else>
          <div class="form-header">
            <div class="welcome-label">Welcome Back</div>
            <h2 class="form-title">Sign in to GrowHub</h2>
            <p class="form-subtitle">Use your 9-10 digit Employee ID (NIP).</p>
          </div>

          <form @submit.prevent="handleSubmit" class="login-form stack">
            <!-- Error alert -->
            <div v-if="errorMessage" class="error-alert">
              <AlertCircle :size="18" class="text-red" />
              <span>{{ errorMessage }}</span>
            </div>

            <!-- NIP Input -->
            <div class="field">
              <label class="label">Employee ID (NIP)</label>
              <div class="input-icon">
                <User :size="18" />
                <input
                  v-model="nip"
                  type="text"
                  maxlength="10"
                  placeholder="e.g. 202301001"
                  class="input"
                  required
                  autocomplete="username"
                />
              </div>
              <span class="hint">Employee ID consists of 9 to 10 numeric digits</span>
            </div>

            <!-- Password Input -->
            <div class="field">
              <label class="label">Password</label>
              <div class="input-icon">
                <Lock :size="18" />
                <input
                  v-model="password"
                  :type="showPassword ? 'text' : 'password'"
                  placeholder="Enter password"
                  class="input"
                  required
                  autocomplete="current-password"
                />
                <button
                  type="button"
                  class="btn-eye"
                  @click="showPassword = !showPassword"
                  aria-label="Toggle password"
                >
                  <EyeOff v-if="showPassword" :size="18" />
                  <Eye v-else :size="18" />
                </button>
              </div>
            
            </div>

            <!-- Submit Button -->
            <button type="submit" class="btn btn-primary btn-lg btn-block mt-2" :disabled="loading">
              <span v-if="loading" class="spinner"></span>
              <span v-else>Sign In</span>
              <ArrowRight v-if="!loading" :size="18" />
            </button>
          </form>

          <!-- Quick Demo Accounts (for effortless testing of different roles) -->
          <div class="demo-accounts-card">
            <div class="demo-title">
              <Sparkles :size="14" class="text-primary" />
              <span>Quick Demo Accounts (Click to Fill):</span>
            </div>
            <div class="demo-chips">
              <button
                v-for="acc in demoAccounts"
                :key="acc.nip"
                class="demo-chip"
                @click="fillDemo(acc)"
                type="button"
              >
                <div class="demo-chip-role" :class="`role-${acc.role}`">{{ acc.role }}</div>
                <div class="demo-chip-text">
                  <span class="bold">{{ acc.name }}</span>
                  <span class="muted small">ID: {{ acc.nip }}</span>
                </div>
              </button>
            </div>
          </div>

          <!-- Helper & Support Contacts Section -->
          <div class="login-helper-card">
            <button
              type="button"
              class="login-helper-toggle"
              @click="isHelperOpen = !isHelperOpen"
              aria-label="Toggle helper contact details"
            >
              <div class="helper-toggle-left">
                <div class="helper-icon-bubble">
                  <Headphones :size="17" />
                </div>
                <div class="helper-toggle-text">
                  <span class="helper-toggle-title">Butuh Bantuan Login / Kendala Akun?</span>
                  <span class="helper-toggle-sub">Hubungi Kontak Helper &amp; IT Support GrowHub</span>
                </div>
              </div>
              <ChevronDown :size="18" class="helper-chevron" :class="{ 'is-flipped': isHelperOpen }" />
            </button>

            <div v-if="isHelperOpen" class="login-helper-content">
              <p class="small text-2 mb-3">
                Jika lupa password, akun terkunci, atau belum menerima OTP, silakan hubungi tim helper resmi di bawah ini:
              </p>

              <div v-if="helperLoading" class="helper-loading-list stack" style="gap: 8px;">
                <div v-for="n in 3" :key="`sk-hlp-${n}`" class="skeleton" style="height: 60px; border-radius: 12px;"></div>
              </div>

              <div v-else-if="helperContacts.length === 0" class="small muted text-center py-2">
                Kontak helper belum dikonfigurasi oleh Administrator.
              </div>

              <div v-else class="helper-contacts-list stack" style="gap: 10px;">
                <div
                  v-for="contact in helperContacts"
                  :key="contact.id"
                  class="helper-contact-item"
                >
                  <div class="helper-channel-icon" :class="`channel-${contact.channel}`">
                    <MessageCircle v-if="contact.channel === 'whatsapp'" :size="18" />
                    <Mail v-else-if="contact.channel === 'email'" :size="18" />
                    <PhoneCall v-else-if="contact.channel === 'phone'" :size="18" />
                    <Send v-else-if="contact.channel === 'telegram'" :size="18" />
                    <Headphones v-else :size="18" />
                  </div>

                  <div class="helper-details">
                    <div class="helper-name-row">
                      <span class="helper-name">{{ contact.name }}</span>
                      <span class="helper-channel-badge" :class="`badge-${contact.channel}`">
                        {{ getChannelLabel(contact.channel) }}
                      </span>
                    </div>

                    <div v-if="contact.role_or_title" class="helper-role">{{ contact.role_or_title }}</div>

                    <div v-if="contact.available_hours" class="helper-hours">
                      <Clock :size="12" />
                      <span>{{ contact.available_hours }}</span>
                    </div>

                    <p v-if="contact.description" class="helper-desc">{{ contact.description }}</p>

                    <div class="helper-action-row">
                      <a
                        :href="getContactLink(contact)"
                        :target="contact.channel === 'phone' ? '_self' : '_blank'"
                        class="btn-contact-action"
                        :class="`action-${contact.channel}`"
                      >
                        <MessageCircle v-if="contact.channel === 'whatsapp'" :size="14" />
                        <Mail v-else-if="contact.channel === 'email'" :size="14" />
                        <PhoneCall v-else-if="contact.channel === 'phone'" :size="14" />
                        <Send v-else-if="contact.channel === 'telegram'" :size="14" />
                        <Headphones v-else :size="14" />
                        <span>{{ contact.contact_value }}</span>
                        <ExternalLink v-if="contact.channel !== 'phone'" :size="12" style="margin-left: 2px;" />
                      </a>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import {
  Sprout, Sparkles, Flame, Award, ShieldCheck, User, Lock,
  Eye, EyeOff, ArrowRight, AlertCircle, KeyRound, Mail, CheckCircle2, RefreshCw,
  Headphones, MessageCircle, PhoneCall, ExternalLink, Clock, ChevronDown, ChevronUp, Send
} from 'lucide-vue-next'
import api from '@/api/client'
import { getApiErrorMessage } from '@/utils/httpError'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'

const router = useRouter()
const authStore = useAuthStore()
const toastStore = useToastStore()

const nip = ref('202301001')
const password = ref('202301001')
const showPassword = ref(false)
const loading = ref(false)
const errorMessage = ref('')

// First login password setup + OTP verification state
const isFirstLogin = ref(false)
const otpStep = ref('verify') // 'verify' | 'password'
const otpCode = ref('')
const otpVerificationToken = ref('')

// Helper & Support contacts state
const helperContacts = ref([])
const helperLoading = ref(false)
const isHelperOpen = ref(false)

const fetchHelpers = async () => {
  helperLoading.value = true
  try {
    const res = await api.get('/support/helpers')
    helperContacts.value = res.data.helpers || []
  } catch (err) {
    console.error('Failed to load helpers', err)
  } finally {
    helperLoading.value = false
  }
}

const getChannelLabel = (channel) => {
  switch (channel) {
    case 'whatsapp': return 'WhatsApp'
    case 'email': return 'Email Support'
    case 'phone': return 'Hotline Telepon'
    case 'telegram': return 'Telegram'
    default: return 'Kontak'
  }
}

const getContactLink = (contact) => {
  if (contact.channel === 'whatsapp') {
    const digits = (contact.contact_value || '').replace(/\D/g, '')
    const phone = digits.startsWith('0') ? '62' + digits.slice(1) : digits
    return `https://wa.me/${phone}?text=Halo%20Admin%20GrowHub,%20saya%20membutuhkan%20bantuan%20terkait%20akun%20login%20saya.`
  }
  if (contact.channel === 'email') {
    return `mailto:${contact.contact_value}?subject=Bantuan%20Akun%20GrowHub`
  }
  if (contact.channel === 'phone') {
    const digits = (contact.contact_value || '').replace(/[^\d+]/g, '')
    return `tel:${digits}`
  }
  if (contact.channel === 'telegram') {
    const clean = (contact.contact_value || '').replace('@', '').trim()
    return `https://t.me/${clean}`
  }
  return '#'
}
const maskedEmail = ref('')
const otpSending = ref(false)
const otpSent = ref(false)
const verifyLoading = ref(false)
const otpErrorMessage = ref('')
const resendCountdown = ref(0)
let countdownTimer = null

const newPassword = ref('')
const confirmPassword = ref('')
const showNewPassword = ref(false)
const showConfirmPassword = ref(false)
const changeLoading = ref(false)
const changeErrorMessage = ref('')

const hasRegisteredEmail = computed(() => Boolean(authStore.user?.email))

const clearCountdown = () => {
  if (countdownTimer) {
    clearInterval(countdownTimer)
    countdownTimer = null
  }
}

const startCountdown = (seconds = 60) => {
  clearCountdown()
  resendCountdown.value = Math.max(0, Number(seconds) || 0)
  if (resendCountdown.value <= 0) return

  countdownTimer = setInterval(() => {
    if (resendCountdown.value <= 1) {
      resendCountdown.value = 0
      clearCountdown()
    } else {
      resendCountdown.value -= 1
    }
  }, 1000)
}

const sanitizeOtp = (e) => {
  otpCode.value = e.target.value.replace(/\D/g, '').slice(0, 6)
}

const resetFirstLoginState = () => {
  otpStep.value = 'verify'
  otpCode.value = ''
  otpVerificationToken.value = ''
  maskedEmail.value = authStore.user?.masked_email || ''
  otpSending.value = false
  otpSent.value = false
  verifyLoading.value = false
  otpErrorMessage.value = ''
  newPassword.value = ''
  confirmPassword.value = ''
  changeErrorMessage.value = ''
  clearCountdown()
  resendCountdown.value = 0
}

const sendOtp = async () => {
  if (!hasRegisteredEmail.value) {
    otpErrorMessage.value = 'Akun Anda belum memiliki email terdaftar. Hubungi Administrator terlebih dahulu.'
    return
  }

  otpSending.value = true
  otpErrorMessage.value = ''
  try {
    const res = await api.post('/auth/change-password/send-otp')
    otpSent.value = true
    maskedEmail.value = res.data.masked_email || authStore.user?.masked_email || authStore.user?.email
    startCountdown(res.data.resend_cooldown || 60)
    toastStore.info(res.data.message || 'Kode OTP telah dikirim ke email terdaftar Anda.')
  } catch (err) {
    const status = err.response?.status
    const data = err.response?.data || {}
    if (status === 429 && data.retry_after) {
      otpSent.value = true
      maskedEmail.value = data.masked_email || authStore.user?.masked_email || authStore.user?.email
      startCountdown(data.retry_after)
      otpErrorMessage.value = data.message
    } else {
      otpErrorMessage.value = getApiErrorMessage(err)
    }
  } finally {
    otpSending.value = false
  }
}

const handleVerifyOtp = async () => {
  otpErrorMessage.value = ''
  if (!/^\d{6}$/.test(otpCode.value)) {
    otpErrorMessage.value = 'Kode OTP harus terdiri dari 6 digit angka.'
    return
  }

  verifyLoading.value = true
  try {
    const res = await api.post('/auth/change-password/verify-otp', {
      otp: otpCode.value,
    })
    otpVerificationToken.value = res.data.otp_verification_token
    otpStep.value = 'password'
    changeErrorMessage.value = ''
    toastStore.success('OTP terverifikasi! Silakan buat password baru Anda.')
  } catch (err) {
    otpErrorMessage.value = getApiErrorMessage(err)
  } finally {
    verifyLoading.value = false
  }
}

onMounted(() => {
  fetchHelpers()
  if (authStore.isAuthenticated && authStore.user?.must_change_password) {
    isFirstLogin.value = true
    resetFirstLoginState()
    if (hasRegisteredEmail.value) {
      sendOtp()
    }
  }
})

onBeforeUnmount(() => {
  clearCountdown()
})

const demoAccounts = [
  { nip: '202301001', name: 'Andi Saputra', role: 'employee', dept: 'Store Operations' },
  { nip: '1000000002', name: 'Rina Kartika', role: 'reviewer', dept: 'Human Capital' },
  { nip: '1000000001', name: 'Admin GrowHub', role: 'admin', dept: 'HC Administrator' },
]

const fillDemo = (acc) => {
  nip.value = acc.nip
  password.value = acc.nip
  errorMessage.value = ''
}

const handleSubmit = async () => {
  nip.value = nip.value.trim()
  if (!/^\d{9,10}$/.test(nip.value)) {
    errorMessage.value = 'Employee ID must consist of 9 to 10 numeric digits.'
    return
  }

  loading.value = true
  errorMessage.value = ''

  const res = await authStore.login(nip.value, password.value)
  loading.value = false

  if (res.success) {
    if (authStore.user?.must_change_password) {
      isFirstLogin.value = true
      resetFirstLoginState()
      if (hasRegisteredEmail.value) {
        await sendOtp()
      }
    } else {
      toastStore.success(`Welcome back, ${authStore.user?.name}!`)
      router.push('/')
    }
  } else {
    errorMessage.value = res.message
  }
}

const handleFirstChangePassword = async () => {
  changeErrorMessage.value = ''

  if (!otpVerificationToken.value) {
    otpStep.value = 'verify'
    otpErrorMessage.value = 'Silakan verifikasi kode OTP terlebih dahulu.'
    return
  }

  if (newPassword.value.length < 8) {
    changeErrorMessage.value = 'Password baru minimal 8 karakter.'
    return
  }

  if (newPassword.value !== confirmPassword.value) {
    changeErrorMessage.value = 'Konfirmasi password baru tidak cocok.'
    return
  }

  if (newPassword.value === authStore.user?.nip) {
    changeErrorMessage.value = 'Password baru tidak boleh sama dengan NIP default.'
    return
  }

  changeLoading.value = true
  try {
    const res = await api.post('/auth/first-change-password', {
      otp_verification_token: otpVerificationToken.value,
      password: newPassword.value,
      password_confirmation: confirmPassword.value,
    })

    authStore.updateUser(res.data.user)
    toastStore.success('Password berhasil diperbarui! Selamat datang di GrowHub.')
    isFirstLogin.value = false
    clearCountdown()
    router.push('/')
  } catch (err) {
    const data = err.response?.data || {}
    if (data.errors?.otp_token?.[0] || data.errors?.otp_verification_token?.[0]) {
      otpVerificationToken.value = ''
      otpCode.value = ''
      otpStep.value = 'verify'
      otpErrorMessage.value = data.errors?.otp_token?.[0] || data.errors?.otp_verification_token?.[0]
    } else {
      changeErrorMessage.value = getApiErrorMessage(err)
    }
  } finally {
    changeLoading.value = false
  }
}

const cancelFirstLogin = async () => {
  clearCountdown()
  await authStore.logout()
  isFirstLogin.value = false
  resetFirstLoginState()
  errorMessage.value = ''
}
</script>

<style scoped src="@/styles/features/login.css"></style>
