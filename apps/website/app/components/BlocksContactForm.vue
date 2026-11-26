<script setup lang="ts">
import type { Block, FormDefinition, FormField } from '~/types'

const props = defineProps<{ block: Block }>()
const { localeCode } = useLocale()
const api = useApi()
const route = useRoute()
const p = computed(() => props.block.props)
const form = computed<FormDefinition | null>(() => props.block.resolved?.form ?? null)
const fields = computed<FormField[]>(() => form.value?.fields ?? [])
const departments = computed(() => p.value.departments ?? [])

const values = reactive<Record<string, any>>({})
const errors = reactive<Record<string, string>>({})
const sending = ref(false)
const success = ref<string | null>(null)
const successReference = ref<string | null>(null)
const startedAt = ref(Date.now())

const labels = computed(() => ({
  submit: localeCode.value === 'th' ? 'ส่งข้อความ' : localeCode.value === 'zh-CN' ? '发送消息' : 'Send enquiry',
  sending: localeCode.value === 'th' ? 'กำลังส่ง…' : localeCode.value === 'zh-CN' ? '发送中…' : 'Sending…',
  again: localeCode.value === 'th' ? 'ส่งอีกข้อความ' : localeCode.value === 'zh-CN' ? '再发送一条' : 'Send another message',
  required: localeCode.value === 'th' ? 'จำเป็น' : localeCode.value === 'zh-CN' ? '必填' : 'Required',
  error: localeCode.value === 'th' ? 'เกิดข้อผิดพลาด กรุณาลองใหม่' : localeCode.value === 'zh-CN' ? '出现问题，请重试。' : 'Something went wrong. Please try again.',
  department: localeCode.value === 'th' ? 'แผนกที่เกี่ยวข้อง' : localeCode.value === 'zh-CN' ? '相关部门' : 'Related teams',
}))

function localize(value: any): string {
  if (!value) return ''
  if (typeof value === 'string') return value
  return value[localeCode.value] || value.en || ''
}

function initialize() {
  for (const field of fields.value) {
    if (field.type === 'consent' || field.type === 'checkbox') {
      values[field.name] = false
    } else if (field.type === 'select' && field.options?.length) {
      values[field.name] = ''
    } else {
      values[field.name] = ''
    }
  }
  startedAt.value = Date.now()
}

watch(fields, initialize, { immediate: true })

async function submit() {
  if (!form.value) return
  sending.value = true
  success.value = null
  Object.keys(errors).forEach((key) => delete errors[key])

  try {
    const response = await api.post<{ message: string; reference: string }>(
      `/public/forms/${form.value.key}/submissions`,
      {
        ...values,
        consent: values.consent === true,
        website: '',
        elapsed_ms: Math.max(1500, Date.now() - startedAt.value),
        page: route.fullPath,
      },
    )
    success.value = response.message
    successReference.value = response.reference
    initialize()
  } catch (error: any) {
    const serverErrors = error?.data?.errors
    if (serverErrors) {
      for (const [key, messages] of Object.entries(serverErrors)) {
        errors[key] = Array.isArray(messages) ? String(messages[0]) : String(messages)
      }
    }
    errors._form = error?.data?.message || labels.value.error
  } finally {
    sending.value = false
  }
}

function reset() {
  success.value = null
  successReference.value = null
  initialize()
}
</script>

