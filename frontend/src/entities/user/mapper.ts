import type { UserDto } from './api-types'
import type { User } from './model'

export function mapUserDtoToModel(dto: UserDto): User {
  return { id: dto.id, email: dto.email, role: dto.role }
}
