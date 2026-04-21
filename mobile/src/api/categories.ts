import apiClient from './client';
import { ApiResponse, Category } from '../types';

export const getCategories = async (spaceId: number): Promise<Category[]> => {
  const res = await apiClient.get<ApiResponse<Category[]>>(`/spaces/${spaceId}/categories`);
  return res.data.data;
};

export const getCategory = async (spaceId: number, id: number): Promise<Category> => {
  const res = await apiClient.get<ApiResponse<Category>>(`/spaces/${spaceId}/categories/${id}`);
  return res.data.data;
};

export const createCategory = async (spaceId: number, data: Partial<Category>): Promise<Category> => {
  const res = await apiClient.post<ApiResponse<Category>>(`/spaces/${spaceId}/categories`, data);
  return res.data.data;
};

export const updateCategory = async (spaceId: number, id: number, data: Partial<Category>): Promise<Category> => {
  const res = await apiClient.put<ApiResponse<Category>>(`/spaces/${spaceId}/categories/${id}`, data);
  return res.data.data;
};

export const deleteCategory = async (spaceId: number, id: number): Promise<void> => {
  await apiClient.delete(`/spaces/${spaceId}/categories/${id}`);
};
