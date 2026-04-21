import apiClient from './client';
import { ApiResponse, Space, Member } from '../types';

export const getSpaces = async (): Promise<Space[]> => {
  const res = await apiClient.get<ApiResponse<Space[]>>('/spaces');
  return res.data.data;
};

export const getSpace = async (id: number): Promise<Space> => {
  const res = await apiClient.get<ApiResponse<Space>>(`/spaces/${id}`);
  return res.data.data;
};

export const createSpace = async (data: Pick<Space, 'name' | 'description'>): Promise<Space> => {
  const res = await apiClient.post<ApiResponse<Space>>('/spaces', data);
  return res.data.data;
};

export const updateSpace = async (id: number, data: Pick<Space, 'name' | 'description'>): Promise<Space> => {
  const res = await apiClient.put<ApiResponse<Space>>(`/spaces/${id}`, data);
  return res.data.data;
};

export const deleteSpace = async (id: number): Promise<void> => {
  await apiClient.delete(`/spaces/${id}`);
};

export const getMembers = async (spaceId: number): Promise<Member[]> => {
  const res = await apiClient.get<ApiResponse<Member[]>>(`/spaces/${spaceId}/members`);
  return res.data.data;
};

export const addMember = async (spaceId: number, username: string, role: string): Promise<Member> => {
  const res = await apiClient.post<ApiResponse<Member>>(`/spaces/${spaceId}/members`, { username, role });
  return res.data.data;
};

export const updateMember = async (spaceId: number, userId: number, role: string): Promise<Member> => {
  const res = await apiClient.put<ApiResponse<Member>>(`/spaces/${spaceId}/members/${userId}`, { role });
  return res.data.data;
};

export const removeMember = async (spaceId: number, userId: number): Promise<void> => {
  await apiClient.delete(`/spaces/${spaceId}/members/${userId}`);
};
