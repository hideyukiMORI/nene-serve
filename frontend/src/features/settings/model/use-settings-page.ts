import {
  useSmtpSettings,
  useTestSmtpSettings,
  useUpdateSmtpSettings,
  type SmtpSettings,
  type UpdateSmtpInput,
} from '@/entities/settings'
import { mapProblemDetailsToMessageKey, type MessageKey } from '@/shared/i18n'

export interface SettingsPage {
  settings: SmtpSettings | null
  loading: boolean
  errorKey: MessageKey | null
  saving: boolean
  saveErrorKey: MessageKey | null
  saved: boolean
  testing: boolean
  testResultKey: MessageKey | 'ok' | null
  save: (input: UpdateSmtpInput) => Promise<boolean>
  test: () => Promise<void>
}

export function useSettingsPage(): SettingsPage {
  const query = useSmtpSettings()
  const update = useUpdateSmtpSettings()
  const testMutation = useTestSmtpSettings()

  return {
    settings: query.data ?? null,
    loading: query.isPending,
    errorKey: query.error !== null ? mapProblemDetailsToMessageKey(query.error) : null,
    saving: update.isPending,
    saveErrorKey: update.error !== null ? mapProblemDetailsToMessageKey(update.error) : null,
    saved: update.isSuccess,
    testing: testMutation.isPending,
    testResultKey:
      testMutation.error !== null
        ? mapProblemDetailsToMessageKey(testMutation.error)
        : testMutation.isSuccess
          ? 'ok'
          : null,
    save: async (input: UpdateSmtpInput): Promise<boolean> => {
      try {
        await update.mutateAsync(input)
        return true
      } catch {
        return false
      }
    },
    test: async (): Promise<void> => {
      try {
        await testMutation.mutateAsync()
      } catch {
        /* surfaced via testResultKey */
      }
    },
  }
}