<template>
  <section class="section contact-form" :class="`contact-form--${p.theme || 'light'}`">
    <div class="container">
      <div class="contact-form__grid">
        <div class="contact-form__intro" data-reveal>
          <p v-if="p.eyebrow" class="eyebrow">{{ p.eyebrow }}</p>
          <h2 class="h2 contact-form__heading">{{ p.heading }}</h2>
          <p v-if="p.body" class="contact-form__body">{{ p.body }}</p>

          <div v-if="departments.length" class="contact-form__departments">
            <p class="mono contact-form__departments-label">{{ labels.department }}</p>
            <div v-for="department in departments" :key="department.email" class="department">
              <p class="department__name">{{ localize(department.name) }}</p>
              <p class="department__description">{{ localize(department.description) }}</p>
              <a :href="`mailto:${department.email}`" class="department__email">{{ department.email }}</a>
            </div>
          </div>
        </div>

        <div class="contact-form__panel" data-reveal>
          <div v-if="success" class="form-success" role="status">
            <span class="form-success__icon"><AppIcon name="check" :size="22" /></span>
            <h3 class="h3">Message received.</h3>
            <p>{{ success }}</p>
            <p v-if="successReference" class="mono">REFERENCE {{ successReference }}</p>
            <button type="button" class="btn btn-ghost" @click="reset">{{ labels.again }}</button>
          </div>

          <form v-else-if="form" class="form" novalidate @submit.prevent="submit">
            <div class="form-grid">
              <div
                v-for="field in fields"
                :key="field.name"
                class="field"
                :class="{
                  'field--full': field.width === 'full' || field.type === 'textarea' || field.type === 'consent',
                  'field--consent': field.type === 'consent',
                }"
              >
                <label :for="`field-${field.name}`">
                  {{ localize(field.label) }}
                  <span v-if="field.required" class="field__required">*</span>
                </label>

                <input
                  v-if="['text', 'email', 'tel', 'phone', 'number', 'date'].includes(field.type)"
                  :id="`field-${field.name}`"
                  v-model="values[field.name]"
                  :type="field.type === 'phone' ? 'tel' : field.type"
                  :name="field.name"
                  :placeholder="localize(field.placeholder)"
                  :required="field.required"
                  :autocomplete="field.type === 'email' ? 'email' : field.type === 'tel' || field.type === 'phone' ? 'tel' : 'off'"
                  class="input"
                  :aria-invalid="!!errors[field.name]"
                  :aria-describedby="errors[field.name] ? `error-${field.name}` : undefined"
                >

                <textarea
                  v-else-if="field.type === 'textarea'"
                  :id="`field-${field.name}`"
                  v-model="values[field.name]"
                  :name="field.name"
                  :placeholder="localize(field.placeholder)"
                  :required="field.required"
                  class="textarea"
                  rows="5"
                  :aria-invalid="!!errors[field.name]"
                  :aria-describedby="errors[field.name] ? `error-${field.name}` : undefined"
                />

                <select
                  v-else-if="field.type === 'select'"
                  :id="`field-${field.name}`"
                  v-model="values[field.name]"
                  :name="field.name"
                  :required="field.required"
                  class="select"
                  :aria-invalid="!!errors[field.name]"
                >
                  <option value="" disabled>—</option>
                  <option v-for="option in field.options" :key="option.value" :value="option.value">
                    {{ localize(option.label) }}
                  </option>
                </select>

                <div v-else-if="field.type === 'radio'" class="radio-group">
                  <label v-for="option in field.options" :key="option.value" class="radio-row">
                    <input v-model="values[field.name]" type="radio" :name="field.name" :value="option.value">
                    <span>{{ localize(option.label) }}</span>
                  </label>
                </div>

                <label v-else-if="field.type === 'consent'" class="checkbox-row">
                  <input
                    :id="`field-${field.name}`"
                    v-model="values[field.name]"
                    type="checkbox"
                    :name="field.name"
                    :required="field.required"
                  >
                  <span>{{ localize(field.label) }}</span>
                </label>

                <label v-else-if="field.type === 'checkbox'" class="checkbox-row">
                  <input v-model="values[field.name]" type="checkbox" :name="field.name">
                  <span>{{ localize(field.label) }}</span>
                </label>

                <p v-if="errors[field.name]" :id="`error-${field.name}`" class="form-error" role="alert">{{ errors[field.name] }}</p>
              </div>
            </div>

            <div class="honeypot" aria-hidden="true">
              <label for="website">Website</label>
              <input id="website" v-model="values.website" type="text" tabindex="-1" autocomplete="off">
            </div>

            <div class="form-actions">
              <ButtonLink :label="sending ? labels.sending : labels.submit" to="#" icon @click.prevent="submit" />
              <p class="form-privacy">
                {{ localeCode === 'th' ? 'ข้อมูลของคุณถูกใช้เพื่อตอบข้อสอบถามเท่านั้น' : localeCode === 'zh-CN' ? '您的信息仅用于回复本次咨询。' : 'Your details are used only to respond to this enquiry.' }}
              </p>
            </div>

            <p v-if="errors._form" class="form-error form-error--global" role="alert">{{ errors._form }}</p>
          </form>
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
.contact-form {
  background: var(--ivory);
}

