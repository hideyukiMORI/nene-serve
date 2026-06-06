/**
 * English message catalog — the authoritative source of truth (ADR 0008,
 * English-first repo). Add new keys here first; the other five locale catalogs
 * mirror this `MessageCatalog` shape (ADR 0011 — six-locale parity).
 */
export interface MessageCatalog {
  'app.title': string
  'app.subtitle': string

  'nav.home': string
  'nav.placements': string
  'nav.group.prepare': string
  'nav.group.build': string
  'nav.group.govern': string
  'nav.group.measure': string
  'nav.group.business': string
  'nav.group.admin': string

  'home.greeting': string
  'home.lead': string
  'home.setup.title': string
  'home.setup.subtitle': string
  'home.setup.progress': string
  'home.setup.resume': string
  'home.step.doThis': string
  'home.step.done': string
  'home.steps.smtp.title': string
  'home.steps.smtp.desc': string
  'home.steps.invite.title': string
  'home.steps.invite.desc': string
  'home.steps.placement.title': string
  'home.steps.placement.desc': string
  'home.steps.creative.title': string
  'home.steps.creative.desc': string
  'home.steps.approve.title': string
  'home.steps.approve.desc': string
  'home.steps.embed.title': string
  'home.steps.embed.desc': string
  'home.steps.measure.title': string
  'home.steps.measure.desc': string

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
  'login.organization': string
  'login.signingInTo': string
  'login.email': string
  'login.password': string
  'login.submit': string
  'login.failed': string
  'login.secure': string
  'login.validation.organizationRequired': string
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
  'placements.create': string
  'placements.field.key': string
  'placements.field.origins': string
  'placements.field.defaultCreative': string

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
  'creatives.create': string
  'creatives.field.destination': string
  'creatives.field.asset': string
  'creatives.field.width': string
  'creatives.field.height': string
  'creatives.field.file': string
  'creatives.uploading': string
  'creatives.uploaded': string
  'creatives.uploadError': string

  'form.create': string
  'form.creating': string
  'form.error': string
  'form.required': string
  'form.positiveInt': string

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

  'nav.users': string
  'nav.settings': string

  'users.title': string
  'users.subtitle': string
  'users.invite': string
  'users.loading': string
  'users.empty': string
  'users.field.email': string
  'users.field.role': string
  'users.action.invite': string
  'users.inviteSent': string
  'users.inviteNotSent': string
  'users.column.email': string
  'users.column.role': string

  'settings.title': string
  'settings.loading': string
  'settings.saved': string
  'settings.testOk': string
  'settings.tenant.title': string
  'settings.tenant.mode': string
  'settings.tenant.organization': string
  'settings.tenant.note': string
  'settings.smtp.subtitle': string
  'settings.smtp.host': string
  'settings.smtp.port': string
  'settings.smtp.encryption': string
  'settings.smtp.username': string
  'settings.smtp.password': string
  'settings.smtp.passwordSet': string
  'settings.smtp.fromAddress': string
  'settings.smtp.fromName': string
  'settings.action.save': string
  'settings.action.test': string
  'settings.action.testing': string

  'setPassword.title': string
  'setPassword.validating': string
  'setPassword.invalid': string
  'setPassword.password': string
  'setPassword.submit': string
  'setPassword.tooShort': string
  'setPassword.doneTitle': string
  'setPassword.doneBody': string
  'setPassword.toLogin': string
}

