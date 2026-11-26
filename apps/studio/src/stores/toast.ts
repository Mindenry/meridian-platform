import { reactive } from 'vue'
import type { ToastMessage } from '@/types'

const toasts = reactive<ToastMessage[]>([])
let nextId = 1

function push(tone: ToastMessage['tone'], title: string, message?: string, duration = 4200) {
  const id = nextId++
  toasts.push({ id, tone, title, message })
  window.setTimeout(() => remove(id), duration)
  return id
}

function remove(id: number) {
  const index = toasts.findIndex((toast) => toast.id === id)
  if (index !== -1) toasts.splice(index, 1)
}

export const toast = {
  toasts,
  push,
  remove,
  success: (title: string, message?: string) => push('success', title, message),
  error: (title: string, message?: string) => push('error', title, message, 6000),
  info: (title: string, message?: string) => push('info', title, message),
}
