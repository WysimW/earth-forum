import React, { useEffect, useMemo, useState } from 'react';
import Layout from '../../components/Layout/Layout';
import Loading from '../../components/Loading/Loading';
import ErrorMessage from '../../components/ErrorMessage/ErrorMessage';
import { sanitizeHtml } from '../../utils/sanitize';
import importantService from '../../services/importantService';
import SeoHead from '../../components/Seo/SeoHead';
import styles from '../Regulation/Regulation.module.css';

const structureHtmlByH1 = (html) => {
  if (typeof document === 'undefined') {
    return { headings: [], sections: [] };
  }

  const container = document.createElement('div');
  container.innerHTML = html;

  const headings = [];
  const sections = [];
  let headingIndex = 0;
  let sectionIndex = 0;
  let currentSection = { id: 'intro', html: [] };

  const nodeToHtml = (node) => {
    const wrapper = document.createElement('div');
    wrapper.appendChild(node.cloneNode(true));
    return wrapper.innerHTML;
  };

  Array.from(container.querySelectorAll('h1, h2, h3, h4')).forEach((heading) => {
    headingIndex += 1;
    heading.id = `guide-section-${headingIndex}`;
    headings.push({
      id: heading.id,
      text: heading.textContent || `Section ${headingIndex}`,
      level: Number(heading.tagName.toLowerCase().replace('h', '')),
    });
  });

  const flattenNodesAroundH1 = (nodes) => Array.from(nodes).flatMap((node) => {
    const isElement = node.nodeType === Node.ELEMENT_NODE;
    const tagName = isElement ? node.tagName.toLowerCase() : '';

    if (isElement && tagName !== 'h1' && node.querySelector('h1')) {
      return flattenNodesAroundH1(node.childNodes);
    }

    return [node];
  });

  flattenNodesAroundH1(container.childNodes).forEach((node) => {
    const isElement = node.nodeType === Node.ELEMENT_NODE;
    const tagName = isElement ? node.tagName.toLowerCase() : '';

    if (isElement && tagName === 'h1') {
      if (currentSection.html.length > 0) {
        sections.push({ ...currentSection, html: currentSection.html.join('') });
      }

      sectionIndex += 1;
      currentSection = { id: `section-${sectionIndex}`, html: [nodeToHtml(node)] };
      return;
    }

    currentSection.html.push(nodeToHtml(node));
  });

  if (currentSection.html.length > 0) {
    sections.push({ ...currentSection, html: currentSection.html.join('') });
  }

  return { headings, sections };
};

const normalizeText = (text = '') => text
  .normalize('NFD')
  .replace(/[\u0300-\u036f]/g, '')
  .toLowerCase()
  .trim();

const parseFaqSection = (html) => {
  if (typeof document === 'undefined') {
    return null;
  }

  const container = document.createElement('div');
  container.innerHTML = html;

  const title = container.querySelector('h1');
  const normalizedTitle = normalizeText(title?.textContent || '');
  if (!['faq', 'questions frequentes'].some((keyword) => normalizedTitle.includes(keyword))) {
    return null;
  }

  const items = [];
  let currentItem = null;

  const nodeToHtml = (node) => {
    const wrapper = document.createElement('div');
    wrapper.appendChild(node.cloneNode(true));
    return wrapper.innerHTML;
  };

  Array.from(container.childNodes).forEach((node) => {
    const isElement = node.nodeType === Node.ELEMENT_NODE;
    const tagName = isElement ? node.tagName.toLowerCase() : '';

    if (tagName === 'h1') {
      return;
    }

    if (tagName === 'h2' || tagName === 'h3') {
      if (currentItem) {
        items.push({ ...currentItem, answer: currentItem.answer.join('') });
      }
      currentItem = {
        id: node.id || `faq-${items.length + 1}`,
        question: node.textContent || `Question ${items.length + 1}`,
        answer: [],
      };
      return;
    }

    if (currentItem) {
      currentItem.answer.push(nodeToHtml(node));
    }
  });

  if (currentItem) {
    items.push({ ...currentItem, answer: currentItem.answer.join('') });
  }

  return {
    title: title?.textContent || 'FAQ',
    titleId: title?.id,
    items,
  };
};

const Guide = () => {
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [summaryOpen, setSummaryOpen] = useState(false);
  const [content, setContent] = useState('');
  const [seo, setSeo] = useState(null);

  const safeHtml = useMemo(() => sanitizeHtml(content || ''), [content]);
  const structuredContent = useMemo(() => structureHtmlByH1(safeHtml), [safeHtml]);

  const load = async () => {
    try {
      setLoading(true);
      setError('');
      const response = await importantService.getGuide();
      setContent(response?.content || '');
      setSeo(response?.seo || null);
    } catch (err) {
      setError('Impossible de charger le mode d’emploi');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    load();
  }, []);

  if (loading) {
    return (
      <Layout>
        <Loading message="Chargement du mode d’emploi..." />
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
      <SeoHead seo={seo} />
      <div className={styles.page}>
        <h1 className={styles.title}>Mode d’emploi</h1>
        <div className={styles.regulationLayout}>
          {structuredContent.headings.length > 0 && (
            <nav className={styles.summary} aria-label="Sommaire du mode d’emploi">
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
            {structuredContent.sections.map((section) => {
              const faq = parseFaqSection(section.html);

              if (faq?.items.length) {
                return (
                  <section key={section.id} className={styles.card}>
                    <div className={styles.content}>
                      <h1 id={faq.titleId}>{faq.title}</h1>
                      <div className={styles.faqList}>
                        {faq.items.map((item) => (
                          <details key={item.id} className={styles.faqItem}>
                            <summary className={styles.faqQuestion}>{item.question}</summary>
                            <div className={styles.faqAnswer} dangerouslySetInnerHTML={{ __html: item.answer }} />
                          </details>
                        ))}
                      </div>
                    </div>
                  </section>
                );
              }

              return (
                <section key={section.id} className={styles.card}>
                  <div className={styles.content} dangerouslySetInnerHTML={{ __html: section.html }} />
                </section>
              );
            })}
          </div>
        </div>
      </div>
    </Layout>
  );
};

export default Guide;

