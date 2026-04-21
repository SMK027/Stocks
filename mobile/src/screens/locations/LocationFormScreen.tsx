import React, { useState, useEffect } from 'react';
import { StyleSheet, ScrollView, KeyboardAvoidingView, Platform } from 'react-native';
import { useToast } from '../../components/Toast';
import { SafeAreaView } from 'react-native-safe-area-context';
import { NativeStackScreenProps } from '@react-navigation/native-stack';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { SpacesStackParamList } from '../../types';
import { createLocation, updateLocation, getLocation } from '../../api/locations';
import { colors, spacing } from '../../theme';
import Input from '../../components/Input';
import Button from '../../components/Button';
import LoadingView from '../../components/LoadingView';

type Props = NativeStackScreenProps<SpacesStackParamList, 'LocationCreate' | 'LocationEdit'>;

export default function LocationFormScreen({ navigation, route }: Props) {
  const { spaceId } = route.params as { spaceId: number; locationId?: number };
  const locationId = (route.params as { locationId?: number }).locationId;
  const isEdit = locationId !== undefined;
  const qc = useQueryClient();
  const { error: toastError, warning: toastWarning } = useToast();

  const [name, setName] = useState('');
  const [description, setDescription] = useState('');

  const { data, isLoading } = useQuery({
    queryKey: ['location', spaceId, locationId],
    queryFn: () => getLocation(spaceId, locationId!),
    enabled: isEdit,
  });

  useEffect(() => {
    if (data) {
      setName(data.name);
      setDescription(data.description ?? '');
    }
  }, [data]);

  const mutation = useMutation({
    mutationFn: () => {
      const payload = {
        name: name.trim(),
        description: description.trim() || undefined,
      };
      return isEdit
        ? updateLocation(spaceId, locationId!, payload)
        : createLocation(spaceId, payload);
    },
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['locations', spaceId] });
      navigation.goBack();
    },
    onError: (err: any) =>
      toastError(err?.response?.data?.message ?? 'Une erreur est survenue.'),
  });

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
            placeholder="Nom de l'emplacement"
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
          <Button
            title={isEdit ? 'Enregistrer' : "Créer l'emplacement"}
            onPress={() => {
              if (!name.trim()) {
                toastWarning('Le nom est obligatoire.');
                return;
              }
              mutation.mutate();
            }}
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
