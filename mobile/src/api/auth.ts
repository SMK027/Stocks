import apiClient from './client';
import { AuthResponse, User } from '../types';

export const login = async (username: string, password: string): Promise<AuthResponse> => {
  const res = await apiClient.post<AuthResponse>('/auth/login', { username, password });
  return res.data;
};

export const getMe = async (): Promise<User> => {
  const res = await apiClient.get<{ success: boolean; data: User }>('/auth/me');
  return res.data.data;
};

export const updateMe = async (data: Partial<Pick<User, 'email' | 'first_name' | 'last_name' | 'daily_digest'>>): Promise<User> => {
  const res = await apiClient.put<{ success: boolean; data: User }>('/auth/me', data);
  return res.data.data;
};

export const changePassword = async (currentPassword: string, newPassword: string): Promise<void> => {
  await apiClient.post('/auth/password', {
    current_password: currentPassword,
    new_password: newPassword,
  });
};

export const refreshToken = async (): Promise<AuthResponse> => {
  const res = await apiClient.post<AuthResponse>('/auth/refresh');
  return res.data;
};
