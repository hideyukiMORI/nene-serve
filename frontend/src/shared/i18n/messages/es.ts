import type { MessageCatalog } from './en'

/** Spanish catalog (ADR 0011). */
export const es: MessageCatalog = {
  'app.title': 'NeNe Serve',
  'app.subtitle': 'Publicación de anuncios y analítica',

  'nav.placements': 'Ubicaciones',

  'shell.theme': 'Tema',
  'shell.themeLight': 'Claro',
  'shell.themeDark': 'Oscuro',
  'shell.lang': 'Idioma',
  'shell.signout': 'Cerrar sesión',

  'common.error.unauthorized': 'Inicie sesión para continuar.',
  'common.error.forbidden': 'No tiene acceso a este recurso.',
  'common.error.notFound': 'No encontrado.',
  'common.error.conflict': 'Esta acción entra en conflicto con el estado actual.',
  'common.error.validation': 'Revise el formulario e inténtelo de nuevo.',
  'common.error.rateLimit': 'Demasiadas solicitudes. Espere e inténtelo de nuevo.',
  'common.error.serverError': 'Algo salió mal de nuestro lado.',
  'common.error.unknown': 'Se produjo un error inesperado.',

  'login.title': 'Iniciar sesión',
  'login.subtitle': 'Consola del operador',
  'login.email': 'Correo electrónico',
  'login.password': 'Contraseña',
  'login.submit': 'Iniciar sesión',
  'login.failed': 'Correo electrónico o contraseña no válidos.',
  'login.secure': 'Acceso cifrado y auditado.',
  'login.validation.emailRequired': 'El correo electrónico es obligatorio.',
  'login.validation.passwordRequired': 'La contraseña es obligatoria.',

  'placements.title': 'Ubicaciones',
  'placements.subtitle': 'Espacios publicitarios y su estado de publicación',
  'placements.empty': 'Aún no hay ubicaciones.',
  'placements.loading': 'Cargando…',
  'placements.loadError': 'No se pudieron cargar las ubicaciones.',
  'placements.column.key': 'Clave',
  'placements.column.status': 'Estado',
  'placements.column.creative': 'Creatividad predeterminada',

  'notFound.title': 'Página no encontrada',
  'notFound.body': 'La página que busca no existe.',
  'notFound.back': 'Volver a las ubicaciones',
}
