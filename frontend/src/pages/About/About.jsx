import React, { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import Layout from '../../components/Layout/Layout';
import SeoHead from '../../components/Seo/SeoHead';
import Loading from '../../components/Loading/Loading';
import ErrorMessage from '../../components/ErrorMessage/ErrorMessage';
import { sanitizeHtml } from '../../utils/sanitize';
import seoService from '../../services/seoService';
import styles from './About.module.css';

const About = () => {
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [content, setContent] = useState('');
  const [seo, setSeo] = useState(null);

  useEffect(() => {
    const load = async () => {
      try {
        setLoading(true);
        setError('');
        const data = await seoService.getAbout();
        setContent(data?.content || '');
        setSeo(data?.seo || null);
      } catch (err) {
        setError('Impossible de charger la page.');
      } finally {
        setLoading(false);
      }
    };

    load();
  }, []);

  const safeHtml = useMemo(() => sanitizeHtml(content), [content]);

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
      <SeoHead seo={seo} />
      <div className={styles.content}>
        <header className={styles.header}>
          <h1 className={styles.title}>Qui sommes-nous</h1>
          <p className={styles.subtitle}>
            Découvrez l'histoire et la communauté d'Earth Forum
          </p>
        </header>

        {safeHtml ? (
          <div
            className={styles.textContent}
            dangerouslySetInnerHTML={{ __html: safeHtml }}
          />
        ) : (
          <>
            <section className={styles.section}>
              <h2 className={styles.sectionTitle}>Notre histoire</h2>
              <div className={styles.textContent}>
                <p>
                  Earth Forum est né de la passion pour le roleplay et les univers de comics.
                </p>
              </div>
            </section>

            <section className={styles.section}>
              <h2 className={styles.sectionTitle}>Rejoignez-nous</h2>
              <div className={styles.textContent}>
                <div className={styles.actions}>
                  <Link to="/register" className={styles.button}>
                    Créer un compte
                  </Link>
                  <Link to="/" className={styles.buttonSecondary}>
                    Explorer les univers
                  </Link>
                </div>
              </div>
            </section>
          </>
        )}
      </div>
    </Layout>
  );
};

export default About;
