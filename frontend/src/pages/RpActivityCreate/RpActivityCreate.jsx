import React, { useEffect, useMemo, useRef, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import Layout from '../../components/Layout/Layout';
import Breadcrumb from '../../components/Breadcrumb/Breadcrumb';
import Loading from '../../components/Loading/Loading';
import ErrorMessage from '../../components/ErrorMessage/ErrorMessage';
import RichTextComposer from '../../components/RichTextComposer/RichTextComposerTiptap';
import { useUniverseTheme } from '../../contexts/UniverseThemeContext';
import rpActivityService from '../../services/rpActivityService';
import factionService from '../../services/factionService';
import api from '../../services/api';
import styles from './RpActivityCreate.module.css';

const normalizeDateValue = (value) => {
  if (!value) return '';
  return String(value).slice(0, 10);
};

const getPlainText = (html) => {
  const parsed = new DOMParser().parseFromString(html || '', 'text/html');
  return (parsed.body.textContent || '').replace(/\u00a0/g, ' ').trim();
};

const RpActivityCreate = () => {
  const navigate = useNavigate();
  const { currentUniverse } = useUniverseTheme();
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [uploading, setUploading] = useState(false);
  const [error, setError] = useState('');
  const [universe, setUniverse] = useState(null);
  const [factions, setFactions] = useState([]);
  const [permissions, setPermissions] = useState({ canCreate: false });
  const fileInputRef = useRef(null);
  const [form, setForm] = useState({
    kind: 'event',
    factionId: '',
    title: '',
    status: 'open',
    description: '',
    openingSpeech: '',
    illustrationUrl: '',
    registrationEndAt: '',
    reminderAt: '',
  });

  const universeSlug = useMemo(
    () => (currentUniverse && currentUniverse !== 'portal' ? currentUniverse : ''),
    [currentUniverse]
  );

  useEffect(() => {
    const loadContext = async () => {
      if (!universeSlug) {
        setLoading(false);
        setError('Sélectionne un univers avant de créer un event.');
        return;
      }

      setLoading(true);
      setError('');
      try {
        const [data, factionsData] = await Promise.all([
          rpActivityService.getUniverseActivities(universeSlug),
          factionService.getFactions({ universe: universeSlug }).catch(() => ({ factions: [] })),
        ]);
        const nextUniverse = data?.universe || null;
        const nextPermissions = data?.permissions || { canCreate: false };
        const nextFactions = Array.isArray(factionsData?.factions) ? factionsData.factions : [];
        setUniverse(nextUniverse);
        setPermissions(nextPermissions);
        setFactions(nextFactions);
        if (!nextPermissions.canCreate) {
          setError('Tu n’as pas les permissions pour créer un event dans cet univers.');
        }
      } catch (err) {
        setError(err.response?.data?.error || 'Impossible de charger le contexte de création.');
      } finally {
        setLoading(false);
      }
    };

    loadContext();
  }, [universeSlug]);

  const handleUpload = async (file) => {
    if (!file || !file.type.startsWith('image/')) {
      setError('Sélectionne une image valide.');
      return;
    }

    setUploading(true);
    setError('');
    const formData = new FormData();
    formData.append('file', file);
    try {
      const response = await api.post('/api/media/upload', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });
      if (!response?.data?.url) {
        throw new Error('URL image manquante');
      }
      setForm((prev) => ({ ...prev, illustrationUrl: response.data.url }));
    } catch (err) {
      setError(err.response?.data?.error || 'Upload impossible.');
    } finally {
      setUploading(false);
      if (fileInputRef.current) {
        fileInputRef.current.value = '';
      }
    }
  };

  const updateDateField = (field, nextValue) => {
    setForm((prev) => ({ ...prev, [field]: nextValue || '' }));
  };

  const clearDateTimeField = (field) => {
    setForm((prev) => ({ ...prev, [field]: '' }));
  };

  const handleSubmit = async (event) => {
    event.preventDefault();
    const descriptionText = getPlainText(form.description);
    if (!universe?.id) {
      setError('Univers introuvable.');
      return;
    }
    if (!permissions?.canCreate) {
      setError('Permissions insuffisantes pour créer cet event.');
      return;
    }
    if (!form.title.trim()) {
      setError('Le titre est requis.');
      return;
    }
    if (!descriptionText) {
      setError('La description est requise.');
      return;
    }
    if (form.kind === 'mission' && !form.factionId) {
      setError('La faction est requise pour créer une mission.');
      return;
    }

    setSaving(true);
    setError('');
    try {
      const payload = {
        universeId: universe.id,
        kind: form.kind,
        title: form.title.trim(),
        status: form.status,
        description: form.description || '',
        openingSpeech: form.openingSpeech || null,
        illustrationUrl: form.illustrationUrl || null,
        registrationEndAt: form.registrationEndAt || null,
        reminderAt: form.reminderAt || null,
        factionId: form.kind === 'mission' ? Number(form.factionId) : null,
      };
      const response = await rpActivityService.createActivity(payload);
      const createdId = response?.item?.id;
      if (createdId) {
        navigate(`/rp-activities/${createdId}`);
        return;
      }
      navigate('/rp-activities');
    } catch (err) {
      setError(err.response?.data?.error || 'Création de l’event impossible.');
    } finally {
      setSaving(false);
    }
  };

  if (loading) {
    return (
      <Layout>
        <Loading message="Chargement de la création d’event..." />
      </Layout>
    );
  }

  if (error && !permissions?.canCreate) {
    return (
      <Layout>
        <ErrorMessage message={error} onRetry={() => navigate('/rp-activities')} />
      </Layout>
    );
  }

  const registrationEndAtDate = normalizeDateValue(form.registrationEndAt);
  const reminderAtDate = normalizeDateValue(form.reminderAt);

  return (
    <Layout>
      <div className={styles.content}>
        <Breadcrumb
          items={[
            { name: 'FORUMS', url: '/forums' },
            { name: 'Events & Missions', url: '/rp-activities' },
            { name: 'Créer une activité', url: null },
          ]}
        />

        <header className={styles.header}>
          <h1 className={styles.title}>Créer un event / une mission RP</h1>
          <p className={styles.subtitle}>
            Univers: {universe?.name || 'Inconnu'}
          </p>
        </header>

        <form className={styles.form} onSubmit={handleSubmit}>
          <label className={styles.field}>
            <span>Type</span>
            <select
              value={form.kind}
              onChange={(e) => setForm((prev) => ({
                ...prev,
                kind: e.target.value,
                factionId: e.target.value === 'mission' ? prev.factionId : '',
              }))}
              className={styles.input}
            >
              <option value="event">Event</option>
              <option value="mission">Mission</option>
            </select>
          </label>

          {form.kind === 'mission' && (
            <label className={styles.field}>
              <span>Faction liée</span>
              <select
                value={form.factionId}
                onChange={(e) => setForm((prev) => ({ ...prev, factionId: e.target.value }))}
                className={styles.input}
                required
              >
                <option value="">Sélectionner une faction</option>
                {factions.map((faction) => (
                  <option key={faction.id} value={faction.id}>
                    {faction.name}
                  </option>
                ))}
              </select>
            </label>
          )}

          <label className={styles.field}>
            <span>Titre</span>
            <input
              value={form.title}
              onChange={(e) => setForm((prev) => ({ ...prev, title: e.target.value }))}
              className={styles.input}
              placeholder="Titre de l’event"
              required
            />
          </label>

          <label className={styles.field}>
            <span>Statut</span>
            <select
              value={form.status}
              onChange={(e) => setForm((prev) => ({ ...prev, status: e.target.value }))}
              className={styles.input}
            >
              <option value="draft">Brouillon</option>
              <option value="open">Ouvert</option>
              <option value="closed">Fermé</option>
            </select>
          </label>

          <div className={`${styles.field} ${styles.dateTimeField}`}>
            <span>Fin des inscriptions (optionnel)</span>
            <div className={styles.dateTimeRow}>
              <input
                type="date"
                value={registrationEndAtDate}
                onChange={(e) => updateDateField('registrationEndAt', e.target.value)}
                className={styles.input}
                lang="fr-FR"
                aria-label="Date de fin des inscriptions"
              />
              <button
                type="button"
                className={styles.secondaryButton}
                onClick={() => clearDateTimeField('registrationEndAt')}
                disabled={!form.registrationEndAt}
              >
                Effacer
              </button>
            </div>
          </div>

          <div className={`${styles.field} ${styles.dateTimeField}`}>
            <span>Date de relance</span>
            <div className={styles.dateTimeRow}>
              <input
                type="date"
                value={reminderAtDate}
                onChange={(e) => updateDateField('reminderAt', e.target.value)}
                className={styles.input}
                lang="fr-FR"
                aria-label="Date de relance"
              />
              <button
                type="button"
                className={styles.secondaryButton}
                onClick={() => clearDateTimeField('reminderAt')}
                disabled={!form.reminderAt}
              >
                Effacer
              </button>
            </div>
          </div>

          <label className={styles.field}>
            <span>Illustration</span>
            <div className={styles.mediaRow}>
              <input
                value={form.illustrationUrl}
                onChange={(e) => setForm((prev) => ({ ...prev, illustrationUrl: e.target.value }))}
                className={styles.input}
                placeholder="https://..."
              />
              <button
                type="button"
                className={styles.secondaryButton}
                onClick={() => fileInputRef.current?.click()}
                disabled={uploading}
              >
                {uploading ? 'Upload...' : 'Uploader'}
              </button>
              <input
                ref={fileInputRef}
                type="file"
                accept="image/*"
                className={styles.hiddenFileInput}
                onChange={(e) => handleUpload(e.target.files?.[0])}
              />
            </div>
          </label>

          {form.illustrationUrl && (
            <div className={styles.preview}>
              <img src={form.illustrationUrl} alt="Illustration event" />
            </div>
          )}

          <div className={styles.field}>
            <span>Description</span>
            <RichTextComposer
              value={form.description}
              onChange={(value) => setForm((prev) => ({ ...prev, description: value }))}
            />
          </div>

          <div className={styles.field}>
            <span>Speech d’ouverture</span>
            <RichTextComposer
              value={form.openingSpeech}
              onChange={(value) => setForm((prev) => ({ ...prev, openingSpeech: value }))}
            />
          </div>

          {error && <p className={styles.error}>{error}</p>}

          <div className={styles.actions}>
            <button type="button" className={styles.secondaryButton} onClick={() => navigate('/rp-activities')}>
              Annuler
            </button>
            <button type="submit" className={styles.primaryButton} disabled={saving || uploading}>
              {saving ? 'Création...' : form.kind === 'mission' ? 'Créer la mission' : 'Créer l’event'}
            </button>
          </div>
        </form>
      </div>
    </Layout>
  );
};

export default RpActivityCreate;
