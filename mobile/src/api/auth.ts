import apiClient from './client';
import { AuthResponse, User } from '../types';

export const login = async (email: string, password: string): Promise<AuthResponse> => {
  const res = await apiClient.post<AuthResponse>('/auth/login', { email, password });
  return res.data;
};

export const getMe = async (): Promise<User> => {
  const res = await apiClient.get<{ success: boolean; user: User }>('/auth/me');
  return res.data.user;
};

export const updateMe = async (data: Partial<Pick<User, 'email' | 'firstname' | 'lastname' | 'daily_digest'>>): Promise<User> => {
  const res = await apiClient.put<{ success: boolean; user: User }>('/auth/me', data);
  return res.data.user;
};

export const changePassword = async (currentPassword: string, newPassword: string): Promise<void> => {
  await apiClient.post('/auth/password', {
    current_password: currentPassword,
    new_password: newPassword,
  });
};

export const refreshToken = async (): Promise<{ success: boolean; token: string }> => {
  const res = await apiClient.post<{ success: boolean; token: string }>('/auth/refresh');
  return res.data;
};
