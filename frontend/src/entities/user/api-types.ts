import type { Paginated } from '@/shared/api/pagination'

export interface UserDto {
  id: string
  organization_id: string
  email: string
  role: string
}

export type UserListDto = Paginated<UserDto>

export interface InviteUserResultDto {
  id: string
  email: string
  role: string
  invite_email_sent: boolean
}