export const en: MessageCatalog = {
  'app.title': 'NeNe Serve',
  'app.subtitle': 'Ad serving & analytics',

  'nav.home': 'Home',
  'nav.placements': 'Placements',
  'nav.group.prepare': 'Prepare',
  'nav.group.build': 'Build',
  'nav.group.govern': 'Govern',
  'nav.group.measure': 'Measure',
  'nav.group.business': 'Business',
  'nav.group.admin': 'Admin',

  'home.greeting': 'Welcome to NeNe Serve',
  'home.lead':
    "Run ads on sites you control — register a creative, get it approved, embed one line, and watch the results. Here's the path.",
  'home.setup.title': 'Getting started',
  'home.setup.subtitle': 'Complete the loop once and your first ad goes live.',
  'home.setup.progress': '{{done}} of {{total}} steps complete',
  'home.setup.resume': 'Resume setup',
  'home.step.doThis': 'Do this',
  'home.step.done': 'Done',
  'home.steps.smtp.title': 'Configure outbound email',
  'home.steps.smtp.desc': 'Add SMTP so invitations and test mail can be sent.',
  'home.steps.invite.title': 'Invite your team',
  'home.steps.invite.desc': 'Add a teammate so someone else can approve your work (four-eyes).',
  'home.steps.placement.title': 'Create a placement',
  'home.steps.placement.desc': 'Define an ad slot and get its embed key.',
  'home.steps.creative.title': 'Upload a creative',
  'home.steps.creative.desc': 'Add the ad content and submit it for review.',
  'home.steps.approve.title': 'Approve the creative',
  'home.steps.approve.desc': 'A second reviewer approves it — only approved creatives serve.',
  'home.steps.embed.title': 'Embed on your site',
  'home.steps.embed.desc': 'Paste one serve.js line where the ad should appear.',
  'home.steps.measure.title': 'Read your results',
  'home.steps.measure.desc': 'Impressions and clicks roll up in Metrics.',

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
  'login.organization': 'Organization',
  'login.signingInTo': 'Signing in to {{name}}',
  'login.email': 'Email',
  'login.password': 'Password',
  'login.submit': 'Sign in',
  'login.failed': 'Invalid email or password.',
  'login.secure': 'Encrypted, audited access.',
  'login.validation.organizationRequired': 'Organization is required.',
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
  'placements.create': 'New placement',
  'placements.field.key': 'Public placement key',
  'placements.field.origins': 'Allowed origins (comma-separated)',
  'placements.field.defaultCreative': 'Default creative id (optional)',

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
  'creatives.create': 'New image creative',
  'creatives.field.destination': 'Destination URL',
  'creatives.field.asset': 'Asset URL',
  'creatives.field.width': 'Width',
  'creatives.field.height': 'Height',
  'creatives.field.file': 'Image file',
  'creatives.uploading': 'Uploading…',
  'creatives.uploaded': 'Uploaded.',
  'creatives.uploadError': 'Upload an image file before creating.',

  'form.create': 'Create',
  'form.creating': 'Creating…',
  'form.error': 'Could not create. Check the form and try again.',
  'form.required': 'This field is required.',
  'form.positiveInt': 'Enter a positive whole number.',

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

  'nav.users': 'Users',
  'nav.settings': 'Settings',

  'users.title': 'Users',
  'users.subtitle': 'Operators in your organization',
  'users.invite': 'Invite a user',
  'users.loading': 'Loading…',
  'users.empty': 'No users yet.',
  'users.field.email': 'Email',
  'users.field.role': 'Role',
  'users.action.invite': 'Send invite',
  'users.inviteSent': 'Invitation email sent.',
  'users.inviteNotSent':
    'User created, but the invite email could not be sent (check SMTP settings).',
  'users.column.email': 'Email',
  'users.column.role': 'Role',

  'settings.title': 'Settings',
  'settings.loading': 'Loading…',
  'settings.saved': 'Saved.',
  'settings.testOk': 'Test email sent.',
  'settings.tenant.title': 'Tenant resolution',
  'settings.tenant.mode': 'Mode',
  'settings.tenant.organization': 'Organization',
  'settings.tenant.note': 'Set in deploy configuration (TENANT_RESOLUTION).',
  'settings.smtp.subtitle': 'Outbound email (SMTP) for invitations',
  'settings.smtp.host': 'Host',
  'settings.smtp.port': 'Port',
  'settings.smtp.encryption': 'Encryption',
  'settings.smtp.username': 'Username',
  'settings.smtp.password': 'Password',
  'settings.smtp.passwordSet': 'Password (leave blank to keep)',
  'settings.smtp.fromAddress': 'From address',
  'settings.smtp.fromName': 'From name',
  'settings.action.save': 'Save',
  'settings.action.test': 'Send test email',
  'settings.action.testing': 'Sending…',

  'setPassword.title': 'Set your password',
  'setPassword.validating': 'Checking your invitation…',
  'setPassword.invalid': 'This invitation is invalid, used, or expired.',
  'setPassword.password': 'New password',
  'setPassword.submit': 'Set password',
  'setPassword.tooShort': 'Password must be at least 8 characters.',
  'setPassword.doneTitle': 'Password set',
  'setPassword.doneBody': 'You can now sign in with your new password.',
  'setPassword.toLogin': 'Go to sign in',
}
