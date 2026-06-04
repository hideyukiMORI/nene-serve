export const settingsKeys = {
  all: ['settings'] as const,
  smtp: () => [...settingsKeys.all, 'smtp'] as const,
}
