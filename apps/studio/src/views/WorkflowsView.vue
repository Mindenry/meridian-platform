<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { GitBranch, Clock3, CheckCircle2, XCircle, ExternalLink, Save } from 'lucide-vue-next'
import PageHead from '@/components/PageHead.vue'
import StatusBadge from '@/components/StatusBadge.vue'
import BaseModal from '@/components/BaseModal.vue'
import EmptyState from '@/components/EmptyState.vue'
import { api, apiErrorMessage } from '@/api/client'
import { auth } from '@/stores/auth'
import { toast } from '@/stores/toast'

const loading = ref(true)
const tab = ref<'pending' | 'definitions' | 'audit'>('pending')
const data = ref<any>(null)
const audit = ref<any[]>([])
const editOpen = ref(false)
const selectedDefinition = ref<any>(null)
const steps = ref<Array<{ name: string; permission: string }>>([])
const saving = ref(false)

async function load() {
  loading.value = true
  try {
    const [workflowResponse, auditResponse] = await Promise.all([
      api.get('/workflows'),
      auth.can('audit.view') ? api.get('/audit-logs') : Promise.resolve({ data: { data: [] } }),
    ])
    data.value = workflowResponse.data.data
    audit.value = auditResponse.data.data
  } finally {
    loading.value = false
  }
}

function openDefinition(definition: any) {
  selectedDefinition.value = definition
  steps.value = JSON.parse(JSON.stringify(definition.steps || []))
  editOpen.value = true
}

function addStep() {
  steps.value.push({ name: 'New approval step', permission: 'pages.approve' })
}

async function saveDefinition() {
  if (!selectedDefinition.value) return
  saving.value = true
  try {
    await api.patch(`/workflows/definitions/${selectedDefinition.value.id}`, { steps: steps.value })
    toast.success('Workflow definition saved')
    editOpen.value = false
    await load()
  } catch (err) {
    toast.error('Could not save workflow', apiErrorMessage(err))
  } finally {
    saving.value = false
  }
}

function formatDate(value?: string | null) {
  if (!value) return '—'
  return new Intl.DateTimeFormat('en-GB', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }).format(new Date(value))
}

onMounted(load)
</script>

