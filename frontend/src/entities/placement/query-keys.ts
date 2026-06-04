export const placementKeys = {
  all: ['placements'] as const,
  list: () => [...placementKeys.all, 'list'] as const,
}
