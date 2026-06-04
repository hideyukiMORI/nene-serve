export const creativeKeys = {
  all: ['creatives'] as const,
  list: () => [...creativeKeys.all, 'list'] as const,
  reviewQueue: () => [...creativeKeys.all, 'review-queue'] as const,
}
