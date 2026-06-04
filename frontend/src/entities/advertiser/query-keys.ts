export const advertiserKeys = {
  all: ['advertisers'] as const,
  list: () => [...advertiserKeys.all, 'list'] as const,
}
