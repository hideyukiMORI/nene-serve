export const invitationKeys = {
  all: ['invitation'] as const,
  preview: (token: string) => [...invitationKeys.all, 'preview', token] as const,
}
