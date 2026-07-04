import React, { useState } from 'react';
import Layout from '../../../components/Layout/Layout';
import Breadcrumb from '../../../components/Breadcrumb/Breadcrumb';
import styles from './RpActivityCard.module.css';

const styleOptions = [
  { id: 'classic', label: 'Style 1 - Editorial' },
  { id: 'compact', label: 'Style 2 - Compact' },
  { id: 'hero', label: 'Style 3 - Hero visuel' },
  { id: 'minimal', label: 'Style 4 - Minimaliste' },
  { id: 'alert', label: 'Style 5 - Alerte staff' },
  { id: 'dossier', label: 'Style 6 - Dossier RP' },
  { id: 'timeline', label: 'Style 7 - Timeline' },
  { id: 'dense', label: 'Style 8 - Forum dense' },
  { id: 'neon', label: 'Style 9 - Neon mission' },
  { id: 'terminal', label: 'Style 10 - Console staff' },
  { id: 'glass', label: 'Style 11 - Glass panel' },
  { id: 'split', label: 'Style 12 - Split editorial' },
  { id: 'ticket', label: 'Style 13 - Ticket event' },
  { id: 'brutal', label: 'Style 14 - Brutalist' },
];

const mockActivity = {
  id: 42,
  kind: 'event',
  status: 'open',
  title: 'Le siege de Trigon',
  registrationsCount: 12,
  faction: { name: 'Ordre de l\'Aube' },
  reminderAt: '2026-05-20T19:30:00',
  openingSpeech:
    'Bonjours a tous. Conformement au resultat du vote organise pour l\'anniversaire du forum, le siege de Trigon ouvre officiellement ses portes. Les inscriptions restent accessibles toute la semaine.',
  illustrationUrl:
    'https://images.unsplash.com/photo-1518709268805-4e9042af9f23?auto=format&fit=crop&w=1280&q=80',
};

const mockRegistrants = [
  { id: 1, name: 'Lysandra', avatar: 'https://i.pravatar.cc/80?img=11' },
  { id: 2, name: 'Kael', avatar: 'https://i.pravatar.cc/80?img=12' },
  { id: 3, name: 'Mira', avatar: 'https://i.pravatar.cc/80?img=13' },
  { id: 4, name: 'Dorian', avatar: 'https://i.pravatar.cc/80?img=14' },
  { id: 5, name: 'Ariane', avatar: 'https://i.pravatar.cc/80?img=15' },
  { id: 6, name: 'Varek', avatar: 'https://i.pravatar.cc/80?img=16' },
  { id: 7, name: 'Nyx', avatar: 'https://i.pravatar.cc/80?img=17' },
  { id: 8, name: 'Soren', avatar: 'https://i.pravatar.cc/80?img=18' },
  { id: 9, name: 'Elena', avatar: 'https://i.pravatar.cc/80?img=19' },
  { id: 10, name: 'Thorne', avatar: 'https://i.pravatar.cc/80?img=20' },
  { id: 11, name: 'Kira', avatar: 'https://i.pravatar.cc/80?img=21' },
  { id: 12, name: 'Orion', avatar: 'https://i.pravatar.cc/80?img=22' },
  { id: 13, name: 'Rhea', avatar: 'https://i.pravatar.cc/80?img=23' },
  { id: 14, name: 'Cassian', avatar: 'https://i.pravatar.cc/80?img=24' },
  { id: 15, name: 'Iris', avatar: 'https://i.pravatar.cc/80?img=25' },
  { id: 16, name: 'Nolan', avatar: 'https://i.pravatar.cc/80?img=26' },
];

const formatReminderDate = (dateValue) => {
  if (!dateValue) return 'Aucune relance planifiee';

  return new Date(dateValue).toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};

