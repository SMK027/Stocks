import React, { useState, useEffect } from 'react';
import {
  StyleSheet,
  ScrollView,
  KeyboardAvoidingView,
  Platform,
} from 'react-native';
import { useToast } from '../../components/Toast';
import { SafeAreaView } from 'react-native-safe-area-context';
import { NativeStackScreenProps } from '@react-navigation/native-stack';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { SpacesStackParamList } from '../../types';
import { createCategory, updateCategory, getCategory } from '../../api/categories';
import { colors, spacing } from '../../theme';
import Input from '../../components/Input';
import Button from '../../components/Button';
import LoadingView from '../../components/LoadingView';

type Props = NativeStackScreenProps<SpacesStackParamList, 'CategoryCreate' | 'CategoryEdit'>;

export default function CategoryFormScreen({ navigation, route }: Props) {
  const { spaceId } = route.params as { spaceId: number; categoryId?: number };
  const categoryId = (route.params as { categoryId?: number }).categoryId;
  const isEdit = categoryId !== undefined;
  const qc = useQueryClient();
  const { error: toastError, warning: toastWarning } = useToast();

  const [name, setName] = useState('');
  const [description, setDescription] = useState('');
  const [maxDays, setMaxDays] = useState('');

  const { data, isLoading } = useQuery({
    queryKey: ['category', spaceId, categoryId],
    queryFn: () => getCategory(spaceId, categoryId!),
    enabled: isEdit,
  });

  useEffect(() => {
    if (data) {
      setName(data.name);
      setDescription(data.description ?? '');
      setMaxDays(data.max_consumption_days != null ? String(data.max_consumption_days) : '');
    }
  }, [data]);

  const mutation = useMutation({
    mutationFn: () => {
      const payload = {
        name: name.trim(),
        description: description.trim() || undefined,
        max_consumption_days: maxDays ? parseInt(maxDays, 10) : null,
      };
      return isEdit
        ? updateCategory(spaceId, categoryId!, payload)
        : createCategory(spaceId, payload);
    },
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['categories', spaceId] });
      navigation.goBack();
    },
    onError: (err: any) =>
      toastError(err?.response?.data?.message ?? 'Une erreur est survenue.'),
  });

  const handleSubmit = () => {
    if (!name.trim()) {
      toastWarning('Le nom est obligatoire.');
      return;
    }
    if (maxDays && isNaN(parseInt(maxDays, 10))) {
      toastWarning('Les jours de consommation max doivent être un nombre.');
      return;
    }
    mutation.mutate();
  };

  if (isEdit && isLoading) return <LoadingView />;

  return (
    <SafeAreaView style={styles.safe} edges={['bottom', 'left', 'right']}>
      <KeyboardAvoidingView
        style={{ flex: 1 }}
        behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
      >
        <ScrollView contentContainerStyle={styles.content} keyboardShouldPersistTaps="handled">
          <Input
            label="Nom *"
            value={name}
            onChangeText={setName}
            placeholder="Nom de la catégorie"
            autoFocus={!isEdit}
          />
          <Input
            label="Description"
            value={description}
            onChangeText={setDescription}
            placeholder="Description (optionnel)"
            multiline
            numberOfLines={3}
          />
          <Input
            label="Jours de consommation max"
            value={maxDays}
            onChangeText={setMaxDays}
            keyboardType="numeric"
            placeholder="Ex : 30 (optionnel)"
          />
          <Button
            title={isEdit ? 'Enregistrer' : 'Créer la catégorie'}
            onPress={handleSubmit}
            loading={mutation.isPending}
            fullWidth
            icon={isEdit ? 'checkmark-outline' : 'add-outline'}
          />
        </ScrollView>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: { flex: 1, backgroundColor: colors.background },
  content: { padding: spacing.lg, gap: spacing.md },
});
