import React from 'react';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { Ionicons } from '@expo/vector-icons';
import { MainTabParamList, SpacesStackParamList } from '../types';
import { colors } from '../theme';

// Écrans
import DashboardScreen from '../screens/dashboard/DashboardScreen';
import ProfileScreen from '../screens/profile/ProfileScreen';
import SpaceListScreen from '../screens/spaces/SpaceListScreen';
import SpaceDetailScreen from '../screens/spaces/SpaceDetailScreen';
import SpaceFormScreen from '../screens/spaces/SpaceFormScreen';
import MembersScreen from '../screens/members/MembersScreen';
import CategoryListScreen from '../screens/categories/CategoryListScreen';
import CategoryFormScreen from '../screens/categories/CategoryFormScreen';
import LocationListScreen from '../screens/locations/LocationListScreen';
import LocationFormScreen from '../screens/locations/LocationFormScreen';
import ProductListScreen from '../screens/products/ProductListScreen';
import ProductFormScreen from '../screens/products/ProductFormScreen';
import InventoryScreen from '../screens/inventory/InventoryScreen';
import InventoryFormScreen from '../screens/inventory/InventoryFormScreen';
import LocationInventoryScreen from '../screens/inventory/LocationInventoryScreen';

const Tab = createBottomTabNavigator<MainTabParamList>();
const SpacesStack = createNativeStackNavigator<SpacesStackParamList>();

const headerStyle = {
  headerStyle: { backgroundColor: colors.primary },
  headerTintColor: colors.white,
  headerTitleStyle: { fontWeight: '600' as const },
};

function SpacesNavigator() {
  return (
    <SpacesStack.Navigator screenOptions={headerStyle}>
      <SpacesStack.Screen
        name="SpaceList"
        component={SpaceListScreen}
        options={{ title: 'Mes espaces' }}
      />
      <SpacesStack.Screen
        name="SpaceDetail"
        component={SpaceDetailScreen}
        options={({ route }) => ({ title: route.params.spaceName })}
      />
      <SpacesStack.Screen
        name="SpaceCreate"
        component={SpaceFormScreen}
        options={{ title: 'Nouvel espace' }}
      />
      <SpacesStack.Screen
        name="SpaceEdit"
        component={SpaceFormScreen}
        options={{ title: "Modifier l'espace" }}
      />
      <SpacesStack.Screen
        name="Members"
        component={MembersScreen}
        options={{ title: 'Membres' }}
      />
      <SpacesStack.Screen
        name="CategoryList"
        component={CategoryListScreen}
        options={{ title: 'Catégories' }}
      />
      <SpacesStack.Screen
        name="CategoryCreate"
        component={CategoryFormScreen}
        options={{ title: 'Nouvelle catégorie' }}
      />
      <SpacesStack.Screen
        name="CategoryEdit"
        component={CategoryFormScreen}
        options={{ title: 'Modifier la catégorie' }}
      />
      <SpacesStack.Screen
        name="LocationList"
        component={LocationListScreen}
        options={{ title: 'Emplacements' }}
      />
      <SpacesStack.Screen
        name="LocationCreate"
        component={LocationFormScreen}
        options={{ title: 'Nouvel emplacement' }}
      />
      <SpacesStack.Screen
        name="LocationEdit"
        component={LocationFormScreen}
        options={{ title: "Modifier l'emplacement" }}
      />
      <SpacesStack.Screen
        name="ProductList"
        component={ProductListScreen}
        options={{ title: 'Produits' }}
      />
      <SpacesStack.Screen
        name="ProductCreate"
        component={ProductFormScreen}
        options={{ title: 'Nouveau produit' }}
      />
      <SpacesStack.Screen
        name="ProductEdit"
        component={ProductFormScreen}
        options={{ title: 'Modifier le produit' }}
      />
      <SpacesStack.Screen
        name="Inventory"
        component={InventoryScreen}
        options={{ title: 'Inventaire' }}
      />
      <SpacesStack.Screen
        name="InventoryCreate"
        component={InventoryFormScreen}
        options={{ title: 'Ajouter au stock' }}
      />
      <SpacesStack.Screen
        name="InventoryEdit"
        component={InventoryFormScreen}
        options={{ title: 'Modifier le stock' }}
      />
      <SpacesStack.Screen
        name="LocationInventory"
        component={LocationInventoryScreen}
        options={({ route }) => ({ title: route.params.locationName })}
      />
    </SpacesStack.Navigator>
  );
}

export default function MainNavigator() {
  return (
    <Tab.Navigator
      screenOptions={({ route }) => ({
        headerStyle: { backgroundColor: colors.primary },
        headerTintColor: colors.white,
        headerTitleStyle: { fontWeight: '600' as const },
        tabBarActiveTintColor: colors.primary,
        tabBarInactiveTintColor: colors.textSecondary,
        tabBarStyle: {
          backgroundColor: colors.white,
          borderTopColor: colors.border,
          height: 62,
          paddingBottom: 10,
        },
        tabBarLabelStyle: { fontSize: 11, fontWeight: '500' as const },
        tabBarIcon: ({ focused, color, size }) => {
          const icons: Record<string, [string, string]> = {
            Dashboard: ['grid', 'grid-outline'],
            Spaces: ['layers', 'layers-outline'],
            Profile: ['person-circle', 'person-circle-outline'],
          };
          const [active, inactive] = icons[route.name] ?? ['ellipse', 'ellipse-outline'];
          return (
            <Ionicons
              name={(focused ? active : inactive) as keyof typeof Ionicons.glyphMap}
              size={size}
              color={color}
            />
          );
        },
      })}
    >
      <Tab.Screen
        name="Dashboard"
        component={DashboardScreen}
        options={{ title: 'Tableau de bord' }}
      />
      <Tab.Screen
        name="Spaces"
        component={SpacesNavigator}
        options={{ title: 'Espaces', headerShown: false }}
      />
      <Tab.Screen
        name="Profile"
        component={ProfileScreen}
        options={{ title: 'Profil' }}
      />
    </Tab.Navigator>
  );
}
