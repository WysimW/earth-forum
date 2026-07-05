import React, { useState, useEffect } from 'react';
import { useLocation, useNavigate, useParams } from 'react-router-dom';
import Layout from '../../components/Layout/Layout';
import Loading from '../../components/Loading/Loading';
import ErrorMessage from '../../components/ErrorMessage/ErrorMessage';
import AvatarCropper from '../../components/AvatarCropper/AvatarCropper';
import RichTextComposer from '../../components/RichTextComposer/RichTextComposerTiptap';
import { characterApi } from '../../services/characterApi';
import api from '../../services/api';
import universeService from '../../services/universeService';
import styles from './CharacterForm.module.css';

const STEPS = [
  { id: 1, title: 'Informations de base', key: 'basic' },
  { id: 2, title: 'Univers et contexte', key: 'universe' },
  { id: 3, title: 'Détails personnels', key: 'personal' },
  { id: 4, title: 'Description', key: 'description' },
  { id: 5, title: 'Capacités et équipements', key: 'abilities' },
];

const CharacterForm = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const location = useLocation();
  const [currentStep, setCurrentStep] = useState(1);
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState(null);
  const [universes, setUniverses] = useState([]);
  const [elseworlds, setElseworlds] = useState([]);
  const [avatarCropperOpen, setAvatarCropperOpen] = useState(false);
  const [characterStatus, setCharacterStatus] = useState('draft');

  const [formData, setFormData] = useState({
    name: '',
    firstName: '',
    lastName: '',
    pseudonyms: '',
    actualPseudo: '',
    avatar: '',
    universe_id: null,
    elseworld_id: null,
    age: '',
    gender: '',
    sexualOrientation: '',
    moralAffiliation: '',
    factions: '',
    civilStatus: '',
    occupation: '',
    biography: '',
    personality: '',
    appearance: '',
    abilities: '',
    equipment: '',
    weaknesses: '',
    alias: '',
  });

  useEffect(() => {
    fetchUniverses();
    if (id) {
      fetchCharacter();
    }
  }, [id]);

  useEffect(() => {
    if (id) {
      return;
    }

    const params = new URLSearchParams(location.search);
    const rawPrefill = params.get('prefill');
    if (!rawPrefill) {
      return;
    }

    try {
      const parsed = JSON.parse(rawPrefill);
      setFormData((prev) => ({
        ...prev,
        name: parsed.name || prev.name,
        firstName: parsed.firstName || prev.firstName,
        lastName: parsed.lastName || prev.lastName,
        actualPseudo: parsed.nickname || prev.actualPseudo,
        moralAffiliation: parsed.alignment || prev.moralAffiliation,
        abilities: parsed.powers || prev.abilities,
        weaknesses: parsed.weaknesses || prev.weaknesses,
        biography: parsed.whoIsText || prev.biography,
        personality: parsed.whyPlayText || prev.personality,
        avatar: parsed.imageUrl || prev.avatar,
      }));
    } catch (error) {
      console.error('Impossible de charger les données de préremplissage.', error);
    }
  }, [id, location.search]);

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

  const fetchCharacter = async () => {
    try {
      setLoading(true);
      const response = await characterApi.getCharacter(id);
      // L'API retourne directement l'objet character (pas dans une propriété character)
      const character = response;
      
      setCharacterStatus(character.status || 'draft');
      setFormData({
        name: character.name || '',
        firstName: character.firstName || '',
        lastName: character.lastName || '',
        pseudonyms: character.pseudonyms || '',
        actualPseudo: character.actualPseudo || '',
        avatar: character.avatar || '',
        universe_id: character.universe?.id || null,
        elseworld_id: character.elseworld?.id || null,
        age: character.age || '',
        gender: character.gender || '',
        sexualOrientation: character.sexualOrientation || '',
        moralAffiliation: character.moralAffiliation || '',
        factions: character.factions || '',
        civilStatus: character.civilStatus || '',
        occupation: character.occupation || '',
        biography: character.biography || '',
        personality: character.personality || '',
        appearance: character.appearance || '',
        abilities: character.abilities || '',
        equipment: character.equipment || '',
        weaknesses: character.weaknesses || '',
        alias: character.alias || '',
      });
    } catch (err) {
      setError('Erreur lors du chargement du personnage');
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  const handleChange = (field, value) => {
    setFormData(prev => ({ ...prev, [field]: value }));
  };

  const buildUpdatePayload = (statusOverride = null) => {
    const payload = { ...formData };
    if (statusOverride !== null) {
      payload.status = statusOverride;
    }
    return payload;
  };

  const shouldSubmitForValidation = () => {
    return ['draft', 'editing', 'rejected'].includes(characterStatus);
  };

  const handleSaveDraft = async () => {
    try {
      setSaving(true);

      if (id) {
        const payload = buildUpdatePayload(characterStatus === 'draft' ? 'draft' : null);
        await characterApi.updateCharacter(id, payload);
      } else {
        await characterApi.createCharacter(buildUpdatePayload('draft'), true);
      }
      
      alert('Brouillon sauvegardé avec succès');
    } catch (err) {
      alert('Erreur lors de la sauvegarde: ' + (err.response?.data?.error || err.message));
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

      if (id) {
        await characterApi.updateCharacter(id, buildUpdatePayload());
        if (shouldSubmitForValidation()) {
          await characterApi.submitCharacter(id);
        }
      } else {
        await characterApi.createCharacter(buildUpdatePayload('pending'), false);
      }
      
      navigate('/characters');
    } catch (err) {
      alert('Erreur lors de la soumission: ' + (err.response?.data?.error || err.message));
      console.error(err);
    } finally {
      setSaving(false);
    }
  };

  const getSubmitLabel = () => {
    if (saving) {
      return shouldSubmitForValidation() ? 'Soumission...' : 'Enregistrement...';
    }
    return shouldSubmitForValidation() ? 'Soumettre pour validation' : 'Enregistrer';
  };

  const getDraftSaveLabel = () => {
    if (saving) {
      return 'Sauvegarde...';
    }
    return characterStatus === 'draft' || !id ? 'Sauvegarder en brouillon' : 'Enregistrer';
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

  const goToStep = (stepId) => {
    setCurrentStep(stepId);
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
            {id ? 'Modifier un personnage' : 'Créer un personnage'}
          </h1>
        </header>

        <nav className={styles.steps} aria-label="Étapes du formulaire">
          {STEPS.map((step) => (
            <button
              key={step.id}
              type="button"
              className={`${styles.step} ${
                step.id === currentStep ? styles.active : ''
              } ${step.id < currentStep ? styles.completed : ''}`}
              onClick={() => goToStep(step.id)}
              aria-current={step.id === currentStep ? 'step' : undefined}
            >
              <span className={styles.stepNumber}>{step.id}</span>
              <span className={styles.stepTitle}>{step.title}</span>
            </button>
          ))}
        </nav>

        <div className={styles.formContainer}>
          {/* Étape 1: Informations de base */}
          {currentStep === 1 && (
            <div className={styles.stepContent}>
              <h2>Informations de base</h2>
              
              <div className={styles.formGroup}>
                <label>Nom affiché *</label>
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
                <label>Alias (pseudonyme actuel)</label>
                <input
                  type="text"
                  value={formData.actualPseudo}
                  onChange={(e) => handleChange('actualPseudo', e.target.value)}
                  placeholder="Le nom sous lequel votre personnage est connu actuellement"
                />
              </div>

              <div className={styles.formGroup}>
                <label>Autres alias</label>
                <textarea
                  value={formData.pseudonyms}
                  onChange={(e) => handleChange('pseudonyms', e.target.value)}
                  rows={2}
                  placeholder="Alias, noms de code, surnoms..."
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
                <label>Orientation sexuelle</label>
                <select
                  value={formData.sexualOrientation}
                  onChange={(e) => handleChange('sexualOrientation', e.target.value)}
                >
                  <option value="">Choisir...</option>
                  <option value="Hétérosexuel(le)">Hétérosexuel(le)</option>
                  <option value="Homosexuel(le)">Homosexuel(le)</option>
                  <option value="Bisexuel(le)">Bisexuel(le)</option>
                  <option value="Pansexuel(le)">Pansexuel(le)</option>
                  <option value="Asexuel(le)">Asexuel(le)</option>
                  <option value="Autre">Autre</option>
                  <option value="Non spécifié">Non spécifié</option>
                </select>
              </div>

              <div className={styles.formGroup}>
                <label>Statut civil</label>
                <select
                  value={formData.civilStatus}
                  onChange={(e) => handleChange('civilStatus', e.target.value)}
                >
                  <option value="">Choisir...</option>
                  <option value="Célibataire">Célibataire</option>
                  <option value="En couple">En couple</option>
                  <option value="Marié(e)">Marié(e)</option>
                  <option value="Divorcé(e)">Divorcé(e)</option>
                  <option value="Veuf/Veuve">Veuf/Veuve</option>
                  <option value="Compliqué">Compliqué</option>
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
            </div>
          )}

          {/* Étape 4: Description */}
          {currentStep === 4 && (
            <div className={styles.stepContent}>
              <h2>Description</h2>
              
              <div className={styles.formGroup}>
                <label>Biographie</label>
                <div className={styles.richTextField}>
                  <RichTextComposer
                    value={formData.biography}
                    onChange={(value) => handleChange('biography', value)}
                  />
                </div>
              </div>

              <div className={styles.formGroup}>
                <label>Personnalité</label>
                <div className={styles.richTextField}>
                  <RichTextComposer
                    value={formData.personality}
                    onChange={(value) => handleChange('personality', value)}
                  />
                </div>
              </div>

              <div className={styles.formGroup}>
                <label>Apparence</label>
                <div className={styles.richTextField}>
                  <RichTextComposer
                    value={formData.appearance}
                    onChange={(value) => handleChange('appearance', value)}
                  />
                </div>
              </div>
            </div>
          )}

          {/* Étape 5: Capacités et équipements */}
          {currentStep === 5 && (
            <div className={styles.stepContent}>
              <h2>Capacités et équipements</h2>
              
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

          <div className={styles.formActions}>
            <button
              type="button"
              className={styles.btnSecondary}
              onClick={handleSaveDraft}
              disabled={saving}
            >
              {getDraftSaveLabel()}
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
                  {getSubmitLabel()}
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

export default CharacterForm;

