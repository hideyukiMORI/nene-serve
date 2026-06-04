export const metricsKeys = {
  all: ['metrics'] as const,
  report: () => [...metricsKeys.all, 'report'] as const,
}