.contact-form--muted {
  background: #e7e1d5;
}

.contact-form--dark {
  background: var(--ink);
  color: var(--ivory);
}

.contact-form__grid {
  display: grid;
  grid-template-columns: repeat(12, minmax(0, 1fr));
  gap: clamp(2rem, 5vw, 6rem);
}

.contact-form__intro {
  grid-column: 1 / span 5;
}

.contact-form__heading {
  margin-top: 1.4rem;
  max-width: 14ch;
}

.contact-form__body {
  margin-top: 1.5rem;
  max-width: 42ch;
  font-size: 0.95rem;
  line-height: 1.65;
  color: var(--steel-dark);
}

.contact-form--dark .contact-form__body {
  color: rgba(241, 237, 228, 0.6);
}

.contact-form__departments {
  margin-top: 3rem;
  border-top: 1px solid var(--line);
  padding-top: 1.5rem;
}

.contact-form__departments-label {
  color: var(--steel);
  margin-bottom: 1rem;
}

.department {
  padding-block: 1rem;
  border-bottom: 1px solid var(--line);
}

.department__name {
  font-size: 0.95rem;
  font-weight: 600;
}

.department__description {
  margin-top: 0.25rem;
  font-size: 0.8rem;
  color: var(--steel-dark);
}

.department__email {
  display: inline-block;
  margin-top: 0.45rem;
  font-size: 0.82rem;
  border-bottom: 1px solid var(--accent);
}

.contact-form__panel {
  grid-column: 7 / span 6;
}

.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.25rem;
}

.field--full {
  grid-column: 1 / -1;
}

.field--consent label:first-child {
  display: none;
}

.field__required {
  color: var(--accent);
}

.radio-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.radio-row {
  display: flex;
  gap: 0.6rem;
  align-items: center;
  font-size: 0.9rem;
}

.radio-row input {
  accent-color: var(--accent);
}

.honeypot {
  position: absolute;
  left: -10000px;
  width: 1px;
  height: 1px;
  overflow: hidden;
}

.form-actions {
  margin-top: 1.75rem;
  display: flex;
  align-items: center;
  gap: 1.5rem;
  flex-wrap: wrap;
}

.form-privacy {
  font-size: 0.75rem;
  line-height: 1.5;
  color: var(--steel);
  max-width: 35ch;
}

.form-error--global {
  margin-top: 1rem;
}

.form-success {
  border: 1px solid var(--line);
  padding: clamp(1.5rem, 3vw, 2.5rem);
  display: flex;
  flex-direction: column;
  gap: 1rem;
  align-items: flex-start;
}

.form-success__icon {
  width: 44px;
  height: 44px;
  display: grid;
  place-items: center;
  border: 1px solid var(--accent);
  color: var(--accent);
}

.form-success :deep(.btn) {
  margin-top: 0.5rem;
}

@media (max-width: 900px) {
  .contact-form__intro,
  .contact-form__panel {
    grid-column: 1 / -1;
  }

  .contact-form__panel {
    margin-top: 1rem;
  }
}

@media (max-width: 560px) {
  .form-grid {
    grid-template-columns: 1fr;
  }

  .form-actions {
    align-items: flex-start;
    flex-direction: column;
  }

  .form-actions :deep(.btn) {
    width: 100%;
  }
}
</style>
