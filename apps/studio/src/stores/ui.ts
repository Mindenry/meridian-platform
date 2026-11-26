import { computed, reactive } from 'vue'

const state = reactive({
  sidebarCollapsed: window.localStorage.getItem('meridian-sidebar') === 'collapsed',
  commandOpen: false,
  notificationsOpen: false,
  mobileNavOpen: false,
})

function toggleSidebar() {
  state.sidebarCollapsed = !state.sidebarCollapsed
  window.localStorage.setItem('meridian-sidebar', state.sidebarCollapsed ? 'collapsed' : 'expanded')
}

function openCommand() {
  state.commandOpen = true
}

function closeCommand() {
  state.commandOpen = false
}

export const ui = {
  state,
  sidebarCollapsed: computed(() => state.sidebarCollapsed),
  toggleSidebar,
  openCommand,
  closeCommand,
}
