import axios, { AxiosInstance, InternalAxiosRequestConfig, AxiosError } from 'axios';
import AsyncStorage from '@react-native-async-storage/async-storage';

export const BASE_URL = 'https://stocks.leofranz.fr/api';

const apiClient: AxiosInstance = axios.create({
  baseURL: BASE_URL,
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
  timeout: 10000,
});

// Callback appelé lors d'un 401 définitif (token invalide / refresh échoué)
let unauthorizedCallback: (() => void) | null = null;
export const setUnauthorizedCallback = (cb: () => void) => {
  unauthorizedCallback = cb;
};

// Callback appelé après un refresh réussi pour mettre à jour le store Zustand
let tokenRefreshedCallback: ((token: string) => void) | null = null;
export const setTokenRefreshedCallback = (cb: (token: string) => void) => {
  tokenRefreshedCallback = cb;
};

// Mutex pour éviter plusieurs refresh simultanés
let isRefreshing = false;
let refreshQueue: Array<(token: string | null) => void> = [];

const processQueue = (token: string | null) => {
  refreshQueue.forEach((resolve) => resolve(token));
  refreshQueue = [];
};

// Attach le token JWT à chaque requête
apiClient.interceptors.request.use(
  async (config: InternalAxiosRequestConfig) => {
    const token = await AsyncStorage.getItem('auth_token');
    if (token && config.headers) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => Promise.reject(error),
);

type RetryableConfig = InternalAxiosRequestConfig & { _retry?: boolean };

// Gère les 401 avec tentative de refresh automatique
apiClient.interceptors.response.use(
  (response) => response,
  async (error: AxiosError) => {
    const originalRequest = error.config as RetryableConfig | undefined;

    if (error.response?.status === 401 && originalRequest) {
      // Déjà tenté un refresh → déconnexion forcée
      if (originalRequest._retry) {
        await AsyncStorage.multiRemove(['auth_token', 'auth_user']);
        unauthorizedCallback?.();
        return Promise.reject(error);
      }

      // Un refresh est déjà en cours → mettre la requête en file d'attente
      if (isRefreshing) {
        return new Promise((resolve, reject) => {
          refreshQueue.push((token) => {
            if (token && originalRequest.headers) {
              originalRequest.headers.Authorization = `Bearer ${token}`;
              resolve(apiClient(originalRequest));
            } else {
              reject(error);
            }
          });
        });
      }

      originalRequest._retry = true;
      isRefreshing = true;

      try {
        const currentToken = await AsyncStorage.getItem('auth_token');
        // Appel direct (pas via apiClient) pour éviter la boucle d'intercepteur
        const res = await axios.post<{ success: boolean; token: string }>(
          `${BASE_URL}/auth/refresh`,
          {},
          {
            headers: {
              'Content-Type': 'application/json',
              Authorization: `Bearer ${currentToken}`,
            },
            timeout: 10000,
          },
        );
        const newToken = res.data.token;

        await AsyncStorage.setItem('auth_token', newToken);
        tokenRefreshedCallback?.(newToken);
        processQueue(newToken);

        if (originalRequest.headers) {
          originalRequest.headers.Authorization = `Bearer ${newToken}`;
        }
        return apiClient(originalRequest);
      } catch {
        processQueue(null);
        await AsyncStorage.multiRemove(['auth_token', 'auth_user']);
        unauthorizedCallback?.();
        return Promise.reject(error);
      } finally {
        isRefreshing = false;
      }
    }

    return Promise.reject(error);
  },
);

export default apiClient;
