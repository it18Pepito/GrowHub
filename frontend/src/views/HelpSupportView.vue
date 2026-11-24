<template>
  <div class="page help-page">
    <div class="page-header">
      <div>
        <h1 class="page-title">Help &amp; Support</h1>
        <p class="page-subtitle">Help center, kontak helper resmi, panduan gamifikasi, dan tiket bantuan untuk karyawan.</p>
      </div>
    </div>

    <!-- Helper & Support Contacts Section -->
    <div class="card card-pad mb-6 helper-section-card">
      <div class="card-head row-between mb-4 flex-wrap" style="gap: 12px;">
        <div>
          <div class="badge badge-primary-light mb-1">
            <Headphones :size="13" />
            <span>Layanan Bantuan &amp; Helper Resmi</span>
          </div>
          <h2 class="card-title">Kontak Helper &amp; Support Karyawan</h2>
          <p class="small text-2">
            Mengalami kendala akun, lupa password, kurasi materi, atau error sistem? Hubungi langsung tim helper resmi kami:
          </p>
        </div>

        <router-link
          v-if="authStore.isAdmin"
          to="/admin/helper-contacts"
          class="btn btn-outline btn-admin-helper"
        >
          <Settings :size="16" />
          <span>Kelola Kontak Helper (Admin)</span>
        </router-link>
      </div>

      <div v-if="helpersLoading" class="grid grid-3" aria-label="Loading helpers">
        <div v-for="n in 3" :key="`sk-hlp-p-${n}`" class="skeleton" style="height: 180px; border-radius: 14px;"></div>
      </div>

      <div v-else-if="helperContacts.length === 0" class="empty py-4 text-center">
        <p class="small muted">Belum ada kontak helper aktif yang tercatat di sistem.</p>
      </div>

      <div v-else class="helpers-grid">
        <div
          v-for="c in helperContacts"
          :key="c.id"
          class="helper-card"
        >
          <div class="helper-card-top">
            <div class="helper-channel-bubble" :class="`channel-${c.channel}`">
              <MessageCircle v-if="c.channel === 'whatsapp'" :size="22" />
              <Mail v-else-if="c.channel === 'email'" :size="22" />
              <PhoneCall v-else-if="c.channel === 'phone'" :size="22" />
              <Send v-else-if="c.channel === 'telegram'" :size="22" />
              <Headphones v-else :size="22" />
            </div>
            <span class="helper-channel-pill" :class="`pill-${c.channel}`">
              {{ getChannelLabel(c.channel) }}
            </span>
          </div>

          <h3 class="helper-card-name">{{ c.name }}</h3>
          <span v-if="c.role_or_title" class="helper-card-role">{{ c.role_or_title }}</span>

          <div v-if="c.available_hours" class="helper-card-hours">
            <Clock :size="12" />
            <span>{{ c.available_hours }}</span>
          </div>

          <p v-if="c.description" class="helper-card-desc">{{ c.description }}</p>

          <div class="helper-card-action">
            <a
              :href="getContactLink(c)"
              :target="c.channel === 'phone' ? '_self' : '_blank'"
              class="btn btn-block btn-contact"
              :class="`btn-${c.channel}`"
            >
              <MessageCircle v-if="c.channel === 'whatsapp'" :size="16" />
              <Mail v-else-if="c.channel === 'email'" :size="16" />
              <PhoneCall v-else-if="c.channel === 'phone'" :size="16" />
              <Send v-else-if="c.channel === 'telegram'" :size="16" />
              <Headphones v-else :size="16" />
              <span>{{ getActionBtnText(c) }}: {{ c.contact_value }}</span>
              <ExternalLink v-if="c.channel !== 'phone'" :size="13" style="margin-left: 2px;" />
            </a>
          </div>
        </div>
      </div>
    </div>

    <!-- Gamification Rules Summary Card -->
    <div class="card card-pad card-dark mb-6">
      <div class="row" style="gap: 12px; margin-bottom: 16px;">
        <Sparkles :size="24" class="text-white" />
        <div>
          <h2 class="card-title text-white">Points & Gamification Guide</h2>
          <p class="small opacity-90">Learn how to maximize your points and accelerate your level progression on GrowHub.</p>
        </div>
      </div>

      <div class="rules-table-wrapper">
        <table class="rules-table">
          <thead>
            <tr>
              <th>Employee Activity</th>
              <th>Points Earned</th>
              <th>Description</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>First Daily Activity (Daily Login)</td>
              <td><span class="bold text-green-light">+2 Points</span></td>
              <td>Keeps your active daily streak going</td>
            </tr>
            <tr>
              <td>Submit Content for Review</td>
              <td><span class="bold text-green-light">+5 Points</span></td>
              <td>Awarded once content enters the curation queue</td>
            </tr>
            <tr>
              <td>Content Approved & Published</td>
              <td><span class="bold text-green-light">+25 Points</span></td>
              <td>Validated and published company-wide</td>
            </tr>
            <tr>
              <td>Receive Likes from Colleagues</td>
              <td><span class="bold text-green-light">+2 Points</span></td>
              <td>For each peer who likes your contribution</td>
            </tr>
            <tr>
              <td>Post a Helpful Comment</td>
              <td><span class="bold text-green-light">+1 Point</span></td>
              <td>Encouraging constructive and professional discussion</td>
            </tr>
            <tr>
              <td>Receive Comments on Your Content</td>
              <td><span class="bold text-green-light">+3 Points</span></td>
              <td>When your content triggers active peer discussions</td>
            </tr>
            <tr>
              <td>Complete Learning Center Module</td>
              <td><span class="bold text-green-light">+15 to +35 Points</span></td>
              <td>Based on module duration and complexity</td>
            </tr>
            <tr>
              <td>7-Day Streak Milestone Bonus</td>
              <td><span class="bold text-green-light">+15 Bonus Points</span></td>
              <td>Automatically awarded for every 7 consecutive days active 🔥</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- FAQ Accordion -->
    <div class="card card-pad mb-6">
      <div class="card-head">
        <h2 class="card-title">Frequently Asked Questions (FAQ)</h2>
      </div>

      <div v-if="loading" class="faq-list stack" aria-label="Loading FAQ">
        <div v-for="n in 4" :key="`sk-faq-${n}`" class="faq-item">
          <div class="faq-question">
            <div class="skeleton sk-pill" style="width: 78px; height: 22px;"></div>
            <div class="skeleton sk-line flex-1" style="max-width: 72%;"></div>
            <div class="skeleton sk-circle" style="width: 18px; height: 18px;"></div>
          </div>
        </div>
      </div>

      <div v-else class="faq-list stack">
        <div
          v-for="(faq, i) in faqs"
          :key="i"
          class="faq-item"
          :class="{ 'faq-open': openFaq === i }"
          @click="openFaq = openFaq === i ? null : i"
        >
          <div class="faq-question">
            <span class="badge badge-gray small">{{ faq.group }}</span>
            <span class="bold small text-dark flex-1">{{ faq.q }}</span>
            <ChevronDown :size="18" class="faq-chevron" />
          </div>
          <div v-if="openFaq === i" class="faq-answer">
            {{ faq.a }}
          </div>
        </div>
      </div>
    </div>

    <!-- Submit Ticket & Ticket History Split -->
    <div class="grid grid-2">
      <!-- Ticket Form -->
      <div class="card card-pad">
        <h2 class="card-title mb-2">Submit Support Ticket</h2>
        <p class="small text-2 mb-3">Encountering account or system issues, or need curation support? We're here to help.</p>

        <form @submit.prevent="submitTicket" class="stack">
          <div class="field">
            <label class="label">Issue Subject <span class="text-red">*</span></label>
            <input
              v-model="ticketForm.subject"
              type="text"
              placeholder="e.g. Issue uploading tutorial video..."
              class="input"
              required
            />
          </div>

          <div class="field">
            <label class="label">Category</label>
            <select v-model="ticketForm.category" class="select">
              <option value="general">General</option>
              <option value="account">Account & NIP</option>
              <option value="content">Curation & Content</option>
              <option value="technical">Technical Issue</option>
              <option value="suggestion">Suggestions & Feedback</option>
            </select>
          </div>

          <div class="field">
            <label class="label">Message / Detailed Description <span class="text-red">*</span></label>
            <textarea
              v-model="ticketForm.message"
              rows="4"
              placeholder="Clearly describe the issue you are experiencing..."
              class="textarea"
              required
            ></textarea>
          </div>

          <button type="submit" class="btn btn-primary" :disabled="submittingTicket">
            <span v-if="submittingTicket" class="spinner"></span>
            <Send v-else :size="16" />
            <span>Submit Ticket</span>
          </button>
        </form>
      </div>

      <!-- Ticket History -->
      <div class="card card-pad">
        <h2 class="card-title mb-2">My Ticket History</h2>
        <p class="small text-2 mb-3">Review resolution status of your submitted tickets.</p>

        <div v-if="loading" class="tickets-list stack" aria-label="Loading ticket history">
          <div v-for="n in 3" :key="`sk-ticket-${n}`" class="ticket-item stack" style="gap: 8px;">
            <div class="row-between">
              <div class="skeleton sk-line" style="width: 56%;"></div>
              <div class="skeleton sk-pill" style="width: 72px; height: 22px;"></div>
            </div>
            <div class="skeleton sk-line-sm" style="width: 94%;"></div>
            <div class="skeleton sk-line-sm" style="width: 68%;"></div>
          </div>
        </div>

        <div v-else-if="tickets.length > 0" class="tickets-list stack">
          <div
            v-for="t in tickets"
            :key="t.id"
            class="ticket-item"
          >
            <div class="row-between mb-1">
              <span class="bold small text-dark truncate">{{ t.subject }}</span>
              <span class="badge" :class="t.status === 'resolved' ? 'badge-primary' : 'badge-amber'">
                {{ t.status }}
              </span>
            </div>
            <p class="small text-2 clamp-2">{{ t.message }}</p>
            <div v-if="t.response" class="ticket-response mt-2">
              <span class="small bold text-primary">Team Response:</span>
              <p class="small text-dark mt-1">{{ t.response }}</p>
            </div>
          </div>
        </div>

        <div v-else class="empty py-4">
          <p class="small muted">You haven't submitted any support tickets yet.</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import {
  Sparkles, ChevronDown, Send, Headphones, MessageCircle,
  Mail, PhoneCall, ExternalLink, Clock, Settings
} from 'lucide-vue-next'
import api from '@/api/client'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'

