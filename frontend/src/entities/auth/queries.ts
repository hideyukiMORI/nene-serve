import { useQuery, type UseQueryResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import { useAuthToken } from '@/shared/auth'

export interface CurrentUser {
  id: string
  email: string
  role: string
  organizationId: string
}

interface CurrentUserDto {
  id: string
  email: string
  role: string
  organization_id: string
}

export type TenantResolutionMode = 'login' | 'single' | 'subdomain' | 'path' | 'custom_domain'

export interface TenantContext {
  mode: TenantResolutionMode
  organization: { slug: string; name: string } | null
}

/**
 * How the admin surface determines the tenant (ADR 0006). The login screen reads
 * this before sign-in: in a URL mode the organization is resolved from the
 * address, so the org field is replaced with the resolved organization name.
 * Unauthenticated, and effectively immutable for the life of the page.
 */
export function useTenantContext(): UseQueryResult<TenantContext, AppError> {
  return useQuery({
    queryKey: ['auth', 'tenant-context'],
    queryFn: () => apiClient.get<TenantContext>('/admin/tenant-context'),
    staleTime: Infinity,
  })
}

export function useCurrentUser(): UseQueryResult<CurrentUser, AppError> {
  const token = useAuthToken()
  return useQuery({
    queryKey: ['auth', 'me'],
    queryFn: async () => {
      const dto = await apiClient.get<CurrentUserDto>('/admin/me')
      return {
        id: dto.id,
        email: dto.email,
        role: dto.role,
        organizationId: dto.organization_id,
      }
    },
    enabled: token !== null,
  })
}
