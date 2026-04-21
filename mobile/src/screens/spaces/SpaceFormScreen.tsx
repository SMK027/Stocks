import React, { useState, useEffect } from 'react';
import {
  View,
  StyleSheet,
  ScrollView,
  Alert,
  KeyboardAvoidingView,
  Platform,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { NativeStackScreenProps } from '@react-navigation/native-stack';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { SpacesStackParamList } from '../../types';
import { createSpace, updateSpace, getSpace } from '../../api/spaces';
import { colors, spacing } from '../../theme';
import Input from '../../components/Input';
import Button from '../../components/Button';
import LoadingView from '../../components/LoadingView';

type Props = NativeStackScreenProps<SpacesStackParamList, 'SpaceCreate' | 'SpaceEdit'>;

export default function SpaceFormScreen({ navigation, route }: Props) {
  const spaceId = (route.params as { spaceId?: number })?.spaceId;
  const isEdit = spaceId !== undefined;
  const qc = useQueryClient();

  const [name, setName] = useState('');
  const [description, setDescription] = useState('');

  const { data: space, isLoading } = useQuery({
    queryKey: ['space', spaceId],
    queryFn: () => getSpace(spaceId!),
    enabled: isEdit,
  });

  useEffect(() => {
    if (space) {
      setName(space.name);
      setDescription(space.description ?? '');
    }
  }, [space]);

  const mutation = useMutation({
    mutationFn: () =>
      isEdit
        ? updateSpace(spaceId!, { name: name.trim(), description: description.trim() || undefined })
        : createSpace({ name: name.trim(), description: description.trim() || undefined }),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['spaces'] });
      navigation.goBack();
    },
    onError: (err: any) =>
      Alert.alert('Erreur', err?.response?.data?.message ?? 'Une erreur est survenue.'),
  });

  const handleSubmit = () => {
    if (!name.trim()) {
      Alert.alert('Champ requis', "Le nom de l'espace est obligatoire.");
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
            placeholder="Nom de l'espace"
            autoFocus={!isEdit}
            returnKeyType="next"
          />
          <Input
            label="Description"
            value={description}
            onChangeText={setDescription}
            placeholder="Description (optionnel)"
            multiline
            numberOfLines={3}
            style={{ minHeight: 90 }}
          />
          <Button
            title={isEdit ? 'Enregistrer' : "Créer l'espace"}
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
