import type { MessageCatalog } from './en'

/** German catalog (ADR 0011). */
export const de: MessageCatalog = {
  'app.title': 'NeNe Serve',
  'app.subtitle': 'Anzeigenauslieferung & Analyse',

  'nav.placements': 'Platzierungen',

  'shell.theme': 'Design',
  'shell.themeLight': 'Hell',
  'shell.themeDark': 'Dunkel',
  'shell.lang': 'Sprache',
  'shell.signout': 'Abmelden',

  'common.error.unauthorized': 'Bitte melden Sie sich an, um fortzufahren.',
  'common.error.forbidden': 'Sie haben keinen Zugriff auf diese Ressource.',
  'common.error.notFound': 'Nicht gefunden.',
  'common.error.conflict': 'Diese Aktion steht im Konflikt mit dem aktuellen Zustand.',
  'common.error.validation': 'Bitte prüfen Sie das Formular und versuchen Sie es erneut.',
  'common.error.rateLimit': 'Zu viele Anfragen. Bitte warten Sie und versuchen Sie es erneut.',
  'common.error.serverError': 'Auf unserer Seite ist ein Fehler aufgetreten.',
  'common.error.unknown': 'Ein unerwarteter Fehler ist aufgetreten.',

  'login.title': 'Anmelden',
  'login.subtitle': 'Betreiberkonsole',
  'login.email': 'E-Mail',
  'login.password': 'Passwort',
  'login.submit': 'Anmelden',
  'login.failed': 'Ungültige E-Mail oder ungültiges Passwort.',
  'login.secure': 'Verschlüsselter, auditierter Zugriff.',
  'login.validation.emailRequired': 'E-Mail ist erforderlich.',
  'login.validation.passwordRequired': 'Passwort ist erforderlich.',

  'placements.title': 'Platzierungen',
  'placements.subtitle': 'Anzeigenplätze und ihr Auslieferungsstatus',
  'placements.empty': 'Noch keine Platzierungen.',
  'placements.loading': 'Wird geladen…',
  'placements.loadError': 'Platzierungen konnten nicht geladen werden.',
  'placements.column.key': 'Schlüssel',
  'placements.column.status': 'Status',
  'placements.column.creative': 'Standard-Creative',

  'notFound.title': 'Seite nicht gefunden',
  'notFound.body': 'Die gesuchte Seite existiert nicht.',
  'notFound.back': 'Zurück zu den Platzierungen',
}
