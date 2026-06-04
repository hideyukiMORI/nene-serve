export interface LoginRequestDto {
  organization: string
  email: string
  password: string
}

export interface LoginResultDto {
  token: string
}
