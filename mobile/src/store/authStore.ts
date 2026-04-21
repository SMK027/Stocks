import { create } from 'zustand';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { User } from '../types';

/** Décode la claim `exp` (Unix timestamp) d'un JWT sans vérifier la signature. */
export function getJWTExpiry(token: string): number | null {
  try {
    const parts = token.split('.');
    if (parts.length !== 3) return null;
    const base64 = parts[1].replace(/-/g, '+').replace(/_/g, '/');
    const payload = JSON.parse(atob(base64)) as Record<string, unknown>;
    return typeof payload.exp === 'number' ? payload.exp : null;
  } catch {
    return null;
  }
}

interface AuthState {
  token: string | null;
  user: User | null;
  isLoading: boolean;
  isAuthenticated: boolean;
  setAuth: (token: string, user: User) => Promise<void>;
  setUser: (user: User) => void;
  updateToken: (token: string) => Promise<void>;
  logout: () => Promise<void>;
  loadFromStorage: () => Promise<void>;
}

export const useAuthStore = create<AuthState>((set) => ({
  token: null,
  user: null,
  isLoading: true,
  isAuthenticated: false,

  setAuth: async (token, user) => {
    await AsyncStorage.setItem('auth_token', token);
    await AsyncStorage.setItem('auth_user', JSON.stringify(user));
    set({ token, user, isAuthenticated: true });
  },

  setUser: (user) => {
    AsyncStorage.setItem('auth_user', JSON.stringify(user));
    set({ user });
  },

  /** Met à jour uniquement le token (après un refresh silencieux). */
  updateToken: async (token: string) => {
    await AsyncStorage.setItem('auth_token', token);
    set({ token });
  },

  logout: async () => {
    await AsyncStorage.multiRemove(['auth_token', 'auth_user']);
    set({ token: null, user: null, isAuthenticated: false });
  },

  loadFromStorage: async () => {
    try {
      const token = await AsyncStorage.getItem('auth_token');
      const userJson = await AsyncStorage.getItem('auth_user');
      if (token && userJson) {
        // Vérification client-side de l'expiration du JWT (sans appel réseau)
        const exp = getJWTExpiry(token);
        if (exp !== null && exp < Date.now() / 1000) {
          // Token expiré localement → nettoyage immédiat
          await AsyncStorage.multiRemove(['auth_token', 'auth_user']);
          return;
        }
        const user = JSON.parse(userJson) as User;
        set({ token, user, isAuthenticated: true });
      }
    } catch {
      // Ignorer les erreurs de lecture du storage
    } finally {
      set({ isLoading: false });
    }
  },
}));
