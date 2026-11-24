<template>
  <transition name="modal-fade">
    <div v-if="isOpen" class="ethics-modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="ethics-modal-title">
      <div class="ethics-modal-card">
        <!-- Header -->
        <div class="ethics-modal-header">
          <div class="ethics-header-icon">
            <ShieldAlert :size="24" />
          </div>
          <div class="ethics-header-text">
            <div class="ethics-badge-pill">
              <span>{{ isPreview ? 'Pratinjau Pop-up Login' : 'Wajib Dibaca' }}</span>
            </div>
            <h2 id="ethics-modal-title" class="ethics-modal-title">Pemberitahuan Etika Komunikasi</h2>
            <p class="ethics-modal-subtitle">
              Sebelum beraktivitas di GrowHub, harap pahami daftar pelanggaran yang tidak diperbolehkan beserta konsekuensi pengurangan poin berikut:
            </p>
          </div>
        </div>

        <!-- Body -->
        <div class="ethics-modal-body">
          <div v-if="loading" class="ethics-loading" aria-label="Loading ethics notices">
            <div v-for="n in 3" :key="`sk-eth-${n}`" class="ethics-item">
              <div class="skeleton sk-circle" style="width: 28px; height: 28px;"></div>
              <div class="ethics-item-content stack flex-1" style="gap: 6px;">
                <div class="skeleton sk-line" style="width: 56%;"></div>
                <div class="skeleton sk-line-sm" style="width: 92%;"></div>
              </div>
            </div>
          </div>

          <div v-else-if="items.length === 0" class="ethics-empty">
            <CheckCircle2 :size="28" class="text-primary" />
            <p class="bold text-dark">Belum ada daftar pelanggaran aktif.</p>
            <p class="small muted">Tetap jaga etika komunikasi, kerahasiaan data, dan profesionalisme selama menggunakan GrowHub.</p>
          </div>

          <div v-else class="ethics-list">
            <div
              v-for="(item, index) in items"
              :key="item.id || index"
              class="ethics-item"
            >
              <div class="ethics-item-num">{{ index + 1 }}</div>

              <div class="ethics-item-content">
                <div class="ethics-item-top">
                  <span class="ethics-item-title">{{ item.title }}</span>
                  <span class="ethics-point-pill">-{{ item.point_deduction || 1 }} Pts</span>
                </div>
                <p class="ethics-item-desc">{{ item.description }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="ethics-modal-footer">
          <div class="ethics-footer-note">
            <AlertTriangle :size="15" class="text-amber flex-shrink-0" />
            <span>Pelanggaran terhadap aturan di atas dapat dikenakan sanksi pengurangan poin hingga penonaktifan akun.</span>
          </div>

          <button
            type="button"
            class="btn btn-primary btn-lg ethics-ack-btn"
            @click="$emit('acknowledge')"
          >
            <CheckCircle2 :size="18" />
            <span>{{ isPreview ? 'Tutup Pratinjau' : 'Saya Mengerti & Setuju' }}</span>
          </button>
        </div>
      </div>
    </div>
  </transition>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { ShieldAlert, AlertTriangle, CheckCircle2 } from 'lucide-vue-next'
import api from '@/api/client'

const props = defineProps({
  isOpen: Boolean,
  previewPolicies: {
    type: Array,
    default: null,
  },
})

defineEmits(['acknowledge'])

const loading = ref(Boolean(props.isOpen && !Array.isArray(props.previewPolicies)))
const fetchedPolicies = ref([])

const isPreview = computed(() => Array.isArray(props.previewPolicies))
const items = computed(() => (isPreview.value ? props.previewPolicies : fetchedPolicies.value))

const loadPolicies = async () => {
  if (isPreview.value || !props.isOpen) return
  loading.value = true
  try {
    const res = await api.get('/ethics-policies')
    fetchedPolicies.value = res.data.policies || []
  } catch (err) {
    console.error('Failed to load ethics policies', err)
  } finally {
    loading.value = false
  }
}

watch(
  () => props.isOpen,
  (open) => {
    if (open && !isPreview.value) {
      loadPolicies()
    }
  }
)

onMounted(() => {
  if (props.isOpen && !isPreview.value) {
    loadPolicies()
  }
})
</script>

<style scoped src="@/styles/components/ethics-notice-modal.css"></style>