const authStore = useAuthStore()
const toastStore = useToastStore()

const loading = ref(true)
const faqs = ref([])
const tickets = ref([])
const openFaq = ref(0)
const submittingTicket = ref(false)

// Helper Contacts
const helperContacts = ref([])
const helpersLoading = ref(true)

const fetchHelpers = async () => {
  helpersLoading.value = true
  try {
    const res = await api.get('/support/helpers')
    helperContacts.value = res.data.helpers || []
  } catch (err) {
    console.error('Failed to load helpers', err)
  } finally {
    helpersLoading.value = false
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

const getActionBtnText = (contact) => {
  switch (contact.channel) {
    case 'whatsapp': return 'Chat WhatsApp'
    case 'email': return 'Kirim Email'
    case 'phone': return 'Hubungi Telepon'
    case 'telegram': return 'Chat Telegram'
    default: return 'Hubungi'
  }
}

const getContactLink = (contact) => {
  if (contact.channel === 'whatsapp') {
    const digits = (contact.contact_value || '').replace(/\D/g, '')
    const phone = digits.startsWith('0') ? '62' + digits.slice(1) : digits
    return `https://wa.me/${phone}?text=Halo%20Admin%20GrowHub,%20saya%20membutuhkan%20bantuan%20terkait%20layanan%20GrowHub.`
  }
  if (contact.channel === 'email') {
    return `mailto:${contact.contact_value}?subject=Bantuan%20Layanan%20GrowHub`
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

const ticketForm = ref({
  subject: '',
  category: 'general',
  message: '',
})

const fetchSupport = async () => {
  loading.value = true
  try {
    const [faqRes, tickRes] = await Promise.all([
      api.get('/support/faqs'),
      api.get('/support/tickets'),
    ])
    faqs.value = faqRes.data
    tickets.value = tickRes.data
  } catch (err) {
    console.error('Failed support data', err)
  } finally {
    loading.value = false
  }
}

const submitTicket = async () => {
  if (!ticketForm.value.subject || !ticketForm.value.message) return
  submittingTicket.value = true
  try {
    await api.post('/support/tickets', ticketForm.value)
    toastStore.success('Support ticket submitted successfully!')
    ticketForm.value = { subject: '', category: 'general', message: '' }
    await fetchSupport()
  } catch (err) {
    toastStore.error('Failed to submit ticket.')
  } finally {
    submittingTicket.value = false
  }
}

onMounted(() => {
  fetchSupport()
  fetchHelpers()
})
</script>

<style scoped src="@/styles/features/help-support.css"></style>
