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
}
