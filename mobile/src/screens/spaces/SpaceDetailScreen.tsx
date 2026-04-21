import React from 'react';
import { View, Text, StyleSheet, ScrollView, TouchableOpacity } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { NativeStackScreenProps } from '@react-navigation/native-stack';
import { Ionicons } from '@expo/vector-icons';
import { SpacesStackParamList } from '../../types';
import { colors, spacing, typography, borderRadius, shadows } from '../../theme';

type Props = NativeStackScreenProps<SpacesStackParamList, 'SpaceDetail'>;

type Section = {
  key: keyof SpacesStackParamList;
  icon: keyof typeof Ionicons.glyphMap;
  label: string;
  subtitle: string;
  color: string;
};

const SECTIONS: Section[] = [
  {
    key: 'Inventory',
    icon: 'cube',
    label: 'Inventaire',
    subtitle: 'Gérer les stocks',
    color: colors.primary,
  },
  {
    key: 'ProductList',
    icon: 'barcode',
    label: 'Produits',
    subtitle: 'Catalogue produits',
    color: colors.secondary,
  },
  {
    key: 'CategoryList',
    icon: 'pricetag',
    label: 'Catégories',
    subtitle: 'Organiser les produits',
    color: colors.warning,
  },
  {
    key: 'LocationList',
    icon: 'location',
    label: 'Emplacements',
    subtitle: 'Zones de stockage',
    color: colors.info,
  },
  {
    key: 'Members',
    icon: 'people',
    label: 'Membres',
    subtitle: 'Gérer les accès',
    color: colors.purple,
  },
];

export default function SpaceDetailScreen({ navigation, route }: Props) {
  const { spaceId, spaceName } = route.params;

  return (
    <SafeAreaView style={styles.safe} edges={['bottom', 'left', 'right']}>
      <ScrollView contentContainerStyle={styles.content} showsVerticalScrollIndicator={false}>
        {/* En-tête espace */}
        <View style={styles.header}>
          <View style={styles.avatar}>
            <Text style={styles.avatarText}>{spaceName.charAt(0).toUpperCase()}</Text>
          </View>
          <Text style={styles.title}>{spaceName}</Text>
        </View>

        <Text style={styles.sectionTitle}>Gestion</Text>

        <View style={styles.grid}>
          {SECTIONS.map(({ key, icon, label, subtitle, color }) => (
            <TouchableOpacity
              key={key}
              style={styles.tile}
              onPress={() => navigation.navigate(key as any, { spaceId, spaceName })}
              activeOpacity={0.75}
            >
              <View style={[styles.tileIcon, { backgroundColor: color + '20' }]}>
                <Ionicons name={icon} size={28} color={color} />
              </View>
              <Text style={styles.tileLabel}>{label}</Text>
              <Text style={styles.tileSub}>{subtitle}</Text>
              <Ionicons
                name="chevron-forward"
                size={16}
                color={colors.textMuted}
                style={styles.tileArrow}
              />
            </TouchableOpacity>
          ))}
        </View>
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: { flex: 1, backgroundColor: colors.background },
  content: { padding: spacing.md, gap: spacing.lg, paddingBottom: spacing.xl },

  header: {
    alignItems: 'center',
    backgroundColor: colors.card,
    borderRadius: borderRadius.xl,
    padding: spacing.xl,
    ...shadows.sm,
    gap: spacing.sm,
  },
  avatar: {
    width: 72,
    height: 72,
    borderRadius: 36,
    backgroundColor: colors.primaryLight,
    justifyContent: 'center',
    alignItems: 'center',
  },
  avatarText: { fontSize: 32, fontWeight: '800', color: colors.primary },
  title: { ...typography.h2, color: colors.text },

  sectionTitle: { ...typography.h3, color: colors.text, paddingHorizontal: spacing.xs },

  grid: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm },
  tile: {
    width: '48%',
    backgroundColor: colors.card,
    borderRadius: borderRadius.lg,
    padding: spacing.md,
    gap: 4,
    ...shadows.sm,
  },
  tileIcon: {
    width: 48,
    height: 48,
    borderRadius: borderRadius.md,
    justifyContent: 'center',
    alignItems: 'center',
    marginBottom: spacing.xs,
  },
  tileLabel: { ...typography.h3, color: colors.text },
  tileSub: { ...typography.caption, color: colors.textSecondary },
  tileArrow: { position: 'absolute', top: spacing.md, right: spacing.md },
});
