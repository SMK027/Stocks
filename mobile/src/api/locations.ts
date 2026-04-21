import apiClient from './client';
import { ApiResponse, Location } from '../types';

export const getLocations = async (spaceId: number): Promise<Location[]> => {
  const res = await apiClient.get<ApiResponse<Location[]>>(`/spaces/${spaceId}/locations`);
  return res.data.data;
};

export const getLocation = async (spaceId: number, id: number): Promise<Location> => {
  const res = await apiClient.get<ApiResponse<Location>>(`/spaces/${spaceId}/locations/${id}`);
  return res.data.data;
};

export const createLocation = async (spaceId: number, data: Partial<Location>): Promise<Location> => {
  const res = await apiClient.post<ApiResponse<Location>>(`/spaces/${spaceId}/locations`, data);
  return res.data.data;
};

export const updateLocation = async (spaceId: number, id: number, data: Partial<Location>): Promise<Location> => {
  const res = await apiClient.put<ApiResponse<Location>>(`/spaces/${spaceId}/locations/${id}`, data);
  return res.data.data;
};

export const deleteLocation = async (spaceId: number, id: number): Promise<void> => {
  await apiClient.delete(`/spaces/${spaceId}/locations/${id}`);
};
