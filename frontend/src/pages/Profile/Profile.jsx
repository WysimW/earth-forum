import React, { useState } from 'react';
import Layout from '../../components/Layout/Layout';
import Breadcrumb from '../../components/Breadcrumb/Breadcrumb';
import AvatarEditor from '../../components/AvatarEditor/AvatarEditor';
import { useAuth } from '../../contexts/AuthContext';
import userService from '../../services/userService';
import styles from './Profile.module.css';

const Profile = () => {
  const { user, refreshUser } = useAuth();
  const [pseudo, setPseudo] = useState(user?.pseudo || '');
  const [avatarEditorOpen, setAvatarEditorOpen] = useState(false);
  const [savingProfile, setSavingProfile] = useState(false);
  const [savingAvatar, setSavingAvatar] = useState(false);
  const [statusMessage, setStatusMessage] = useState('');
  const [errorMessage, setErrorMessage] = useState('');

  const breadcrumbItems = [
    { name: 'Accueil', url: '/', icon: 'home' },
    { name: 'Mon profil', url: null, icon: 'character' },
  ];

  const handleSubmit = async (event) => {
    event.preventDefault();
    setSavingProfile(true);
    setErrorMessage('');
    setStatusMessage('');

    try {
      await userService.updateProfile({ pseudo: pseudo.trim() });
      const refreshedUser = await refreshUser();
      setPseudo(refreshedUser.pseudo || '');
      setStatusMessage('Vos informations ont été mises à jour.');
    } catch (error) {
      setErrorMessage(error.response?.data?.message || 'Erreur lors de la mise à jour du profil.');
    } finally {
      setSavingProfile(false);
    }
  };

  const handleAvatarSave = async (avatarUrl) => {
    setSavingAvatar(true);
    setErrorMessage('');
    setStatusMessage('');

    try {
      await userService.updateProfile({ avatar: avatarUrl });
      await refreshUser();
      setAvatarEditorOpen(false);
      setStatusMessage('Votre avatar a été mis à jour.');
    } catch (error) {
      setErrorMessage(error.response?.data?.message || "Erreur lors de la mise à jour de l'avatar.");
    } finally {
      setSavingAvatar(false);
    }
  };

  return (
    <Layout>
      <div className={styles.content}>
        <Breadcrumb items={breadcrumbItems} />

        <header className={styles.header}>
          <h1 className={styles.title}>Mon profil</h1>
          <p className={styles.subtitle}>Mettez à jour vos informations publiques.</p>
        </header>

        <section className={styles.card}>
          <div className={styles.avatarSection}>
            {user?.avatar ? (
              <img src={user.avatar} alt={user.pseudo || 'Avatar utilisateur'} className={styles.avatar} />
            ) : (
              <div className={styles.avatarPlaceholder} aria-hidden="true">
                <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                  <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                  <circle cx="12" cy="7" r="4" />
                </svg>
              </div>
            )}
            <button
              type="button"
              className={styles.secondaryButton}
              onClick={() => setAvatarEditorOpen(true)}
            >
              {user?.avatar ? "Modifier l'avatar" : 'Ajouter un avatar'}
            </button>
          </div>

          <form className={styles.form} onSubmit={handleSubmit}>
            <label className={styles.label} htmlFor="pseudo">Pseudo</label>
            <input
              id="pseudo"
              type="text"
              className={styles.input}
              value={pseudo}
              onChange={(event) => setPseudo(event.target.value)}
              required
            />

            <label className={styles.label} htmlFor="email">Email</label>
            <input
              id="email"
              type="email"
              className={styles.input}
              value={user?.email || ''}
              disabled
            />

            {errorMessage && <p className={styles.error}>{errorMessage}</p>}
            {statusMessage && <p className={styles.success}>{statusMessage}</p>}

            <button type="submit" className={styles.primaryButton} disabled={savingProfile}>
              {savingProfile ? 'Enregistrement...' : 'Enregistrer'}
            </button>
          </form>
        </section>
      </div>

      <AvatarEditor
        open={avatarEditorOpen}
        onClose={() => setAvatarEditorOpen(false)}
        currentAvatar={user?.avatar || null}
        characterName={user?.pseudo || ''}
        onSave={handleAvatarSave}
        saving={savingAvatar}
      />
    </Layout>
  );
};

export default Profile;
