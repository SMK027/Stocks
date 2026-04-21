import apiClient from './client';
import { ApiResponse, InventoryItem } from '../types';

export const getInventory = async (spaceId: number): Promise<InventoryItem[]> => {
  const res = await apiClient.get<ApiResponse<InventoryItem[]>>(`/spaces/${spaceId}/inventory`);
  return res.data.data;
};

export const getExpiredItems = async (spaceId: number): Promise<InventoryItem[]> => {
  const res = await apiClient.get<ApiResponse<InventoryItem[]>>(`/spaces/${spaceId}/inventory/expired`);
  return res.data.data;
};

export const getExpiringItems = async (spaceId: number, days = 7): Promise<InventoryItem[]> => {
  const res = await apiClient.get<ApiResponse<InventoryItem[]>>(
    `/spaces/${spaceId}/inventory/expiring?days=${days}`,
  );
  return res.data.data;
};

export const getCasseItems = async (spaceId: number): Promise<InventoryItem[]> => {
  const res = await apiClient.get<ApiResponse<InventoryItem[]>>(`/spaces/${spaceId}/inventory/casse`);
  return res.data.data;
};

export const getInventoryItem = async (spaceId: number, id: number): Promise<InventoryItem> => {
  const res = await apiClient.get<ApiResponse<InventoryItem>>(`/spaces/${spaceId}/inventory/${id}`);
  return res.data.data;
};

export const upsertInventoryItem = async (
  spaceId: number,
  data: {
    product_id: number;
    location_id: number;
    quantity: number;
    stock_date?: string;
    expiry_date?: string | null;
  },
): Promise<InventoryItem> => {
  const res = await apiClient.post<ApiResponse<InventoryItem>>(`/spaces/${spaceId}/inventory`, data);
  return res.data.data;
};

export const updateInventoryItem = async (
  spaceId: number,
  id: number,
  data: Partial<Pick<InventoryItem, 'quantity' | 'stock_date' | 'expiry_date' | 'product_id' | 'location_id'>>,
): Promise<InventoryItem> => {
  const res = await apiClient.put<ApiResponse<InventoryItem>>(`/spaces/${spaceId}/inventory/${id}`, data);
  return res.data.data;
};

export const deleteInventoryItem = async (spaceId: number, id: number): Promise<void> => {
  await apiClient.delete(`/spaces/${spaceId}/inventory/${id}`);
};

export const decreaseInventoryItem = async (
  spaceId: number,
  id: number,
  decreaseBy = 1,
): Promise<InventoryItem | null> => {
  const res = await apiClient.post<ApiResponse<InventoryItem> & { deleted?: boolean }>(
    `/spaces/${spaceId}/inventory/${id}/decrease`,
    { decrease_by: decreaseBy },
  );
  return res.data.data ?? null;
};

export const markAsCasse = async (spaceId: number, id: number): Promise<void> => {
  await apiClient.post(`/spaces/${spaceId}/inventory/${id}/casse`);
};

export const unmarkCasse = async (spaceId: number, id: number): Promise<void> => {
  await apiClient.post(`/spaces/${spaceId}/inventory/${id}/uncasse`);
};