<template>
  <div>
    <PageHead eyebrow="GOVERNANCE" title="Workflows" description="Review instances, approval history, workflow definitions, and the site audit trail.">
      <template #actions>
        <span class="badge badge--in_review">{{ data?.pending ?? 0 }} awaiting approval</span>
      </template>
    </PageHead>

    <div class="editor-tabs">
      <button type="button" :class="{ 'is-active': tab === 'pending' }" @click="tab = 'pending'">Review queue</button>
      <button type="button" :class="{ 'is-active': tab === 'definitions' }" @click="tab = 'definitions'">Definitions</button>
      <button type="button" :class="{ 'is-active': tab === 'audit' }" @click="tab = 'audit'">Audit trail</button>
    </div>

    <div v-if="loading" class="skeleton" style="height:360px" />
    <template v-else>
      <template v-if="tab === 'pending'">
        <EmptyState v-if="!data?.instances?.length" title="No review instances" body="Submit a page for review from the page editor to start a workflow." />
        <div v-else class="stack-s">
          <article v-for="instance in data.instances" :key="instance.id" class="panel">
            <div class="panel__body">
              <div class="flex-between">
                <div>
                  <p class="instance-title">{{ instance.title || 'Untitled page' }}</p>
                  <p class="mono faint">INSTANCE {{ instance.id }} · SUBMITTED {{ formatDate(instance.submitted_at) }}</p>
                </div>
                <div class="flex-s">
                  <StatusBadge :status="instance.status" />
                  <RouterLink :to="`/pages/${instance.page_id}`" class="btn-s btn-s--sm">Open page <ExternalLink :size="11" /></RouterLink>
                </div>
              </div>
              <div class="workflow-trail">
                <div v-for="(step, index) in instance.steps" :key="index" class="trail-step" :class="{ 'is-current': instance.status === 'in_review' && instance.current_step === index, 'is-done': instance.status === 'completed' || instance.current_step > index }">
                  <span class="trail-dot" />
                  <span>{{ step.name }}</span>
                </div>
              </div>
              <div v-if="instance.approvals?.length" class="approvals">
                <div v-for="approval in instance.approvals" :key="approval.decided_at + approval.step_name" class="approval">
                  <span class="approval-icon">
                    <CheckCircle2 v-if="approval.decision === 'approved'" :size="12" style="color:var(--s-green)" />
                    <XCircle v-else-if="approval.decision === 'changes_requested'" :size="12" style="color:var(--s-red)" />
                    <Clock3 v-else :size="12" style="color:var(--s-amber)" />
                  </span>
                  <div><p>{{ approval.step_name }} · {{ approval.decision }}</p><p class="mono faint">{{ approval.user || 'System' }} · {{ formatDate(approval.decided_at) }}</p><p v-if="approval.note" class="approval-note">{{ approval.note }}</p></div>
                </div>
              </div>
            </div>
          </article>
        </div>
      </template>

      <template v-else-if="tab === 'definitions'">
        <div class="s-two-col">
          <div class="stack-s">
            <article v-for="definition in data?.definitions" :key="definition.id" class="panel">
              <div class="panel__body">
                <div class="flex-between"><p class="instance-title">{{ definition.name }} <span v-if="definition.is_default" class="badge badge--accent">DEFAULT</span></p><button v-if="auth.can('workflow.manage')" type="button" class="btn-s btn-s--sm" @click="openDefinition(definition)">Edit steps</button></div>
                <div class="definition-steps"><span v-for="(step, index) in definition.steps" :key="index">{{ Number(index) + 1 }}. {{ step.name }}</span></div>
                <p class="mono faint">{{ definition.instances_count }} historical instance(s)</p>
              </div>
            </article>
          </div>
          <section class="panel"><div class="panel__head"><p class="panel__title">How it works</p></div><div class="panel__body" style="font-size: 12px;line-height:1.6;color:var(--s-muted)">
            <p>Draft → In review → Approved → Published. A reviewer can request changes instead of approving. Every transition is recorded in the workflow instance, audit log, and notifications.</p>
            <p style="margin-top:8px">Scheduled publication is processed by the scheduler and is idempotent: a second run cannot publish twice.</p>
          </div></section>
        </div>
      </template>

      <template v-else>
        <div class="panel">
          <div class="panel__head"><p class="panel__title">Audit trail</p><span class="mono faint">{{ audit.length }} recent events</span></div>
          <div v-if="!audit.length" class="table-empty">No audit events available for this role.</div>
          <div v-else class="table-wrap">
            <table class="table-s">
              <thead><tr><th>Action</th><th>Description</th><th>User</th><th>When</th></tr></thead>
              <tbody><tr v-for="log in audit" :key="log.id"><td class="mono" style="font-size: 12px">{{ log.action }}</td><td>{{ log.description }}</td><td class="muted">{{ log.user }}</td><td class="muted nowrap">{{ formatDate(log.created_at) }}</td></tr></tbody>
            </table>
          </div>
        </div>
      </template>
    </template>

    <BaseModal :open="editOpen" title="Workflow definition" :wide="true" @close="editOpen = false">
      <div class="stack-s">
        <p class="mono faint">Steps run in order. Each approval records a user and note.</p>
        <div v-for="(step, index) in steps" :key="index" class="step-row">
          <span class="mono">{{ index + 1 }}</span>
          <input v-model="step.name" class="input-s" placeholder="Step name">
          <select v-model="step.permission" class="select-s" style="width:170px"><option value="pages.approve">pages.approve</option><option value="articles.approve">articles.approve</option></select>
          <button type="button" class="btn-s btn-s--ghost btn-s--icon btn-s--sm" @click="steps.splice(index, 1)"><XCircle :size="13" /></button>
        </div>
        <button type="button" class="btn-s btn-s--sm" @click="addStep">+ Add step</button>
      </div>
      <template #footer>
        <button type="button" class="btn-s" @click="editOpen = false">Cancel</button>
        <button type="button" class="btn-s btn-s--primary" :disabled="saving" @click="saveDefinition"><Save :size="12" /> {{ saving ? 'Saving…' : 'Save definition' }}</button>
      </template>
    </BaseModal>
  </div>
</template>

<style scoped>
.editor-tabs { display: flex; gap: 2px; border-bottom: 1px solid var(--s-line); margin-bottom: 14px; }
.editor-tabs button { border: 0; background: transparent; padding: 8px 12px; font-size: 12px; color: var(--s-muted); border-bottom: 2px solid transparent; }
.editor-tabs button.is-active { color: var(--s-ink); border-bottom-color: var(--s-ink); font-weight: 650; }
.instance-title { font-size: 14px; font-weight: 650; display: flex; gap: 7px; align-items: center; }
.workflow-trail { display: flex; gap: 18px; flex-wrap: wrap; margin-top: 12px; border-top: 1px solid var(--s-line); padding-top: 10px; }
.trail-step { display: flex; align-items: center; gap: 6px; font-size: 12px; color: var(--s-faint); }
.trail-step.is-current { color: var(--s-amber); font-weight: 600; }
.trail-step.is-done { color: var(--s-green); }
.trail-dot { width: 6px; height: 6px; border-radius: 50%; border: 1px solid currentColor; }
.trail-step.is-current .trail-dot { background: var(--s-amber); }
.trail-step.is-done .trail-dot { background: var(--s-green); }
.approvals { margin-top: 10px; border-top: 1px solid var(--s-line); padding-top: 7px; }
.approval { display: flex; gap: 8px; padding: 6px 0; border-bottom: 1px solid var(--s-line); font-size: 12px; }
.approval:last-child { border-bottom: 0; }
.approval-icon { margin-top: 1px; }
.approval-note { margin-top: 3px; color: var(--s-muted); line-height: 1.4; }
.definition-steps { display: flex; flex-wrap: wrap; gap: 6px; margin: 8px 0; font-size: 12px; color: var(--s-muted); }
.definition-steps span { border: 1px solid var(--s-line); border-radius: 3px; padding: 4px 6px; }
.step-row { display: grid; grid-template-columns: 20px 1fr 170px 30px; gap: 6px; align-items: center; }
@media (max-width: 700px) { .step-row { grid-template-columns: 20px 1fr 30px; } .step-row select { grid-column: 2; } }
</style>
