<script setup lang="ts">
const props = defineProps<{ status: string; label?: string }>()

const labels: Record<string, string> = {
  draft: 'Draft',
  in_review: 'In review',
  changes_requested: 'Changes requested',
  approved: 'Approved',
  scheduled: 'Scheduled',
  published: 'Published',
  archived: 'Archived',
  new: 'New',
  contacted: 'Contacted',
  qualified: 'Qualified',
  proposal: 'Proposal',
  won: 'Won',
  lost: 'Lost',
  read: 'Read',
  spam: 'Spam',
  open: 'Open',
  closed: 'Closed',
  pending: 'Pending',
  completed: 'Completed',
  cancelled: 'Cancelled',
  failed: 'Failed',
  connected: 'Connected',
  unverified: 'Unverified',
  disconnected: 'Disconnected',
  configured: 'Configured',
  healthy: 'Healthy',
  attention: 'Attention',
  not_run: 'Not run',
}
</script>

<template>
  <span class="badge" :class="`badge--${status}`">{{ label || labels[status] || status }}</span>
</template>
