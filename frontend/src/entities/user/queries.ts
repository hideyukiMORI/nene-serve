import { useQuery, type UseQueryResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { UserListDto } from './api-types'
import { mapUserDtoToModel } from './mapper'
import type { User } from './model'
import { userKeys } from './query-keys'

export function useUsers(): UseQueryResult<User[], AppError> {
  return useQuery({
    queryKey: userKeys.list(),
    queryFn: async () => {
      const dto = await apiClient.get<UserListDto>('/admin/users')
      return dto.users.map(mapUserDtoToModel)
    },
  })
}
