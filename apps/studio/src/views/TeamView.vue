<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { Plus, ShieldCheck, UserCog, Copy, Check, Users } from 'lucide-vue-next'
import PageHead from '@/components/PageHead.vue'
import BaseModal from '@/components/BaseModal.vue'
import BaseDrawer from '@/components/BaseDrawer.vue'
import { api, apiErrorMessage, apiValidationErrors } from '@/api/client'
import { auth } from '@/stores/auth'
import { toast } from '@/stores/toast'

const loading = ref(true)
const users = ref<any[]>([])
const roles = ref<any[]>([])
const permissions = ref<Record<string, any[]>>({})
const inviteOpen = ref(false)
const editOpen = ref(false)
const tempPassword = ref('')
const copied = ref(false)
const saving = ref(false)
const errors = ref<Record<string, string>>({})
const inviteForm = ref({ name: '', email: '', job_title: '', role_slug: 'content_editor', locale: 'en' })
const selectedUser = ref<any>(null)
const editForm = ref({ name: '', job_title: '', is_active: true, role_slugs: [] as string[] })

const canManage = computed(() => auth.can('users.manage'))
const assignableRoles = computed(() => roles.value.filter((role) => role.slug !== 'super_admin' || auth.user.value?.is_super_admin))

async function load() {
  loading.value = true
  try {
    const { data } = await api.get('/users')
    users.value = data.data
    roles.value = data.roles
    permissions.value = data.permissions
  } finally {
    loading.value = false
  }
}

async function invite() {
  saving.value = true
  errors.value = {}
  try {
    const { data } = await api.post('/users/invite', inviteForm.value)
    tempPassword.value = data.temporary_password
    toast.success('Team member created')
    inviteOpen.value = false
    await load()
  } catch (err) {
    errors.value = apiValidationErrors(err)
    toast.error('Could not invite user', apiErrorMessage(err))
  } finally {
    saving.value = false
  }
}

function openEdit(user: any) {
  selectedUser.value = user
  editForm.value = {
    name: user.name,
    job_title: user.job_title || '',
    is_active: user.is_active,
    role_slugs: user.roles.filter((r: any) => r.slug !== 'super_admin' || user.is_super_admin).map((r: any) => r.slug),
  }
  editOpen.value = true
}

async function saveUser() {
  if (!selectedUser.value) return
  saving.value = true
  try {
    await api.patch(`/users/${selectedUser.value.id}`, editForm.value)
    toast.success('Access updated')
    editOpen.value = false
    await load()
  } catch (err) {
    toast.error('Could not update user', apiErrorMessage(err))
  } finally {
    saving.value = false
  }
}

function toggleRole(slug: string) {
  editForm.value.role_slugs = editForm.value.role_slugs.includes(slug)
    ? editForm.value.role_slugs.filter((item) => item !== slug)
    : [...editForm.value.role_slugs, slug]
}

async function copyPassword() {
  await navigator.clipboard.writeText(tempPassword.value)
  copied.value = true
  setTimeout(() => { copied.value = false }, 1800)
}

function formatDate(value?: string | null) {
  if (!value) return 'Never'
  return new Intl.DateTimeFormat('en-GB', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }).format(new Date(value))
}

onMounted(load)
</script>

