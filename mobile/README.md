# Stocks Mobile

Application mobile React Native (Expo) pour la gestion de stocks, utilisant l'API de [stocks.leofranz.fr](https://stocks.leofranz.fr).

## Stack technique

- **Expo SDK 52** + React Native 0.76
- **TypeScript** strict
- **React Navigation 6** (Stack + Bottom Tabs)
- **TanStack Query v5** — cache et synchronisation serveur
- **Zustand v5** — état auth local
- **Axios** — client HTTP avec intercepteurs JWT
- **AsyncStorage** — persistance du token

## Prérequis

- Node.js 18+
- Expo CLI : `npm install -g expo-cli`
- Compte sur [stocks.leofranz.fr](https://stocks.leofranz.fr) (créé via le site web)

## Installation

```bash
cd mobile
npm install
```

## Démarrage

```bash
# Serveur de développement
npm start

# Android (émulateur ou appareil)
npm run android

# iOS (macOS requis)
npm run ios
```

Scannez le QR code avec **Expo Go** sur votre téléphone.

## Build production

Configurez EAS Build :

```bash
npm install -g eas-cli
eas login
eas build -p android   # APK / AAB
eas build -p ios       # IPA
```

## Structure du projet

```
mobile/
├── App.tsx                         # Point d'entrée
├── src/
│   ├── api/                        # Couche HTTP (axios)
│   │   ├── client.ts               # Instance axios + intercepteurs JWT
│   │   ├── auth.ts                 # Login, me, updateMe, changePassword
│   │   ├── spaces.ts               # Espaces + membres
│   │   ├── categories.ts
│   │   ├── locations.ts
│   │   ├── products.ts
│   │   └── inventory.ts            # Inventaire, casse, expiry
│   ├── store/
│   │   └── authStore.ts            # Zustand : token + user
│   ├── navigation/
│   │   ├── AppNavigator.tsx        # Racine (Auth / Main)
│   │   ├── AuthNavigator.tsx       # Stack connexion
│   │   └── MainNavigator.tsx       # Tabs + Stacks imbriqués
│   ├── screens/
│   │   ├── auth/LoginScreen.tsx
│   │   ├── dashboard/DashboardScreen.tsx
│   │   ├── spaces/                 # Liste, Détail, Formulaire
│   │   ├── members/MembersScreen.tsx
│   │   ├── categories/             # Liste + Formulaire
│   │   ├── locations/              # Liste + Formulaire
│   │   ├── products/               # Liste + Formulaire
│   │   ├── inventory/              # Écran principal + Formulaire
│   │   └── profile/ProfileScreen.tsx
│   ├── components/                 # UI réutilisable
│   │   ├── Button.tsx
│   │   ├── Input.tsx
│   │   ├── Card.tsx
│   │   ├── Badge.tsx
│   │   ├── EmptyState.tsx
│   │   ├── LoadingView.tsx
│   │   └── ErrorView.tsx
│   ├── theme/index.ts              # Couleurs, espacements, typographie
│   └── types/index.ts              # Interfaces TypeScript
```

## Fonctionnalités

| Fonctionnalité | Description |
|---|---|
| 🔐 Connexion | JWT — token persisté dans AsyncStorage |
| 🏠 Tableau de bord | Vue globale des espaces |
| 📦 Espaces | CRUD complet + accès rapide aux sous-modules |
| 👥 Membres | Ajouter, modifier le rôle, retirer |
| 🏷 Catégories | CRUD avec jours de consommation max |
| 📍 Emplacements | CRUD zones de stockage |
| 🛒 Produits | CRUD avec affectation multi-catégories |
| 📋 Inventaire | Actif / Expirant (J-7) / Périmé / Casse |
| ✏️ Actions inventaire | Diminuer (-1), Mettre en casse, Remettre, Supprimer |
| 👤 Profil | Modifier infos, changer mot de passe, résumé quotidien |

## Sécurité

- Le token JWT est envoyé en header `Authorization: Bearer` sur chaque requête
- Un 401 déclenche une déconnexion automatique
- Aucun mot de passe n'est stocké localement
- Les dates d'expiration ne sont qu'affichées — aucun champ n'est présupposé côté client
