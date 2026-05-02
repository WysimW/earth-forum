import React from 'react';
import Layout from '../../components/Layout/Layout';
import ForumCard from '../../components/ForumCard/ForumCard';
import ForumCardV2 from '../../components/ForumCardV2/ForumCardV2';
import ForumCardV3 from '../../components/ForumCardV3/ForumCardV3';
import styles from './TestComponent.module.css';

const TestComponent = () => {
  // Fausses données pour tester le ForumCard
  const mockForums = [
    {
      id: 1,
      name: 'Annonces et informations essentielles de DC Comics',
      description: 'Forum dédié aux annonces importantes et aux informations essentielles de l\'univers DC Comics. Restez informé des dernières mises à jour.',
      type: 'important',
      banner: 'https://images.unsplash.com/photo-1612036782180-6f0b6cd846fe?w=800&h=400&fit=crop',
      stats: {
        totalThreads: 1,
        totalPosts: 3,
        subforumCount: 4,
      },
      subforums: [
        { id: 11, name: 'Règlement' },
        { id: 12, name: 'Annonces' },
        { id: 13, name: 'Guide du débutant' },
        { id: 14, name: 'Support technique' },
      ],
      lastPost: {
        threadId: 101,
        threadTitle: 'Guide de création de personnage',
        author: 'HarleyQ',
        character: null,
        date: '2025-04-17 10:22:00',
      },
    },
    {
      id: 2,
      name: 'Factions',
      description: 'Forum dédié aux factions de l\'univers DC Comics. Explorez les différentes organisations et leurs interactions.',
      type: 'important',
      banner: null,
      stats: {
        totalThreads: 1,
        totalPosts: 1,
        subforumCount: 0,
      },
      subforums: [],
      lastPost: {
        threadId: 102,
        threadTitle: 'Faction: Justice League',
        author: 'ClarkK',
        character: null,
        date: '2025-04-19 02:11:00',
      },
    },
    {
      id: 3,
      name: 'Présentation',
      description: 'Forum de présentation des personnages. Créez et partagez vos fiches de personnages.',
      type: 'important',
      banner: 'https://images.unsplash.com/photo-1578662996442-48f60103fc96?w=800&h=400&fit=crop',
      stats: {
        totalThreads: 1,
        totalPosts: 3,
        subforumCount: 3,
      },
      subforums: [
        { id: 31, name: 'Fiches en attente' },
        { id: 32, name: 'Fiches validées' },
        { id: 33, name: 'Fiches refusées' },
      ],
      lastPost: {
        threadId: 103,
        threadTitle: 'Fiche de Mia Mizoguchi',
        author: 'Wysim',
        character: null,
        date: '2025-04-21 23:20:00',
      },
    },
    {
      id: 4,
      name: 'Metropolis',
      description: 'La ville de Superman, lumineuse et futuriste. Explorez les rues de Metropolis et ses nombreux quartiers.',
      type: 'roleplay',
      banner: 'https://images.unsplash.com/photo-1449824913935-59a10b8d2000?w=800&h=400&fit=crop',
      stats: {
        totalThreads: 1,
        totalPosts: 3,
        subforumCount: 3,
      },
      subforums: [
        { id: 41, name: 'Daily Planet' },
        { id: 42, name: 'LexCorp' },
        { id: 43, name: 'Rues de Metropolis' },
      ],
      lastPost: {
        threadId: 104,
        threadTitle: 'Menace à Metropolis',
        author: 'Lex Luthor',
        character: 'Lex Luthor',
        date: '2025-03-14 18:22:00',
      },
    },
    {
      id: 5,
      name: 'Gotham City',
      description: 'La ville sombre de Batman, rongée par le crime. Plongez dans l\'atmosphère gothique de Gotham.',
      type: 'roleplay',
      banner: 'https://images.unsplash.com/photo-1514565131-fce0801e5785?w=800&h=400&fit=crop',
      stats: {
        totalThreads: 4,
        totalPosts: 10,
        subforumCount: 4,
      },
      subforums: [
        { id: 51, name: 'GCPD' },
        { id: 52, name: 'Arkham Asylum' },
        { id: 53, name: 'Wayne Enterprises' },
        { id: 54, name: 'Batcave' },
      ],
      lastPost: {
        threadId: 105,
        threadTitle: 'Première mission des Teen Titans',
        author: 'Nightwing',
        character: 'Nightwing',
        date: '2025-04-18 22:51:00',
      },
    },
    {
      id: 6,
      name: 'Themyscira',
      description: 'L\'île des Amazones, patrie de Wonder Woman. Découvrez cette terre mystique et ses traditions.',
      type: 'roleplay',
      banner: 'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=800&h=400&fit=crop',
      stats: {
        totalThreads: 0,
        totalPosts: 0,
        subforumCount: 0,
      },
      subforums: [],
      lastPost: null,
    },
    {
      id: 7,
      name: 'Atlantis',
      description: 'Le royaume sous-marin d\'Aquaman. Explorez les profondeurs océaniques et la civilisation atlante.',
      type: 'roleplay',
      banner: 'https://images.unsplash.com/photo-1559827260-dc66d52bef19?w=800&h=400&fit=crop',
      stats: {
        totalThreads: 0,
        totalPosts: 0,
        subforumCount: 0,
      },
      subforums: [],
      lastPost: null,
    },
    {
      id: 8,
      name: 'Justice League',
      description: 'Organisation des plus grands héros de la Terre. Rejoignez les rangs de la Justice League.',
      type: 'roleplay',
      banner: 'https://images.unsplash.com/photo-1551269901-5c5e14c25df7?w=800&h=400&fit=crop',
      stats: {
        totalThreads: 1,
        totalPosts: 5,
        subforumCount: 2,
      },
      subforums: [
        { id: 81, name: 'Tour de garde' },
        { id: 82, name: 'Hall of Justice' },
      ],
      lastPost: {
        threadId: 108,
        threadTitle: 'Réunion à la Tour de Garde',
        author: 'Green Lantern',
        character: 'Green Lantern',
        date: '2025-03-23 11:22:00',
      },
    },
    {
      id: 9,
      name: 'Super-Vilains',
      description: 'Les organisations criminelles et leurs repaires. Explorez le côté obscur de l\'univers DC.',
      type: 'roleplay',
      banner: 'https://images.unsplash.com/photo-1518709268805-4e9042af2176?w=800&h=400&fit=crop',
      stats: {
        totalThreads: 0,
        totalPosts: 0,
        subforumCount: 2,
      },
      subforums: [
        { id: 91, name: 'Legion of Doom' },
        { id: 92, name: 'Injustice League' },
      ],
      lastPost: null,
    },
    {
      id: 10,
      name: 'Flood',
      description: 'Discussions libres entre membres. Un espace pour échanger librement sur tous les sujets.',
      type: 'hrp',
      banner: null,
      stats: {
        totalThreads: 0,
        totalPosts: 0,
        subforumCount: 1,
      },
      subforums: [
        { id: 101, name: 'Jeux forumiques' },
      ],
      lastPost: null,
    },
    {
      id: 11,
      name: 'Actualités DC Comics',
      description: 'Discussions sur les comics, films et séries DC. Restez à jour avec les dernières actualités.',
      type: 'hrp',
      banner: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=800&h=400&fit=crop',
      stats: {
        totalThreads: 1,
        totalPosts: 3,
        subforumCount: 0,
      },
      subforums: [],
      lastPost: {
        threadId: 111,
        threadTitle: 'Discussion: Le meilleur film DC de tous les temps?',
        author: 'DianaP',
        character: null,
        date: '2025-04-15 14:22:00',
      },
    },
  ];

  return (
    <Layout>
      <div className={styles.content}>
        <header className={styles.header}>
          <h1 className={styles.title}>Page de Test des Composants</h1>
          <p className={styles.subtitle}>
            Cette page permet de tester et visualiser les différents composants de l'application
          </p>
        </header>

        <section className={styles.section}>
          <h2 className={styles.sectionTitle}>ForumCard (Version actuelle)</h2>
          <p className={styles.sectionDescription}>
            Composant d'affichage d'un forum avec ses informations, statistiques et sous-forums.
          </p>
          
          <div className={styles.forumsGrid}>
            {mockForums.map((forum) => (
              <ForumCard key={forum.id} forum={forum} />
            ))}
          </div>
        </section>

        <section className={styles.section}>
          <h2 className={styles.sectionTitle}>ForumCardV2 (Nouvelle version)</h2>
          <p className={styles.sectionDescription}>
            Nouvelle version du composant ForumCard avec un design repensé et une meilleure organisation visuelle.
          </p>
          
          <div className={styles.forumsGrid}>
            {mockForums.map((forum) => (
              <ForumCardV2 key={`v2-${forum.id}`} forum={forum} />
            ))}
          </div>
        </section>

        <section className={styles.section}>
          <h2 className={styles.sectionTitle}>ForumCardV3 (Style horizontal)</h2>
          <p className={styles.sectionDescription}>
            Version avec un design complètement différent : layout horizontal, bordure latérale colorée, et organisation en trois sections.
          </p>
          
          <div className={styles.forumsList}>
            {mockForums.map((forum) => (
              <ForumCardV3 key={`v3-${forum.id}`} forum={forum} />
            ))}
          </div>
        </section>
      </div>
    </Layout>
  );
};

export default TestComponent;