<template>
  <div>
    <PageHead eyebrow="ORGANISATION" title="Team & access" description="Role-based access scoped to this website. Permissions are enforced on the backend for every request.">
      <template #actions>
        <button v-if="canManage" type="button" class="btn-s btn-s--primary" @click="inviteOpen = true"><Plus :size="14" /> Invite team member</button>
      </template>
    </PageHead>

    <div class="s-two-col">
      <div class="panel">
        <div class="panel__head"><p class="panel__title">People</p><span class="mono faint">{{ users.length }}</span></div>
        <div v-if="loading" class="panel__body"><div v-for="i in 5" :key="i" class="skeleton" style="height:42px;margin-bottom:7px" /></div>
        <div v-else class="table-wrap">
          <table class="table-s">
            <thead><tr><th>Person</th><th>Roles</th><th>Last sign-in</th><th>Status</th><th /></tr></thead>
            <tbody>
              <tr v-for="user in users" :key="user.id">
                <td>
                  <div class="person">
                    <span class="avatar" style="background:var(--s-ink);color:#fff">{{ user.initials }}</span>
                    <div><div class="table-s__title">{{ user.name }} <span v-if="user.is_super_admin" class="badge badge--accent">SUPER</span></div><div class="table-s__sub">{{ user.email }}</div></div>
                  </div>
                </td>
                <td><span v-for="role in user.roles" :key="role.slug" class="badge badge--neutral" style="margin-right:3px">{{ role.name }}</span></td>
                <td class="muted nowrap">{{ formatDate(user.last_login_at) }}</td>
                <td><span class="badge" :class="user.is_active ? 'badge--published' : 'badge--archived'">{{ user.is_active ? 'Active' : 'Inactive' }}</span></td>
                <td><button v-if="canManage" type="button" class="btn-s btn-s--ghost btn-s--sm" @click="openEdit(user)"><UserCog :size="12" /> Edit</button></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="panel">
        <div class="panel__head"><p class="panel__title">Roles</p><ShieldCheck :size="14" /></div>
        <div class="panel__body stack-s">
          <div v-for="role in roles" :key="role.slug" class="role-block">
            <div class="flex-between"><strong>{{ role.name }}</strong><span class="mono faint">{{ role.permissions_count }} perms</span></div>
            <p class="role-block__desc">{{ role.description }}</p>
          </div>
        </div>
      </div>
    </div>

    <div v-if="tempPassword" class="temp-password">
      <div>
        <p class="mono" style="color:var(--s-accent)">TEMPORARY PASSWORD — SHOWN ONCE</p>
        <p>Deliver this through a secure channel. The user should change it after signing in.</p>
      </div>
      <div class="temp-password__value">
        <code>{{ tempPassword }}</code>
        <button type="button" class="btn-s btn-s--sm" @click="copyPassword"><Check v-if="copied" :size="12" /><Copy v-else :size="12" /> {{ copied ? 'Copied' : 'Copy' }}</button>
      </div>
      <button type="button" class="btn-s btn-s--ghost btn-s--sm" @click="tempPassword = ''">Dismiss</button>
    </div>

    <BaseModal :open="inviteOpen" title="Invite a team member" @close="inviteOpen = false">
      <div class="stack-s">
        <div class="two-fields-s">
          <div class="field-s"><label class="field-s__label">Name</label><input v-model="inviteForm.name" class="input-s"><p v-if="errors.name" class="error-s">{{ errors.name }}</p></div>
          <div class="field-s"><label class="field-s__label">Email</label><input v-model="inviteForm.email" type="email" class="input-s"><p v-if="errors.email" class="error-s">{{ errors.email }}</p></div>
        </div>
        <div class="field-s"><label class="field-s__label">Job title</label><input v-model="inviteForm.job_title" class="input-s"></div>
        <div class="field-s">
          <label class="field-s__label">Role</label>
          <select v-model="inviteForm.role_slug" class="select-s">
            <option v-for="role in assignableRoles" :key="role.slug" :value="role.slug">{{ role.name }}</option>
          </select>
        </div>
        <p class="mono faint">A one-time temporary password is generated and shown once.</p>
      </div>
      <template #footer>
        <button type="button" class="btn-s" @click="inviteOpen = false">Cancel</button>
        <button type="button" class="btn-s btn-s--primary" :disabled="saving || !inviteForm.name || !inviteForm.email" @click="invite">{{ saving ? 'Creating…' : 'Create account' }}</button>
      </template>
    </BaseModal>

    <BaseDrawer :open="editOpen" title="Edit access" @close="editOpen = false">
      <div v-if="selectedUser" class="stack-s">
        <div class="field-s"><label class="field-s__label">Name</label><input v-model="editForm.name" class="input-s"></div>
        <div class="field-s"><label class="field-s__label">Job title</label><input v-model="editForm.job_title" class="input-s"></div>
        <label class="checkbox-s"><input v-model="editForm.is_active" type="checkbox"><span>Account active</span></label>
        <div>
          <p class="mono muted" style="margin-bottom:8px">ROLES FOR THIS WEBSITE</p>
          <label v-for="role in assignableRoles" :key="role.slug" class="role-check">
            <input type="checkbox" :checked="editForm.role_slugs.includes(role.slug)" @change="toggleRole(role.slug)">
            <span><strong>{{ role.name }}</strong><small>{{ role.description }}</small></span>
          </label>
        </div>
        <p class="mono faint">Super administrator access is global and only assignable by another super administrator.</p>
      </div>
      <template #footer>
        <button type="button" class="btn-s" @click="editOpen = false">Cancel</button>
        <button type="button" class="btn-s btn-s--primary" :disabled="saving" @click="saveUser">{{ saving ? 'Saving…' : 'Save access' }}</button>
      </template>
    </BaseDrawer>
  </div>
</template>

<style scoped>
.person { display: flex; align-items: center; gap: 8px; }
.role-block { padding-bottom: 9px; border-bottom: 1px solid var(--s-line); }
.role-block:last-child { border-bottom: 0; }
.role-block strong { font-size: 12px; }
.role-block__desc { margin-top: 2px; font-size: 9.5px; color: var(--s-faint); line-height: 1.4; }
.two-fields-s { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.temp-password { margin-top: 14px; border: 1px solid var(--s-accent); border-radius: var(--s-radius); background: var(--s-accent-soft); padding: 12px; display: flex; gap: 16px; align-items: center; flex-wrap: wrap; }
.temp-password p { font-size: 12px; color: #7e4b34; margin-top: 2px; }
.temp-password__value { display: flex; align-items: center; gap: 6px; background: var(--s-white); border: 1px solid #efd0c2; padding: 5px 7px; }
.temp-password__value code { font-family: var(--font-mono); font-size: 12px; }
.role-check { display: flex; gap: 8px; padding: 7px 0; border-bottom: 1px solid var(--s-line); font-size: 12px; align-items: flex-start; }
.role-check input { margin-top: 2px; accent-color: var(--s-accent); }
.role-check strong { display: block; font-size: 12px; }
.role-check small { display: block; font-size: 12px; color: var(--s-faint); margin-top: 1px; }
@media (max-width: 560px) { .two-fields-s { grid-template-columns: 1fr; } }
</style>
