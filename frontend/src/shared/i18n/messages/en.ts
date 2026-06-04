/**
 * English message catalog — the authoritative source of truth (ADR 0008,
 * English-first repo). Add new keys here first; the other five locale catalogs
 * mirror this `MessageCatalog` shape (ADR 0011 — six-locale parity).
 */
export interface MessageCatalog {
  'app.title': string
  'app.subtitle': string

  'nav.placements': string

  'shell.theme': string
  'shell.themeLight': string
  'shell.themeDark': string
  'shell.lang': string
  'shell.signout': string

  'common.error.unauthorized': string
  'common.error.forbidden': string
  'common.error.notFound': string
  'common.error.conflict': string
  'common.error.validation': string
  'common.error.rateLimit': string
  'common.error.serverError': string
  'common.error.unknown': string

  'login.title': string
  'login.subtitle': string
  'login.email': string
  'login.password': string
  'login.submit': string
  'login.failed': string
  'login.secure': string
  'login.validation.emailRequired': string
  'login.validation.passwordRequired': string

  'placements.title': string
  'placements.subtitle': string
  'placements.empty': string
  'placements.loading': string
  'placements.loadError': string
  'placements.column.key': string
  'placements.column.status': string
  'placements.column.creative': string

  'notFound.title': string
  'notFound.body': string
  'notFound.back': string

  'nav.creatives': string
  'nav.review': string

  'creatives.title': string
  'creatives.subtitle': string
  'creatives.empty': string
  'creatives.loading': string
  'creatives.loadError': string
  'creatives.column.id': string
  'creatives.column.type': string
  'creatives.column.status': string
  'creatives.column.version': string

  'review.title': string
  'review.subtitle': string
  'review.empty': string
  'review.loading': string
  'review.loadError': string
  'review.column.id': string
  'review.column.type': string
  'review.column.status': string
  'review.column.actions': string
  'review.action.startReview': string
  'review.action.approve': string
  'review.action.reject': string
  'review.action.requestChanges': string
  'review.fourEyes': string
  'review.actionFailed': string

  'nav.metrics': string
  'metrics.title': string
  'metrics.subtitle': string
  'metrics.loading': string
  'metrics.loadError': string
  'metrics.empty': string
  'metrics.kpi.impressions': string
  'metrics.kpi.clicks': string
  'metrics.kpi.ctr': string
  'metrics.kpi.fillRate': string
  'metrics.column.date': string
  'metrics.column.impressions': string
  'metrics.column.clicks': string
  'metrics.column.ctr': string

  'nav.marketplace': string
  'marketplace.title': string
  'marketplace.subtitle': string
  'marketplace.loading': string
  'marketplace.loadError': string
  'marketplace.advertisers.title': string
  'marketplace.advertisers.empty': string
  'marketplace.pricingRules.title': string
  'marketplace.pricingRules.empty': string
  'marketplace.campaigns.title': string
  'marketplace.campaigns.empty': string
  'marketplace.column.name': string
  'marketplace.column.status': string
  'marketplace.column.model': string
  'marketplace.column.rate': string
  'marketplace.column.version': string
  'marketplace.column.funding': string
  'marketplace.column.budget': string
  'marketplace.create.advertiser': string
  'marketplace.create.pricingRule': string
  'marketplace.create.campaign': string
  'marketplace.field.advertiser': string
  'marketplace.field.pricingRule': string
  'marketplace.field.rateCents': string
  'marketplace.field.budgetCents': string
  'marketplace.action.create': string
  'marketplace.action.creating': string
  'marketplace.createError': string
  'marketplace.validation.required': string
  'marketplace.validation.nonNegative': string
}

