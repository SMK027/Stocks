import apiClient from './client';
import { ApiResponse, Product } from '../types';

export const getProducts = async (spaceId: number): Promise<Product[]> => {
  const res = await apiClient.get<ApiResponse<Product[]>>(`/spaces/${spaceId}/products`);
  return res.data.data;
};

export const getProduct = async (spaceId: number, id: number): Promise<Product> => {
  const res = await apiClient.get<ApiResponse<Product>>(`/spaces/${spaceId}/products/${id}`);
  return res.data.data;
};

export const createProduct = async (
  spaceId: number,
  data: { name: string; description?: string; category_ids?: number[] },
): Promise<Product> => {
  const res = await apiClient.post<ApiResponse<Product>>(`/spaces/${spaceId}/products`, data);
  return res.data.data;
};

export const updateProduct = async (
  spaceId: number,
  id: number,
  data: { name?: string; description?: string; category_ids?: number[] },
): Promise<Product> => {
  const res = await apiClient.put<ApiResponse<Product>>(`/spaces/${spaceId}/products/${id}`, data);
  return res.data.data;
};

export const deleteProduct = async (spaceId: number, id: number): Promise<void> => {
  await apiClient.delete(`/spaces/${spaceId}/products/${id}`);
};
