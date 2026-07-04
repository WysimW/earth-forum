import React from 'react';
import { Link } from 'react-router-dom';
import Layout from '../../components/Layout/Layout';
import Breadcrumb from '../../components/Breadcrumb/Breadcrumb';
import styles from './Components.module.css';

const Components = () => {
  const components = [
    {
      category: 'Navigation',
      items: [
        {
          name: 'Sidebar univers',
          path: '/components/sidebar',
          description: 'Rail latéral Important avec états étendu et compact',
        },
      ],
    },
    {
      category: 'Cards',
      items: [
        {
          name: 'Cards de Personnages',
          path: '/components/cards',
          description: 'Différents styles de cartes pour afficher les personnages',
        },
        {
          name: 'Card Event RP',
          path: '/components/rpactivitycard',
          description: 'Variantes de design pour la card des activites RP',
        },
      ],
    },
    {
      category: 'Headers',
      items: [
        {
          name: 'Headers de Threads',
          path: '/components/threadheader',
          description: 'Styles d\'en-têtes pour les threads normaux',
        },
        {
          name: 'Headers de Fiches de Personnages',
          path: '/components/characterheader',
          description: 'Styles d\'en-têtes pour les fiches de personnages',
        },
        {
          name: 'Headers de Forums',
          path: '/components/forumdetailheader',
          description: 'Styles d\'en-têtes pour les pages de forums',
        },
      ],
    },
    {
      category: 'Lists',
      items: [
        {
          name: 'Listes de Threads',
          path: '/components/threadlist',
          description: 'Styles de listes et items de threads',
        },
        {
          name: 'Listes de Forums',
          path: '/components/forumdetail',
          description: 'Styles de cartes de forums pour les pages de détail',
        },
      ],
    },
    {
      category: 'Posts',
      items: [
        {
          name: 'Posts HRP',
          path: '/components/threadposthrp',
          description: 'Styles de posts pour les threads hors-roleplay',
        },
        {
          name: 'Posts RP',
          path: '/components/threadpostrp',
          description: 'Styles de posts pour les threads de roleplay',
        },
      ],
    },
    {
      category: 'Filters',
      items: [
        {
          name: 'Filtres de Threads',
          path: '/components/threadfilter',
          description: 'Styles de composants de filtrage pour les threads',
        },
      ],
    },
  ];

  const breadcrumbItems = [
    { name: 'Accueil', url: '/' },
    { name: 'Composants', url: null },
  ];

  return (
    <Layout>
      <div className={styles.container}>
        <Breadcrumb items={breadcrumbItems} />
        
        <div className={styles.header}>
          <h1 className={styles.title}>Composants UI</h1>
          <p className={styles.description}>
            Explorez tous les composants et styles disponibles pour l'interface du forum.
            Cliquez sur un composant pour voir les différentes variantes de style.
          </p>
        </div>

        <div className={styles.componentsGrid}>
          {components.map((category, categoryIndex) => (
            <div key={categoryIndex} className={styles.category}>
              <h2 className={styles.categoryTitle}>{category.category}</h2>
              <div className={styles.itemsGrid}>
                {category.items.map((item, itemIndex) => (
                  <Link
                    key={itemIndex}
                    to={item.path}
                    className={styles.componentCard}
                  >
                    <div className={styles.cardHeader}>
                      <h3 className={styles.cardTitle}>{item.name}</h3>
                      <svg
                        width="20"
                        height="20"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        strokeWidth="2"
                        className={styles.arrowIcon}
                      >
                        <polyline points="9 18 15 12 9 6" />
                      </svg>
                    </div>
                    <p className={styles.cardDescription}>{item.description}</p>
                    <div className={styles.cardPath}>
                      <svg
                        width="14"
                        height="14"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        strokeWidth="2"
                      >
                        <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71" />
                        <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71" />
                      </svg>
                      <span>{item.path}</span>
                    </div>
                  </Link>
                ))}
              </div>
            </div>
          ))}
        </div>
      </div>
    </Layout>
  );
};

export default Components;


