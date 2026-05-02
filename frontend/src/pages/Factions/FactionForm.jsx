import React, { useEffect, useRef, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import Layout from '../../components/Layout/Layout';
import Loading from '../../components/Loading/Loading';
import ErrorMessage from '../../components/ErrorMessage/ErrorMessage';
import Breadcrumb from '../../components/Breadcrumb/Breadcrumb';
import api from '../../services/api';
import factionService from '../../services/factionService';
import universeService from '../../services/universeService';
import styles from './Factions.module.css';

const DEFAULT_FORM = {
  name: '',
  description: '',
  universe_id: '',
  alignment: '',
  scope: '',
  status: 'open',
  objectives: '',
  logo: '',
  icon: '',
  headquartersDescription: '',
};

const ALIGNMENT_OPTIONS = [
  { value: 'hero', label: 'Super-héros' },
  { value: 'villain', label: 'Super-vilains' },
  { value: 'antihero', label: 'Anti-héros' },
  { value: 'vigilante', label: 'Vigilante' },
  { value: 'neutral', label: 'Neutre' },
  { value: 'other', label: 'Autre' },
];

const SCOPE_OPTIONS = [
  { value: 'galaxy', label: 'Galaxie' },
  { value: 'international', label: 'International' },
  { value: 'national', label: 'National' },
  { value: 'regional', label: 'Régional' },
  { value: 'local', label: 'Local' },
];

const FactionForm = () => {
  const { id } = useParams();
  const isEditing = Boolean(id);
  const navigate = useNavigate();
  const [form, setForm] = useState(DEFAULT_FORM);
  const [universes, setUniverses] = useState([]);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState(null);
  const [uploadingField, setUploadingField] = useState(null);
  const logoInputRef = useRef(null);
  const iconInputRef = useRef(null);

  useEffect(() => {
    const load = async () => {
      try {
        setLoading(true);
        const [universesData, factionData] = await Promise.all([
          universeService.getUniverses(),
          isEditing ? factionService.getFaction(id) : Promise.resolve(null),
        ]);
        setUniverses(universesData || []);
        if (factionData) {
          setForm({
            name: factionData.name || '',
            description: factionData.description || '',
            universe_id: factionData.universe?.id ? String(factionData.universe.id) : '',
            alignment: factionData.alignment || '',
            scope: factionData.scope || '',
            status: factionData.status || 'open',
            objectives: factionData.objectives || '',
            logo: factionData.logo || '',
            icon: factionData.icon || '',
            headquartersDescription: factionData.headquartersDescription || '',
          });
        }
      } catch (err) {
        setError('Erreur lors du chargement du formulaire');
      } finally {
        setLoading(false);
      }
    };

    load();
  }, [id, isEditing]);

  const handleChange = (event) => {
    const { name, value } = event.target;
    setForm((prev) => ({ ...prev, [name]: value }));
  };

  const handleSubmit = async (event) => {
    event.preventDefault();
    setSaving(true);
    try {
      const payload = {
        ...form,
        universe_id: Number(form.universe_id),
      };
      if (isEditing) {
        await factionService.updateFaction(id, payload);
      } else {
        await factionService.createFaction(payload);
      }
      navigate('/factions');
    } catch (err) {
      setError(err.response?.data?.error || 'Erreur lors de l\'enregistrement');
    } finally {
      setSaving(false);
    }
  };

  const handleUpload = async (targetField, file) => {
    if (!file || !file.type.startsWith('image/')) {
      alert('Veuillez sélectionner un fichier image valide.');
      return;
    }

    setUploadingField(targetField);
    const formData = new FormData();
    formData.append('file', file);

    try {
      const response = await api.post('/api/media/upload', formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      });

      if (!response.data?.url) {
        throw new Error('URL de média manquante');
      }

      setForm((prev) => ({ ...prev, [targetField]: response.data.url }));
    } catch (uploadError) {
      console.error(uploadError);
      alert(uploadError.response?.data?.error || 'Erreur lors de l\'upload');
    } finally {
      setUploadingField(null);
      if (targetField === 'logo' && logoInputRef.current) {
        logoInputRef.current.value = '';
      }
      if (targetField === 'icon' && iconInputRef.current) {
        iconInputRef.current.value = '';
      }
    }
  };

  if (loading) {
    return (
      <Layout>
        <Loading message="Chargement du formulaire..." />
      </Layout>
    );
  }

  if (error && !saving) {
    return (
      <Layout>
        <ErrorMessage message={error} onRetry={() => window.location.reload()} />
      </Layout>
    );
  }

  return (
    <Layout>
      <div className={styles.content}>
        <Breadcrumb items={[{ name: 'Accueil', url: '/', icon: 'home' }, { name: 'Factions', url: '/factions' }, { name: isEditing ? 'Modifier' : 'Créer', url: null }]} />
        <div className={styles.formCard}>
          <h1>{isEditing ? 'Modifier une faction' : 'Créer une faction'}</h1>
          <p className={styles.formSubtitle}>
            Renseignez les informations principales de la faction puis enregistrez.
          </p>

          <form className={styles.form} onSubmit={handleSubmit}>
            <div className={styles.fieldGrid}>
              <label className={styles.field}>
                <span>
                  Nom <em className={styles.required}>obligatoire</em>
                </span>
                <input name="name" value={form.name} onChange={handleChange} required className={styles.input} />
              </label>

              <label className={styles.field}>
                <span>
                  Univers <em className={styles.required}>obligatoire</em>
                </span>
                <select name="universe_id" value={form.universe_id} onChange={handleChange} required className={styles.input}>
                  <option value="">Sélectionner</option>
                  {universes.map((universe) => (
                    <option key={universe.id} value={universe.id}>{universe.name}</option>
                  ))}
                </select>
              </label>

              <label className={styles.field}>
                <span>Alignement</span>
                <select name="alignment" value={form.alignment} onChange={handleChange} className={styles.input}>
                  <option value="">Sélectionner</option>
                  {ALIGNMENT_OPTIONS.map((option) => (
                    <option key={option.value} value={option.value}>{option.label}</option>
                  ))}
                </select>
              </label>

              <label className={styles.field}>
                <span>Portée</span>
                <select name="scope" value={form.scope} onChange={handleChange} className={styles.input}>
                  <option value="">Sélectionner</option>
                  {SCOPE_OPTIONS.map((option) => (
                    <option key={option.value} value={option.value}>{option.label}</option>
                  ))}
                </select>
              </label>

              <label className={styles.field}>
                <span>Statut</span>
                <select name="status" value={form.status} onChange={handleChange} className={styles.input}>
                  <option value="open">Ouverte</option>
                  <option value="closed">Fermée</option>
                </select>
              </label>

              <label className={styles.field}>
                <span>URL du logo</span>
                <div className={styles.mediaInputRow}>
                  <input
                    name="logo"
                    value={form.logo}
                    onChange={handleChange}
                    className={styles.input}
                    placeholder="https://..."
                  />
                  <button
                    type="button"
                    className={styles.secondaryBtn}
                    onClick={() => logoInputRef.current?.click()}
                    disabled={uploadingField === 'logo'}
                  >
                    {uploadingField === 'logo' ? 'Upload...' : 'Upload'}
                  </button>
                  <input
                    ref={logoInputRef}
                    type="file"
                    accept="image/*"
                    className={styles.hiddenFileInput}
                    onChange={(event) => handleUpload('logo', event.target.files?.[0])}
                  />
                </div>
              </label>

              <label className={styles.field}>
                <span>URL de l'icône</span>
                <div className={styles.mediaInputRow}>
                  <input
                    name="icon"
                    value={form.icon}
                    onChange={handleChange}
                    className={styles.input}
                    placeholder="https://..."
                  />
                  <button
                    type="button"
                    className={styles.secondaryBtn}
                    onClick={() => iconInputRef.current?.click()}
                    disabled={uploadingField === 'icon'}
                  >
                    {uploadingField === 'icon' ? 'Upload...' : 'Upload'}
                  </button>
                  <input
                    ref={iconInputRef}
                    type="file"
                    accept="image/*"
                    className={styles.hiddenFileInput}
                    onChange={(event) => handleUpload('icon', event.target.files?.[0])}
                  />
                </div>
              </label>
            </div>

            {(form.logo || form.icon) && (
              <div className={styles.logoPreview}>
                {form.logo && (
                  <div className={styles.previewBlock}>
                    <p>Aperçu logo</p>
                    <img src={form.logo} alt="Aperçu du logo de faction" className={styles.previewImage} />
                  </div>
                )}
                {form.icon && (
                  <div className={styles.previewBlock}>
                    <p>Aperçu icône</p>
                    <img src={form.icon} alt="Aperçu de l'icône de faction" className={styles.previewIcon} />
                  </div>
                )}
              </div>
            )}

            <p className={styles.sectionTitle}>Description roleplay</p>
            <label className={styles.field}>
              <span>Emplacement du QG</span>
              <textarea
                name="headquartersDescription"
                value={form.headquartersDescription}
                onChange={handleChange}
                className={styles.textarea}
                placeholder="Décrivez où se trouve le quartier général..."
              />
            </label>

            <label className={styles.field}>
              <span>Description</span>
              <textarea name="description" value={form.description} onChange={handleChange} className={styles.textarea} />
            </label>

            <label className={styles.field}>
              <span>Objectifs</span>
              <textarea name="objectives" value={form.objectives} onChange={handleChange} className={styles.textarea} />
            </label>

            <div className={styles.actions}>
              <button type="button" className={styles.secondaryBtn} onClick={() => navigate('/factions')}>
                Annuler
              </button>
              <button type="submit" className={styles.primaryBtn} disabled={saving}>
                {saving ? 'Enregistrement...' : 'Enregistrer'}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Layout>
  );
};

export default FactionForm;

