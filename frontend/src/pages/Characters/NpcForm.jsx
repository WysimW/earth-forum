import React, { useState, useEffect } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import Layout from '../../components/Layout/Layout';
import Loading from '../../components/Loading/Loading';
import ErrorMessage from '../../components/ErrorMessage/ErrorMessage';
import AvatarCropper from '../../components/AvatarCropper/AvatarCropper';
import { npcApi } from '../../services/characterApi';
import api from '../../services/api';
import universeService from '../../services/universeService';
import styles from './CharacterForm.module.css';

const STEPS = [
  { id: 1, title: 'Informations de base', key: 'basic' },
  { id: 2, title: 'Univers et contexte', key: 'universe' },
  { id: 3, title: 'Détails personnels', key: 'personal' },
  { id: 4, title: 'Description', key: 'description' },
  { id: 5, title: 'Rôle et relations', key: 'role' },
];

const NpcForm = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const [currentStep, setCurrentStep] = useState(1);
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState(null);
  const [universes, setUniverses] = useState([]);
  const [elseworlds, setElseworlds] = useState([]);
  const [avatarCropperOpen, setAvatarCropperOpen] = useState(false);

  const [formData, setFormData] = useState({
    name: '',
    firstName: '',
    lastName: '',
    pseudonyms: '',
    avatar: '',
    universe_id: null,
    elseworld_id: null,
    age: '',
    gender: '',
    moralAffiliation: '',
    factions: '',
    occupation: '',
    biography: '',
    personality: '',
    appearance: '',
    abilities: '',
    equipment: '',
    weaknesses: '',
    roleInStory: '',
    relationships: '',
    quests: '',
    secrets: '',
  });

  useEffect(() => {
    fetchUniverses();
    if (id) {
      fetchNpc();
    }
  }, [id]);

  useEffect(() => {
    if (formData.universe_id) {
      fetchElseworlds(formData.universe_id);
    } else {
      setElseworlds([]);
    }
  }, [formData.universe_id]);

  const fetchUniverses = async () => {
    try {
      const data = await universeService.getUniverses();
      setUniverses(data);
    } catch (err) {
      console.error('Error fetching universes:', err);
    }
  };

  const fetchElseworlds = async (universeId) => {
    try {
      const response = await api.get(`/ajax/elseworlds/universe/${universeId}`);
      setElseworlds(response.data || []);
    } catch (err) {
      console.error('Error fetching elseworlds:', err);
      setElseworlds([]);
    }
  };

  const fetchNpc = async () => {
    try {
      setLoading(true);
      const data = await npcApi.getNpc(id);
      const npc = data.npc || data;
      
      setFormData({
        name: npc.name || '',
        firstName: npc.firstName || '',
        lastName: npc.lastName || '',
        pseudonyms: npc.pseudonyms || '',
        avatar: npc.avatar || '',
        universe_id: npc.universe?.id || null,
        elseworld_id: npc.elseworld?.id || null,
        age: npc.age || '',
        gender: npc.gender || '',
        moralAffiliation: npc.moralAffiliation || '',
        factions: npc.factions || '',
        occupation: npc.occupation || '',
        biography: npc.biography || '',
        personality: npc.personality || '',
        appearance: npc.appearance || '',
        abilities: npc.abilities || '',
        equipment: npc.equipment || '',
        weaknesses: npc.weaknesses || '',
        roleInStory: npc.roleInStory || '',
        relationships: npc.relationships || '',
        quests: npc.quests || '',
        secrets: npc.secrets || '',
      });
    } catch (err) {
      setError('Erreur lors du chargement du PNJ');
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  const handleChange = (field, value) => {
    setFormData(prev => ({ ...prev, [field]: value }));
  };

  const handleSaveDraft = async () => {
    try {
      setSaving(true);
      const payload = {
        ...formData,
        isDraft: true,
      };

      if (id) {
        await npcApi.updateNpc(id, payload);
      } else {
        await npcApi.createNpc(payload, true);
      }
      
      alert('Brouillon sauvegardé avec succès');
    } catch (err) {
      alert('Erreur lors de la sauvegarde');
      console.error(err);
    } finally {
      setSaving(false);
    }
  };

  const handleSubmit = async () => {
    if (!formData.name) {
      alert('Le nom est requis');
      return;
    }

    try {
      setSaving(true);
      const payload = {
        ...formData,
        isDraft: false,
      };

      if (id) {
        await npcApi.updateNpc(id, payload);
      } else {
        await npcApi.createNpc(payload, false);
      }
      
      navigate('/characters');
    } catch (err) {
      alert('Erreur lors de la soumission');
      console.error(err);
    } finally {
      setSaving(false);
    }
  };

  const handleAvatarSelect = (media) => {
    handleChange('avatar', media.url);
    setAvatarCropperOpen(false);
  };

  const nextStep = () => {
    if (currentStep < STEPS.length) {
      setCurrentStep(currentStep + 1);
    }
  };

  const prevStep = () => {
    if (currentStep > 1) {
      setCurrentStep(currentStep - 1);
    }
  };

  if (loading) {
    return (
      <Layout>
        <Loading message="Chargement..." />
      </Layout>
    );
  }

  if (error) {
    return (
      <Layout>
        <ErrorMessage message={error} />
      </Layout>
    );
  }

  return (
    <Layout>
      <div className={styles.content}>
        <header className={styles.header}>
          <h1 className={styles.title}>
            {id ? 'Modifier un PNJ' : 'Créer un PNJ'}
          </h1>
        </header>

        <div className={styles.steps}>
          {STEPS.map((step) => (
            <div
              key={step.id}
              className={`${styles.step} ${
                step.id === currentStep ? styles.active : ''
              } ${step.id < currentStep ? styles.completed : ''}`}
            >
              <div className={styles.stepNumber}>{step.id}</div>
              <div className={styles.stepTitle}>{step.title}</div>
            </div>
          ))}
        </div>

        <div className={styles.formContainer}>
          {/* Étape 1: Informations de base */}
          {currentStep === 1 && (
            <div className={styles.stepContent}>
              <h2>Informations de base</h2>
              
              <div className={styles.formGroup}>
                <label>Nom *</label>
                <input
                  type="text"
                  value={formData.name}
                  onChange={(e) => handleChange('name', e.target.value)}
                  required
                />
              </div>

              <div className={styles.formGroup}>
                <label>Prénom</label>
                <input
                  type="text"
                  value={formData.firstName}
                  onChange={(e) => handleChange('firstName', e.target.value)}
                />
              </div>

              <div className={styles.formGroup}>
                <label>Nom de famille</label>
                <input
                  type="text"
                  value={formData.lastName}
                  onChange={(e) => handleChange('lastName', e.target.value)}
                />
              </div>

              <div className={styles.formGroup}>
                <label>Avatar</label>
                <div className={styles.avatarSection}>
                  {formData.avatar && (
                    <img src={formData.avatar} alt="Avatar" className={styles.avatarPreview} />
                  )}
                  <button
                    type="button"
                    className={styles.btnSecondary}
                    onClick={() => setAvatarCropperOpen(true)}
                  >
                    {formData.avatar ? 'Changer l\'avatar' : 'Choisir un avatar'}
                  </button>
                </div>
              </div>
            </div>
          )}

          {/* Étape 2: Univers et contexte */}
          {currentStep === 2 && (
            <div className={styles.stepContent}>
              <h2>Univers et contexte</h2>
              
              <div className={styles.formGroup}>
                <label>Univers</label>
                <select
                  value={formData.universe_id || ''}
                  onChange={(e) => handleChange('universe_id', e.target.value ? parseInt(e.target.value) : null)}
                >
                  <option value="">Aucun univers</option>
                  {universes.map((universe) => (
                    <option key={universe.id} value={universe.id}>
                      {universe.name}
                    </option>
                  ))}
                </select>
              </div>

              {formData.universe_id && elseworlds.length > 0 && (
                <div className={styles.formGroup}>
                  <label>Elseworld</label>
                  <select
                    value={formData.elseworld_id || ''}
                    onChange={(e) => handleChange('elseworld_id', e.target.value ? parseInt(e.target.value) : null)}
                  >
                    <option value="">Aucun elseworld</option>
                    {elseworlds.map((elseworld) => (
                      <option key={elseworld.id} value={elseworld.id}>
                        {elseworld.name}
                      </option>
                    ))}
                  </select>
                </div>
              )}

              <div className={styles.formGroup}>
                <label>Affiliation morale</label>
                <select
                  value={formData.moralAffiliation}
                  onChange={(e) => handleChange('moralAffiliation', e.target.value)}
                >
                  <option value="">Choisir...</option>
                  <option value="Super-héros">Super-héros</option>
                  <option value="Super-vilain">Super-vilain</option>
                  <option value="Anti-héros">Anti-héros</option>
                  <option value="Neutre">Neutre</option>
                  <option value="Vigilante">Vigilante</option>
                  <option value="Civil">Civil</option>
                  <option value="Autre">Autre</option>
                </select>
              </div>

              <div className={styles.formGroup}>
                <label>Factions</label>
                <textarea
                  value={formData.factions}
                  onChange={(e) => handleChange('factions', e.target.value)}
                  rows={3}
                  placeholder="Justice League, Titans, Legion of Doom..."
                />
              </div>
            </div>
          )}

          {/* Étape 3: Détails personnels */}
          {currentStep === 3 && (
            <div className={styles.stepContent}>
              <h2>Détails personnels</h2>
              
              <div className={styles.formGroup}>
                <label>Âge</label>
                <input
                  type="text"
                  value={formData.age}
                  onChange={(e) => handleChange('age', e.target.value)}
                  placeholder="32, inconnu, millénaire..."
                />
              </div>

              <div className={styles.formGroup}>
                <label>Genre</label>
                <select
                  value={formData.gender}
                  onChange={(e) => handleChange('gender', e.target.value)}
                >
                  <option value="">Choisir...</option>
                  <option value="Masculin">Masculin</option>
                  <option value="Féminin">Féminin</option>
                  <option value="Autre">Autre</option>
                </select>
              </div>

              <div className={styles.formGroup}>
                <label>Travail/Occupation</label>
                <input
                  type="text"
                  value={formData.occupation}
                  onChange={(e) => handleChange('occupation', e.target.value)}
                  placeholder="Profession, occupation principale..."
                />
              </div>

              <div className={styles.formGroup}>
                <label>Pseudonymes</label>
                <textarea
                  value={formData.pseudonyms}
                  onChange={(e) => handleChange('pseudonyms', e.target.value)}
                  rows={2}
                  placeholder="Alias, noms de code, surnoms..."
                />
              </div>
            </div>
          )}

          {/* Étape 4: Description */}
          {currentStep === 4 && (
            <div className={styles.stepContent}>
              <h2>Description</h2>
              
              <div className={styles.formGroup}>
                <label>Biographie</label>
                <textarea
                  value={formData.biography}
                  onChange={(e) => handleChange('biography', e.target.value)}
                  rows={8}
                  placeholder="Histoire, passé et description du PNJ"
                />
              </div>

              <div className={styles.formGroup}>
                <label>Personnalité</label>
                <textarea
                  value={formData.personality}
                  onChange={(e) => handleChange('personality', e.target.value)}
                  rows={6}
                  placeholder="Traits de caractère, comportement, attitude..."
                />
              </div>

              <div className={styles.formGroup}>
                <label>Apparence</label>
                <textarea
                  value={formData.appearance}
                  onChange={(e) => handleChange('appearance', e.target.value)}
                  rows={6}
                  placeholder="Description physique du PNJ"
                />
              </div>

              <div className={styles.formGroup}>
                <label>Capacités</label>
                <textarea
                  value={formData.abilities}
                  onChange={(e) => handleChange('abilities', e.target.value)}
                  rows={6}
                  placeholder="Pouvoirs, compétences et talents spéciaux"
                />
              </div>

              <div className={styles.formGroup}>
                <label>Équipements</label>
                <textarea
                  value={formData.equipment}
                  onChange={(e) => handleChange('equipment', e.target.value)}
                  rows={6}
                  placeholder="Armes, gadgets, accessoires, véhicules..."
                />
              </div>

              <div className={styles.formGroup}>
                <label>Faiblesses</label>
                <textarea
                  value={formData.weaknesses}
                  onChange={(e) => handleChange('weaknesses', e.target.value)}
                  rows={6}
                  placeholder="Points faibles, vulnérabilités, limitations..."
                />
              </div>
            </div>
          )}

          {/* Étape 5: Rôle et relations */}
          {currentStep === 5 && (
            <div className={styles.stepContent}>
              <h2>Rôle et relations</h2>
              
              <div className={styles.formGroup}>
                <label>Rôle dans l'histoire</label>
                <textarea
                  value={formData.roleInStory}
                  onChange={(e) => handleChange('roleInStory', e.target.value)}
                  rows={6}
                  placeholder="Rôle narratif du PNJ dans l'histoire"
                />
              </div>

              <div className={styles.formGroup}>
                <label>Relations</label>
                <textarea
                  value={formData.relationships}
                  onChange={(e) => handleChange('relationships', e.target.value)}
                  rows={6}
                  placeholder="Relations avec d'autres personnages"
                />
              </div>

              <div className={styles.formGroup}>
                <label>Quêtes</label>
                <textarea
                  value={formData.quests}
                  onChange={(e) => handleChange('quests', e.target.value)}
                  rows={6}
                  placeholder="Quêtes et missions associées au PNJ"
                />
              </div>

              <div className={styles.formGroup}>
                <label>Secrets</label>
                <textarea
                  value={formData.secrets}
                  onChange={(e) => handleChange('secrets', e.target.value)}
                  rows={6}
                  placeholder="Secrets et informations cachées (visible uniquement par vous)"
                />
              </div>
            </div>
          )}

          <div className={styles.formActions}>
            <button
              type="button"
              className={styles.btnSecondary}
              onClick={handleSaveDraft}
              disabled={saving}
            >
              {saving ? 'Sauvegarde...' : 'Sauvegarder en brouillon'}
            </button>
            
            <div className={styles.navigation}>
              {currentStep > 1 && (
                <button
                  type="button"
                  className={styles.btnSecondary}
                  onClick={prevStep}
                >
                  Précédent
                </button>
              )}
              {currentStep < STEPS.length ? (
                <button
                  type="button"
                  className={styles.btnPrimary}
                  onClick={nextStep}
                >
                  Suivant
                </button>
              ) : (
                <button
                  type="button"
                  className={styles.btnPrimary}
                  onClick={handleSubmit}
                  disabled={saving || !formData.name}
                >
                  {saving ? 'Soumission...' : 'Créer le PNJ'}
                </button>
              )}
            </div>
          </div>
        </div>

        <AvatarCropper
          open={avatarCropperOpen}
          onClose={() => setAvatarCropperOpen(false)}
          onSelect={handleAvatarSelect}
          value={formData.avatar ? { url: formData.avatar } : null}
        />
      </div>
    </Layout>
  );
};

export default NpcForm;