const RpActivityCardComponent = () => {
  const [selectedStyle, setSelectedStyle] = useState('classic');
  const [registrationsCount, setRegistrationsCount] = useState(mockActivity.registrationsCount);
  const mainAvatarLimit = 6;
  const smallAvatarLimit = 2;
  const mainVisibleRegistrants = mockRegistrants.slice(0, Math.min(registrationsCount, mainAvatarLimit));
  const smallVisibleRegistrants = mockRegistrants.slice(0, Math.min(registrationsCount, smallAvatarLimit));
  const hiddenRegistrants = Math.max(0, registrationsCount - mainVisibleRegistrants.length);

  const breadcrumbItems = [
    { name: 'Accueil', url: '/' },
    { name: 'Composants', url: '/components' },
    { name: 'Card Event', url: null },
  ];

  return (
    <Layout>
      <div className={styles.container}>
        <Breadcrumb items={breadcrumbItems} />

        <div className={styles.controls}>
          <h1 className={styles.pageTitle}>Styles de Card Event</h1>
          <p className={styles.pageDescription}>
            Variantes de design pour la card `RpActivityCard` utilisee sur la page des activites RP.
          </p>
          <div className={styles.registrationControls}>
            <p className={styles.registrationLabel}>Simulation des inscrits: {registrationsCount}</p>
            <div className={styles.registrationActions}>
              <button
                type="button"
                className={styles.registrationButton}
                onClick={() => setRegistrationsCount((prev) => Math.max(0, prev - 1))}
              >
                -1
              </button>
              <input
                className={styles.registrationRange}
                type="range"
                min="0"
                max="30"
                step="1"
                value={registrationsCount}
                onChange={(event) => setRegistrationsCount(Number(event.target.value))}
              />
              <button
                type="button"
                className={styles.registrationButton}
                onClick={() => setRegistrationsCount((prev) => Math.min(30, prev + 1))}
              >
                +1
              </button>
            </div>
          </div>
          <div className={styles.styleSelector}>
            {styleOptions.map((styleOption) => (
              <button
                key={styleOption.id}
                className={`${styles.styleBtn} ${selectedStyle === styleOption.id ? styles.active : ''}`}
                onClick={() => setSelectedStyle(styleOption.id)}
              >
                {styleOption.label}
              </button>
            ))}
          </div>
        </div>

        <section className={styles.preview}>
          <h2 className={styles.previewTitle}>Apercu principal</h2>
          <article className={`${styles.card} ${styles[`card_${selectedStyle}`]}`}>
            <div className={styles.illustration}>
              <img src={mockActivity.illustrationUrl} alt={mockActivity.title} />
            </div>

            <div className={styles.cardBody}>
              <header className={styles.header}>
                <div className={styles.badges}>
                  <span className={`${styles.badge} ${styles.kind}`}>{mockActivity.kind}</span>
                  <span className={`${styles.badge} ${styles.status}`}>{mockActivity.status}</span>
                </div>
                <div className={styles.countWrap}>
                  <span className={styles.count}>{registrationsCount} inscrit(s)</span>
                  <div className={styles.countAvatars}>
                    {mainVisibleRegistrants.map((registrant) => (
                      <img
                        key={registrant.id}
                        src={registrant.avatar}
                        alt={registrant.name}
                        title={registrant.name}
                        className={styles.countAvatar}
                      />
                    ))}
                    {hiddenRegistrants > 0 && (
                      <span className={styles.countMore}>+{hiddenRegistrants}</span>
                    )}
                  </div>
                </div>
              </header>

              <h3 className={styles.title}>{mockActivity.title}</h3>

              <div className={styles.metaRow}>
                <p className={styles.meta}>
                  Faction: <strong>{mockActivity.faction.name}</strong>
                </p>
                <p className={styles.meta}>
                  Relance: <strong>{formatReminderDate(mockActivity.reminderAt)}</strong>
                </p>
              </div>

              <p className={styles.speech}>{mockActivity.openingSpeech}</p>

              <div className={styles.actions}>
                <button type="button" className={styles.actionPrimary}>Voir le detail</button>
                <button type="button" className={styles.actionSecondary}>Sujet principal</button>
              </div>
            </div>
          </article>
        </section>

        <section className={styles.preview}>
          <h2 className={styles.previewTitle}>Apercu en grille</h2>
          <div className={styles.grid}>
            {[1, 2, 3].map((item) => (
              <article key={item} className={`${styles.card} ${styles[`card_${selectedStyle}`]}`}>
                <div className={styles.illustration}>
                  <img src={mockActivity.illustrationUrl} alt={mockActivity.title} />
                </div>
                <div className={styles.cardBody}>
                  <header className={styles.header}>
                    <div className={styles.badges}>
                      <span className={`${styles.badge} ${styles.kind}`}>{mockActivity.kind}</span>
                      <span className={`${styles.badge} ${styles.status}`}>{mockActivity.status}</span>
                    </div>
                    <div className={styles.countWrap}>
                      <span className={styles.count}>{registrationsCount} inscrit(s)</span>
                      <div className={styles.countAvatars}>
                        {smallVisibleRegistrants.map((registrant) => (
                          <img
                            key={registrant.id}
                            src={registrant.avatar}
                            alt={registrant.name}
                            title={registrant.name}
                            className={styles.countAvatar}
                          />
                        ))}
                      </div>
                    </div>
                  </header>
                  <h3 className={styles.title}>{mockActivity.title}</h3>
                  <p className={styles.speech}>{mockActivity.openingSpeech}</p>
                </div>
              </article>
            ))}
          </div>
        </section>
      </div>
    </Layout>
  );
};

export default RpActivityCardComponent;
