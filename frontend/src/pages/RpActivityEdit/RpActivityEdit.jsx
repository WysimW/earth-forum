import React, { useEffect, useRef, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import Layout from '../../components/Layout/Layout';
import Breadcrumb from '../../components/Breadcrumb/Breadcrumb';
import Loading from '../../components/Loading/Loading';
import ErrorMessage from '../../components/ErrorMessage/ErrorMessage';
import RichTextComposer from '../../components/RichTextComposer/RichTextComposerTiptap';
import rpActivityService from '../../services/rpActivityService';
import api from '../../services/api';
import styles from '../RpActivityCreate/RpActivityCreate.module.css';

const normalizeDateValue = (value) => {
  if (!value) return '';
  return String(value).slice(0, 10);
};

const getPlainText = (html) => {
  const parsed = new DOMParser().parseFromString(html || '', 'text/html');
  return (parsed.body.textContent || '').replace(/\u00a0/g, ' ').trim();
};

const RpActivityEdit = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const fileInputRef = useRef(null);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [uploading, setUploading] = useState(false);
  const [error, setError] = useState('');
  const [activity, setActivity] = useState(null);
  const [form, setForm] = useState({
    title: '',
    status: 'open',
    description: '',
    openingSpeech: '',
    illustrationUrl: '',
    registrationEndAt: '',
    reminderAt: '',
  });

  useEffect(() => {
    const fetchActivity = async () => {
      setLoading(true);
      setError('');
      try {
        const data = await rpActivityService.getActivity(id);
        if (!data?.permissions?.canEdit) {
          setError('Tu ne peux pas modifier cet event.');
          setActivity(null);
          return;
        }
        setActivity(data);
        setForm({
          title: data.title || '',
          status: data.status || 'open',
          description: data.description || '',
          openingSpeech: data.openingSpeech || '',
          illustrationUrl: data.illustrationUrl || '',
          registrationEndAt: data.registrationEndAt ? String(data.registrationEndAt).slice(0, 10) : '',
          reminderAt: data.reminderAt ? String(data.reminderAt).slice(0, 10) : '',
        });
      } catch (err) {
        setError(err.response?.data?.error || 'Impossible de charger cet event.');
        setActivity(null);
      } finally {
        setLoading(false);
      }
    };

    fetchActivity();
  }, [id]);

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
    if (!activity?.universe?.id) {
      setError('Univers introuvable.');
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

    setSaving(true);
    setError('');
    try {
      const payload = {
        universeId: activity.universe.id,
        kind: activity.kind,
        title: form.title.trim(),
        status: form.status,
        description: form.description || '',
        openingSpeech: form.openingSpeech || null,
        illustrationUrl: form.illustrationUrl || null,
        registrationEndAt: form.registrationEndAt || null,
        reminderAt: form.reminderAt || null,
        factionId: activity.faction?.id || null,
      };
      await rpActivityService.updateActivity(id, payload);
      navigate(`/rp-activities/${id}`);
    } catch (err) {
      setError(err.response?.data?.error || 'Modification impossible.');
    } finally {
      setSaving(false);
    }
  };

  if (loading) {
    return (
      <Layout>
        <Loading message="Chargement de l’édition..." />
      </Layout>
    );
  }

  if (error && !activity) {
    return (
      <Layout>
        <ErrorMessage message={error} onRetry={() => navigate(`/rp-activities/${id}`)} />
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
            { name: activity?.title || 'Event', url: `/rp-activities/${id}` },
            { name: 'Éditer', url: null },
          ]}
        />

        <header className={styles.header}>
          <h1 className={styles.title}>Éditer l’event</h1>
          <p className={styles.subtitle}>Modifie le contenu publié de l’activité.</p>
        </header>

        <form className={styles.form} onSubmit={handleSubmit}>
          <label className={styles.field}>
            <span>Titre</span>
            <input
              value={form.title}
              onChange={(e) => setForm((prev) => ({ ...prev, title: e.target.value }))}
              className={styles.input}
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

          <div className={styles.field}>
            <span>Description</span>
            <RichTextComposer
              value={form.description}
              onChange={(value) => setForm((prev) => ({ ...prev, description: value }))}
            />
          </div>

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
            <span>Speech d’ouverture</span>
            <RichTextComposer
              value={form.openingSpeech}
              onChange={(value) => setForm((prev) => ({ ...prev, openingSpeech: value }))}
            />
          </div>

          {error && <p className={styles.error}>{error}</p>}

          <div className={styles.actions}>
            <button type="button" className={styles.secondaryButton} onClick={() => navigate(`/rp-activities/${id}`)}>
              Annuler
            </button>
            <button type="submit" className={styles.primaryButton} disabled={saving || uploading}>
              {saving ? 'Enregistrement...' : 'Enregistrer'}
            </button>
          </div>
        </form>
      </div>
    </Layout>
  );
};

export default RpActivityEdit;
