import React, { useEffect, useMemo, useState } from 'react';
import Layout from '../../components/Layout/Layout';
import Loading from '../../components/Loading/Loading';
import ErrorMessage from '../../components/ErrorMessage/ErrorMessage';
import { sanitizeHtml } from '../../utils/sanitize';
import importantService from '../../services/importantService';
import styles from './Regulation.module.css';

const Regulation = () => {
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');
  const [data, setData] = useState({ content: '', accepted: false, version: '1' });
  const [summaryOpen, setSummaryOpen] = useState(false);

  const safeHtml = useMemo(() => sanitizeHtml(data.content || ''), [data.content]);
  const structuredContent = useMemo(() => {
    if (typeof document === 'undefined') {
      return { headings: [], sections: [] };
    }

    const container = document.createElement('div');
    container.innerHTML = safeHtml;

    const headings = [];
    const sections = [];
    let headingIndex = 0;
    let sectionIndex = 0;
    let currentSection = {
      id: 'intro',
      html: [],
    };

    const nodeToHtml = (node) => {
      const wrapper = document.createElement('div');
      wrapper.appendChild(node.cloneNode(true));
      return wrapper.innerHTML;
    };

    Array.from(container.childNodes).forEach((node) => {
      const isElement = node.nodeType === Node.ELEMENT_NODE;
      const tagName = isElement ? node.tagName.toLowerCase() : '';
      const isHeading = /^h[1-4]$/.test(tagName);

      if (isElement && tagName === 'h1') {
        if (currentSection.html.length > 0) {
          sections.push({
            ...currentSection,
            html: currentSection.html.join(''),
          });
        }

        sectionIndex += 1;
        headingIndex += 1;
        node.id = `reglement-section-${headingIndex}`;
        headings.push({
          id: node.id,
          text: node.textContent || `Section ${headingIndex}`,
          level: 1,
        });
        currentSection = {
          id: `section-${sectionIndex}`,
          html: [nodeToHtml(node)],
        };
        return;
      }

      if (isHeading) {
        headingIndex += 1;
        node.id = `reglement-section-${headingIndex}`;
        headings.push({
          id: node.id,
          text: node.textContent || `Section ${headingIndex}`,
          level: Number(tagName.replace('h', '')),
        });
      }

      currentSection.html.push(nodeToHtml(node));
    });

    if (currentSection.html.length > 0) {
      sections.push({
        ...currentSection,
        html: currentSection.html.join(''),
      });
    }

    return { headings, sections };
  }, [safeHtml]);

  const load = async () => {
    try {
      setLoading(true);
      setError('');
      const response = await importantService.getRegulation();
      setData({
        content: response?.content || '',
        accepted: Boolean(response?.accepted),
        version: response?.version || '1',
      });
    } catch (err) {
      setError('Impossible de charger le règlement');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    load();
  }, []);

  const acceptRegulation = async () => {
    try {
      setSaving(true);
      await importantService.acceptRegulation();
      await load();
    } catch (err) {
      setError('Vous devez être connecté pour valider le règlement');
    } finally {
      setSaving(false);
    }
  };

  if (loading) {
    return (
      <Layout>
        <Loading message="Chargement du règlement..." />
      </Layout>
    );
  }

  if (error) {
    return (
      <Layout>
        <ErrorMessage message={error} onRetry={load} />
      </Layout>
    );
  }

  return (
    <Layout>
      <div className={styles.page}>
        <h1 className={styles.title}>Règlement</h1>
        <div className={styles.regulationLayout}>
          {structuredContent.headings.length > 0 && (
            <nav className={styles.summary} aria-label="Sommaire du règlement">
              <button
                type="button"
                className={styles.summaryToggle}
                onClick={() => setSummaryOpen((prev) => !prev)}
                aria-expanded={summaryOpen}
              >
                <span>Sommaire</span>
                <span className={`${styles.summaryChevron} ${summaryOpen ? styles.summaryChevronOpen : ''}`}>
                  ▾
                </span>
              </button>
              {summaryOpen && (
                <ol className={styles.summaryList}>
                  {structuredContent.headings.map((heading) => (
                    <li
                      key={heading.id}
                      className={`${styles.summaryItem} ${styles[`summaryLevel${heading.level}`] || ''}`}
                    >
                      <a href={`#${heading.id}`}>{heading.text}</a>
                    </li>
                  ))}
                </ol>
              )}
            </nav>
          )}
          <div className={styles.sections}>
            {structuredContent.sections.map((section) => (
              <section key={section.id} className={styles.card}>
                <div className={styles.content} dangerouslySetInnerHTML={{ __html: section.html }} />
              </section>
            ))}
          </div>
          <div className={styles.actions}>
            <button
              type="button"
              className={styles.acceptButton}
              disabled={saving || data.accepted}
              onClick={acceptRegulation}
            >
              {data.accepted ? 'Règlement déjà validé' : (saving ? 'Validation...' : 'Valider le règlement')}
            </button>
          </div>
        </div>
      </div>
    </Layout>
  );
};

export default Regulation;