export const en: MessageCatalog = {
  'app.title': 'NeNe Serve',
  'app.subtitle': 'Ad serving & analytics',

  'nav.placements': 'Placements',

  'shell.theme': 'Theme',
  'shell.themeLight': 'Light',
  'shell.themeDark': 'Dark',
  'shell.lang': 'Language',
  'shell.signout': 'Sign out',

  'common.error.unauthorized': 'Please sign in to continue.',
  'common.error.forbidden': 'You do not have access to this resource.',
  'common.error.notFound': 'Not found.',
  'common.error.conflict': 'This action conflicts with the current state.',
  'common.error.validation': 'Please check the form and try again.',
  'common.error.rateLimit': 'Too many requests. Please wait and retry.',
  'common.error.serverError': 'Something went wrong on our side.',
  'common.error.unknown': 'An unexpected error occurred.',

  'login.title': 'Sign in',
  'login.subtitle': 'Operator console',
  'login.email': 'Email',
  'login.password': 'Password',
  'login.submit': 'Sign in',
  'login.failed': 'Invalid email or password.',
  'login.secure': 'Encrypted, audited access.',
  'login.validation.emailRequired': 'Email is required.',
  'login.validation.passwordRequired': 'Password is required.',

  'placements.title': 'Placements',
  'placements.subtitle': 'Ad slots and their serving status',
  'placements.empty': 'No placements yet.',
  'placements.loading': 'Loading…',
  'placements.loadError': 'Could not load placements.',
  'placements.column.key': 'Key',
  'placements.column.status': 'Status',
  'placements.column.creative': 'Default creative',

  'notFound.title': 'Page not found',
  'notFound.body': 'The page you are looking for does not exist.',
  'notFound.back': 'Back to placements',

  'nav.creatives': 'Creatives',
  'nav.review': 'Review',

  'creatives.title': 'Creatives',
  'creatives.subtitle': 'Uploaded creatives and their review status',
  'creatives.empty': 'No creatives yet.',
  'creatives.loading': 'Loading…',
  'creatives.loadError': 'Could not load creatives.',
  'creatives.column.id': 'ID',
  'creatives.column.type': 'Type',
  'creatives.column.status': 'Review status',
  'creatives.column.version': 'Version',

  'review.title': 'Review queue',
  'review.subtitle': 'Creatives awaiting a review decision',
  'review.empty': 'Nothing to review.',
  'review.loading': 'Loading…',
  'review.loadError': 'Could not load the review queue.',
  'review.column.id': 'ID',
  'review.column.type': 'Type',
  'review.column.status': 'Status',
  'review.column.actions': 'Actions',
  'review.action.startReview': 'Start review',
  'review.action.approve': 'Approve',
  'review.action.reject': 'Reject',
  'review.action.requestChanges': 'Request changes',
  'review.fourEyes': 'Four-eyes: you cannot approve a creative you submitted.',
  'review.actionFailed': 'The action could not be completed.',

  'nav.metrics': 'Metrics',
  'metrics.title': 'Metrics',
  'metrics.subtitle': 'Delivery performance over the last 30 days',
  'metrics.loading': 'Loading…',
  'metrics.loadError': 'Could not load metrics.',
  'metrics.empty': 'No metrics in this window.',
  'metrics.kpi.impressions': 'Impressions',
  'metrics.kpi.clicks': 'Clicks',
  'metrics.kpi.ctr': 'CTR',
  'metrics.kpi.fillRate': 'Fill rate',
  'metrics.column.date': 'Date',
  'metrics.column.impressions': 'Impressions',
  'metrics.column.clicks': 'Clicks',
  'metrics.column.ctr': 'CTR',

  'nav.marketplace': 'Marketplace',
  'marketplace.title': 'Marketplace',
  'marketplace.subtitle': 'Advertisers, pricing rules and campaigns',
  'marketplace.loading': 'Loading…',
  'marketplace.loadError': 'Could not load marketplace data.',
  'marketplace.advertisers.title': 'Advertisers',
  'marketplace.advertisers.empty': 'No advertisers yet.',
  'marketplace.pricingRules.title': 'Pricing rules',
  'marketplace.pricingRules.empty': 'No pricing rules yet.',
  'marketplace.campaigns.title': 'Campaigns',
  'marketplace.campaigns.empty': 'No campaigns yet.',
  'marketplace.column.name': 'Name',
  'marketplace.column.status': 'Status',
  'marketplace.column.model': 'Model',
  'marketplace.column.rate': 'Rate',
  'marketplace.column.version': 'Version',
  'marketplace.column.funding': 'Funding',
  'marketplace.column.budget': 'Budget',
  'marketplace.create.advertiser': 'New advertiser',
  'marketplace.create.pricingRule': 'New pricing rule',
  'marketplace.create.campaign': 'New campaign',
  'marketplace.field.advertiser': 'Advertiser',
  'marketplace.field.pricingRule': 'Pricing rule',
  'marketplace.field.rateCents': 'Rate (cents)',
  'marketplace.field.budgetCents': 'Budget (cents)',
  'marketplace.action.create': 'Create',
  'marketplace.action.creating': 'Creating…',
  'marketplace.createError': 'Could not create. Check the form and try again.',
  'marketplace.validation.required': 'This field is required.',
  'marketplace.validation.nonNegative': 'Enter 0 or a positive whole number.',
}
