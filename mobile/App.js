import React, { useEffect, useState } from 'react';
import {
  ActivityIndicator,
  Pressable,
  SafeAreaView,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';

const API_BASE_URL = 'http://127.0.0.1:8000';

async function apiFetch(path, options = {}, token = null) {
  const headers = {
    'Content-Type': 'application/json',
    ...(options.headers || {}),
  };

  if (token) {
    headers.Authorization = `Bearer ${token}`;
  }

  const response = await fetch(`${API_BASE_URL}${path}`, {
    ...options,
    headers,
  });

  const data = await response.json().catch(() => null);

  if (!response.ok) {
    throw new Error(data?.message || 'Request failed');
  }

  return data;
}

function SectionTitle({ children }) {
  return <Text style={styles.sectionTitle}>{children}</Text>;
}

function ResourceCard({ item }) {
  const resource = item.resource || item;

  return (
    <View style={styles.card}>
      <Text style={styles.cardTitle}>{resource.title}</Text>
      <Text style={styles.cardMeta}>
        {resource.category?.name ? `Categorie: ${resource.category.name} | ` : ''}
        {resource.ressourceType?.label ? `Type: ${resource.ressourceType.label} | ` : ''}
        {resource.relationKind?.name ? `Public: ${resource.relationKind.name}` : 'Sans public'}
      </Text>
      <Text style={styles.cardBody} numberOfLines={4}>
        {resource.content}
      </Text>
      <Text style={styles.badge}>{resource.resourceStatus || resource.resourceStatus?.value || 'public'}</Text>
    </View>
  );
}

export default function App() {
  const [tab, setTab] = useState('feed');
  const [token, setToken] = useState(null);
  const [profile, setProfile] = useState(null);
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [resources, setResources] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const loadResources = async () => {
    setLoading(true);
    setError('');

    try {
      const data = await apiFetch('/api/resources.json', {}, token);
      setResources(data['member'] || []);
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadResources();
  }, [token]);

  const handleLogin = async () => {
    setLoading(true);
    setError('');

    try {
      const data = await apiFetch('/auth/login', {
        method: 'POST',
        body: JSON.stringify({ email, password }),
      });

      setToken(data.token);
      setProfile(data.user);
      setTab('feed');
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  const logout = () => {
    setToken(null);
    setProfile(null);
    setPassword('');
    setTab('feed');
  };

  return (
    <SafeAreaView style={styles.safeArea}>
      <View style={styles.header}>
        <Text style={styles.eyebrow}>Prototype mobile</Text>
        <Text style={styles.title}>Ressources relationnelles</Text>
        <Text style={styles.subtitle}>
          Fil mobile-first pour consulter les ressources et se connecter a l API.
        </Text>
      </View>

      <View style={styles.tabs}>
        {[
          ['feed', 'Fil'],
          ['login', token ? 'Compte' : 'Connexion'],
          ['about', 'Aide'],
        ].map(([key, label]) => (
          <Pressable
            key={key}
            onPress={() => setTab(key)}
            style={[styles.tabButton, tab === key && styles.tabButtonActive]}
          >
            <Text style={[styles.tabButtonText, tab === key && styles.tabButtonTextActive]}>{label}</Text>
          </Pressable>
        ))}
      </View>

      <ScrollView contentContainerStyle={styles.content}>
        {tab === 'feed' && (
          <View>
            <SectionTitle>Catalogue mobile</SectionTitle>
            <Text style={styles.note}>
              Les ressources publiques sont visibles sans connexion. Une fois connecte, le mobile consomme aussi les ressources partagees.
            </Text>

            <Pressable onPress={loadResources} style={styles.primaryButton}>
              <Text style={styles.primaryButtonText}>Rafraichir le fil</Text>
            </Pressable>

            {loading && <ActivityIndicator style={styles.loader} />}
            {!!error && <Text style={styles.error}>{error}</Text>}

            <View style={styles.list}>
              {resources.map((resource) => (
                <ResourceCard key={resource.id} item={resource} />
              ))}
            </View>
          </View>
        )}

        {tab === 'login' && (
          <View>
            <SectionTitle>{token ? 'Compte connecte' : 'Connexion API'}</SectionTitle>

            {!token ? (
              <View style={styles.form}>
                <TextInput
                  value={email}
                  onChangeText={setEmail}
                  autoCapitalize="none"
                  keyboardType="email-address"
                  placeholder="Email"
                  style={styles.input}
                />
                <TextInput
                  value={password}
                  onChangeText={setPassword}
                  secureTextEntry
                  placeholder="Mot de passe"
                  style={styles.input}
                />
                <Pressable onPress={handleLogin} style={styles.primaryButton}>
                  <Text style={styles.primaryButtonText}>Se connecter</Text>
                </Pressable>
              </View>
            ) : (
              <View style={styles.card}>
                <Text style={styles.cardTitle}>{profile?.username || profile?.email}</Text>
                <Text style={styles.cardBody}>Email: {profile?.email}</Text>
                <Text style={styles.cardBody}>Compte verifie: {profile?.isVerified ? 'oui' : 'non'}</Text>
                <Pressable onPress={logout} style={styles.secondaryButton}>
                  <Text style={styles.secondaryButtonText}>Se deconnecter</Text>
                </Pressable>
              </View>
            )}

            {!!error && <Text style={styles.error}>{error}</Text>}
          </View>
        )}

        {tab === 'about' && (
          <View>
            <SectionTitle>Aide mobile</SectionTitle>
            <View style={styles.card}>
              <Text style={styles.cardBody}>
                Ce prototype React Native couvre le besoin "mobile first" du sujet avec un parcours minimal:
                consultation du catalogue, connexion JWT et affichage du profil connecte.
              </Text>
              <Text style={styles.cardBody}>
                Endpoints utilises: /auth/login, /api/resources.json, /api/verify-token.
              </Text>
            </View>
          </View>
        )}
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: {
    flex: 1,
    backgroundColor: '#f4f6f0',
  },
  header: {
    paddingHorizontal: 20,
    paddingTop: 18,
    paddingBottom: 12,
    backgroundColor: '#17313e',
  },
  eyebrow: {
    color: '#cce0d8',
    fontSize: 12,
    textTransform: 'uppercase',
    letterSpacing: 1,
    marginBottom: 6,
  },
  title: {
    color: '#ffffff',
    fontSize: 24,
    fontWeight: '700',
  },
  subtitle: {
    color: '#d8e7e1',
    marginTop: 6,
    lineHeight: 20,
  },
  tabs: {
    flexDirection: 'row',
    padding: 12,
    gap: 8,
    backgroundColor: '#eef2e5',
  },
  tabButton: {
    flex: 1,
    paddingVertical: 12,
    borderRadius: 999,
    backgroundColor: '#ffffff',
    alignItems: 'center',
  },
  tabButtonActive: {
    backgroundColor: '#b75d2b',
  },
  tabButtonText: {
    color: '#17313e',
    fontWeight: '600',
  },
  tabButtonTextActive: {
    color: '#ffffff',
  },
  content: {
    padding: 16,
    gap: 16,
  },
  sectionTitle: {
    fontSize: 20,
    fontWeight: '700',
    color: '#17313e',
    marginBottom: 12,
  },
  note: {
    color: '#4d5a63',
    marginBottom: 12,
    lineHeight: 20,
  },
  form: {
    gap: 12,
  },
  input: {
    backgroundColor: '#ffffff',
    borderRadius: 14,
    paddingHorizontal: 14,
    paddingVertical: 12,
    borderWidth: 1,
    borderColor: '#d7dfd8',
  },
  primaryButton: {
    backgroundColor: '#b75d2b',
    borderRadius: 14,
    paddingVertical: 13,
    alignItems: 'center',
    marginBottom: 12,
  },
  primaryButtonText: {
    color: '#ffffff',
    fontWeight: '700',
  },
  secondaryButton: {
    backgroundColor: '#17313e',
    borderRadius: 14,
    paddingVertical: 13,
    alignItems: 'center',
    marginTop: 12,
  },
  secondaryButtonText: {
    color: '#ffffff',
    fontWeight: '700',
  },
  loader: {
    marginTop: 12,
  },
  error: {
    color: '#b42318',
    marginTop: 8,
    marginBottom: 8,
  },
  list: {
    gap: 12,
  },
  card: {
    backgroundColor: '#ffffff',
    borderRadius: 18,
    padding: 16,
    marginBottom: 12,
    borderWidth: 1,
    borderColor: '#dde3db',
  },
  cardTitle: {
    fontSize: 16,
    fontWeight: '700',
    color: '#17313e',
    marginBottom: 8,
  },
  cardMeta: {
    color: '#6a747c',
    marginBottom: 10,
  },
  cardBody: {
    color: '#29343b',
    lineHeight: 20,
  },
  badge: {
    marginTop: 12,
    alignSelf: 'flex-start',
    paddingHorizontal: 10,
    paddingVertical: 6,
    borderRadius: 999,
    backgroundColor: '#eef2e5',
    color: '#17313e',
    fontWeight: '600',
    overflow: 'hidden',
  },
});
