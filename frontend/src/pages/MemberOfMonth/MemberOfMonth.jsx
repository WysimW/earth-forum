import React, { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import Layout from '../../components/Layout/Layout';
import Loading from '../../components/Loading/Loading';
import ErrorMessage from '../../components/ErrorMessage/ErrorMessage';
import { useUniverseTheme } from '../../contexts/UniverseThemeContext';
import { useAuth } from '../../contexts/AuthContext';
import importantService from '../../services/importantService';
import styles from './MemberOfMonth.module.css';

const MemberOfMonth = () => {
  const { universeSlug: routeUniverseSlug } = useParams();
  const { currentUniverse } = useUniverseTheme();
  const { isAuthenticated } = useAuth();
  const universeSlug = routeUniverseSlug || (currentUniverse !== 'portal' ? currentUniverse : '');

  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [entry, setEntry] = useState(null);
  const [history, setHistory] = useState([]);
  const [messages, setMessages] = useState([]);
  const [messageInput, setMessageInput] = useState('');
  const [sendingMessage, setSendingMessage] = useState(false);
  const formatStatsDate = (isoDate) => {
    if (!isoDate) {
      return '';
    }

    const date = new Date(isoDate);
    if (Number.isNaN(date.getTime())) {
      return '';
    }

    return new Intl.DateTimeFormat('fr-FR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
    }).format(date);
  };

  const loadData = async () => {
    if (!universeSlug) {
      setLoading(false);
      return;
    }

    try {
      setLoading(true);
      setError('');
      const [latest, historyResponse] = await Promise.all([
        importantService.getMemberOfMonth(universeSlug),
        importantService.getMemberOfMonthHistory(universeSlug),
      ]);

      const latestEntry = latest?.entry || null;
      setEntry(latestEntry);
      setHistory(Array.isArray(historyResponse?.items) ? historyResponse.items : []);

      if (latestEntry?.id) {
        const messagesResponse = await importantService.getMemberMessages(latestEntry.id);
        setMessages(Array.isArray(messagesResponse?.items) ? messagesResponse.items : []);
      } else {
        setMessages([]);
      }
    } catch (err) {
      setError('Erreur lors du chargement du membre du mois');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadData();
  }, [universeSlug]);

  const submitMessage = async () => {
    if (!entry?.id || !messageInput.trim()) {
      return;
    }

    try {
      setSendingMessage(true);
      await importantService.createMemberMessage(entry.id, messageInput.trim());
      setMessageInput('');
      const messagesResponse = await importantService.getMemberMessages(entry.id);
      setMessages(Array.isArray(messagesResponse?.items) ? messagesResponse.items : []);
    } catch (err) {
      setError('Impossible d’envoyer le message');
    } finally {
      setSendingMessage(false);
    }
  };

  if (loading) {
    return (
      <Layout>
        <Loading message="Chargement du membre du mois..." />
      </Layout>
    );
  }

  if (error) {
    return (
      <Layout>
        <ErrorMessage message={error} onRetry={loadData} />
      </Layout>
    );
  }

  if (!universeSlug) {
    return (
      <Layout>
        <ErrorMessage message="Sélectionnez un univers pour consulter cette page." />
      </Layout>
    );
  }

  return (
    <Layout>
      <div className={styles.page}>
        <h1 className={styles.title}>Membre du mois</h1>
        {entry ? (
          <div className={styles.featured}>
            {entry.user?.avatar && <img src={entry.user.avatar} alt={entry.user.pseudo} className={styles.avatar} />}
            <div>
              <h2 className={styles.name}>{entry.user?.pseudo}</h2>
              <p className={styles.meta}>{entry.month}/{entry.year}</p>
            </div>
          </div>
        ) : (
          <p className={styles.empty}>Aucun membre du mois sélectionné pour cet univers.</p>
        )}

        {entry?.highlightText && (
          <section className={styles.adminNote}>
            <p className={styles.adminLabel}>Message de l&apos;équipe d&apos;administration</p>
            <p className={styles.highlight}>{entry.highlightText}</p>
          </section>
        )}

        {entry?.monthlyStats && (
          <section className={styles.stats}>
            <h3 className={styles.statsTitle}>Participation (30 derniers jours)</h3>
            {entry.monthlyStats.periodFrom && entry.monthlyStats.periodTo && (
              <p className={styles.statsPeriod}>
                Du {formatStatsDate(entry.monthlyStats.periodFrom)} au {formatStatsDate(entry.monthlyStats.periodTo)}
              </p>
            )}
            <div className={styles.statsGrid}>
              <article className={styles.statCard}>
                <span className={styles.statValue}>{entry.monthlyStats.postsCount ?? 0}</span>
                <span className={styles.statLabel}>messages postés</span>
              </article>
              <article className={styles.statCard}>
                <span className={styles.statValue}>{entry.monthlyStats.participatedThreadsCount ?? 0}</span>
                <span className={styles.statLabel}>sujets participés</span>
              </article>
              <article className={styles.statCard}>
                <span className={styles.statValue}>{entry.monthlyStats.createdThreadsCount ?? 0}</span>
                <span className={styles.statLabel}>sujets créés</span>
              </article>
            </div>

            {Array.isArray(entry.monthlyStats.topThreads) && entry.monthlyStats.topThreads.length > 0 && (
              <div className={styles.topThreads}>
                <h4 className={styles.topThreadsTitle}>Sujets où iel a le plus participé ce mois-ci</h4>
                <ul className={styles.topThreadsList}>
                  {entry.monthlyStats.topThreads.map((thread) => (
                    <li key={thread.id} className={styles.topThreadItem}>
                      <Link to={`/threads/${thread.slug || thread.id}`} className={styles.topThreadLink}>
                        {thread.title}
                      </Link>
                      <span className={styles.topThreadCount}>{thread.postsCount} message(s)</span>
                    </li>
                  ))}
                </ul>
              </div>
            )}
          </section>
        )}

        {entry && (
          <section className={styles.messages}>
            <h3>Messages</h3>
            <div className={styles.messageList}>
              {messages.map((message) => (
                <article key={message.id} className={styles.messageItem}>
                  <strong>{message.user?.pseudo || 'Membre'}</strong>
                  <p>{message.content}</p>
                </article>
              ))}
            </div>
            {isAuthenticated && (
              <div className={styles.messageForm}>
                <textarea
                  className={styles.textarea}
                  value={messageInput}
                  onChange={(event) => setMessageInput(event.target.value)}
                  placeholder="Laisser un message..."
                />
                <button type="button" className={styles.button} onClick={submitMessage} disabled={sendingMessage}>
                  {sendingMessage ? 'Envoi...' : 'Publier'}
                </button>
              </div>
            )}
          </section>
        )}

        <section>
          <h3>Historique</h3>
          <ul className={styles.history}>
            {history.map((item) => (
              <li key={item.id}>
                {item.month}/{item.year} - {item.user?.pseudo}
              </li>
            ))}
          </ul>
        </section>
      </div>
    </Layout>
  );
};

export default MemberOfMonth;

