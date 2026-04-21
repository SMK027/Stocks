import React, { useEffect } from 'react';
import { View, ActivityIndicator } from 'react-native';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { useAuthStore, getJWTExpiry } from '../store/authStore';
import { setUnauthorizedCallback, setTokenRefreshedCallback } from '../api/client';
import { refreshToken } from '../api/auth';
import { RootStackParamList } from '../types';
import { colors } from '../theme';
import AuthNavigator from './AuthNavigator';
import MainNavigator from './MainNavigator';

const Stack = createNativeStackNavigator<RootStackParamList>();

/** Seuil : rafraîchir le token s'il expire dans moins de 7 jours. */
const REFRESH_THRESHOLD_MS = 7 * 24 * 60 * 60 * 1000;

export default function AppNavigator() {
  const { isLoading, isAuthenticated, loadFromStorage } = useAuthStore();

  useEffect(() => {
    // Synchronise les callbacks avant le chargement
    setUnauthorizedCallback(() => {
      useAuthStore.getState().logout();
    });
    setTokenRefreshedCallback((token) => {
      useAuthStore.getState().updateToken(token);
    });

    loadFromStorage().then(() => {
      // Refresh proactif : si le token expire dans moins de 7 jours, on le renouvelle en arrière-plan
      const { token } = useAuthStore.getState();
      if (token) {
        const exp = getJWTExpiry(token);
        if (exp !== null && exp * 1000 - Date.now() < REFRESH_THRESHOLD_MS) {
          refreshToken()
            .then(({ token: newToken }) => {
              useAuthStore.getState().updateToken(newToken);
            })
            .catch(() => {
              // Échec silencieux — l'intercepteur 401 prendra le relais si nécessaire
            });
        }
      }
    });
  }, []);

  if (isLoading) {
    return (
      <View style={{ flex: 1, justifyContent: 'center', alignItems: 'center', backgroundColor: colors.background }}>
        <ActivityIndicator size="large" color={colors.primary} />
      </View>
    );
  }

  return (
    <Stack.Navigator screenOptions={{ headerShown: false, animation: 'fade' }}>
      {isAuthenticated ? (
        <Stack.Screen name="Main" component={MainNavigator} />
      ) : (
        <Stack.Screen name="Auth" component={AuthNavigator} />
      )}
    </Stack.Navigator>
  );
}
