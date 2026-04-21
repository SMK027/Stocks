// ─── Entités ─────────────────────────────────────────────────────────────────

export interface User {
  id: number;
  username: string;
  email: string;
  global_role: string;
  firstname?: string;
  lastname?: string;
  daily_digest?: boolean;
}

export interface Space {
  id: number;
  name: string;
  description?: string;
  user_role?: string;
}

export interface Member {
  id: number;
  username: string;
  email: string;
  role: string;
}

export interface Category {
  id: number;
  name: string;
  description?: string;
  max_consumption_days?: number | null;
  space_id: number;
}

export interface Location {
  id: number;
  name: string;
  description?: string;
  space_id: number;
}

export interface Product {
  id: number;
  name: string;
  description?: string;
  space_id: number;
  categories?: Category[];
  category_ids?: number[];
}

export interface InventoryItem {
  id: number;
  space_id: number;
  product_id: number;
  location_id: number;
  quantity: number;
  stock_date: string;
  expiry_date?: string | null;
  is_casse: boolean;
  product_name?: string;
  location_name?: string;
}

// ─── Réponses API ─────────────────────────────────────────────────────────────

export interface ApiResponse<T> {
  success: boolean;
  data: T;
  message?: string;
}

export interface AuthResponse {
  success: boolean;
  token: string;
  user: User;
}

// ─── Navigation ───────────────────────────────────────────────────────────────

export type RootStackParamList = {
  Auth: undefined;
  Main: undefined;
};

export type AuthStackParamList = {
  Login: undefined;
};

export type MainTabParamList = {
  Dashboard: undefined;
  Spaces: undefined;
  Profile: undefined;
};

export type SpacesStackParamList = {
  SpaceList: undefined;
  SpaceDetail: { spaceId: number; spaceName: string };
  SpaceCreate: undefined;
  SpaceEdit: { spaceId: number };
  Members: { spaceId: number; spaceName: string };
  CategoryList: { spaceId: number; spaceName: string };
  CategoryCreate: { spaceId: number };
  CategoryEdit: { spaceId: number; categoryId: number };
  LocationList: { spaceId: number; spaceName: string };
  LocationCreate: { spaceId: number };
  LocationEdit: { spaceId: number; locationId: number };
  ProductList: { spaceId: number; spaceName: string };
  ProductCreate: { spaceId: number };
  ProductEdit: { spaceId: number; productId: number };
  Inventory: { spaceId: number; spaceName: string };
  InventoryCreate: { spaceId: number };
  InventoryEdit: { spaceId: number; itemId: number };
};
