export const pricingRuleKeys = {
  all: ['pricing-rules'] as const,
  list: () => [...pricingRuleKeys.all, 'list'] as const,
}
