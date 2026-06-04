export interface UserDto {
  id: string
  organization_id: string
  email: string
  role: string
}

export interface UserListDto {
  users: UserDto[]
}

export interface InviteUserResultDto {
  id: string
  email: string
  role: string
  invite_email_sent: boolean
}
