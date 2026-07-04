import React, { useCallback, useEffect, useRef, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import Layout from '../../components/Layout/Layout';
import Breadcrumb from '../../components/Breadcrumb/Breadcrumb';
import messagingService from '../../services/messagingService';
import styles from './Messaging.module.css';

const SEARCH_DEBOUNCE_MS = 320;
const MIN_QUERY_LEN = 2;

function memberInitial(pseudo) {
  const t = (pseudo || '?').trim();
  return t ? t.charAt(0).toUpperCase() : '?';
}

const MessagingNew = () => {
  const navigate = useNavigate();
  const [inputValue, setInputValue] = useState('');
  const [selectedMember, setSelectedMember] = useState(null);
  const [results, setResults] = useState([]);
  const [searchLoading, setSearchLoading] = useState(false);
  const [searchError, setSearchError] = useState('');
  const [lastFetchedQuery, setLastFetchedQuery] = useState('');
  const [highlightIndex, setHighlightIndex] = useState(-1);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState('');
  const listRef = useRef(null);
  const debounceRef = useRef(null);
  const searchRequestIdRef = useRef(0);

  const breadcrumbItems = [
    { name: 'PORTAIL', url: '/', icon: 'home' },
    { name: 'Messagerie', url: '/messagerie', icon: 'forum' },
    { name: 'Nouvelle conversation', url: null, icon: 'character' },
  ];

  const runSearch = useCallback(async (query) => {
    const requestId = ++searchRequestIdRef.current;
    setSearchError('');
    setSearchLoading(true);
    setLastFetchedQuery('');
    try {
      const data = await messagingService.searchMembers(query, { limit: 20 });
      if (requestId !== searchRequestIdRef.current) {
        return;
      }
      const members = Array.isArray(data?.members) ? data.members : [];
      setResults(members);
      setHighlightIndex(members.length > 0 ? 0 : -1);
      setLastFetchedQuery(query);
    } catch (err) {
      if (requestId !== searchRequestIdRef.current) {
        return;
      }
      setResults([]);
      setHighlightIndex(-1);
      setSearchError(err.response?.data?.error || 'Recherche indisponible pour le moment.');
      setLastFetchedQuery(query);
    } finally {
      if (requestId === searchRequestIdRef.current) {
        setSearchLoading(false);
      }
    }
  }, []);

  useEffect(() => {
    if (debounceRef.current) {
      window.clearTimeout(debounceRef.current);
      debounceRef.current = null;
    }

    if (selectedMember) {
      setResults([]);
      setHighlightIndex(-1);
      setSearchLoading(false);
      setLastFetchedQuery('');
      return undefined;
    }

    const q = inputValue.trim();
    if (q.length < MIN_QUERY_LEN) {
      setResults([]);
      setHighlightIndex(-1);
      setSearchError('');
      setSearchLoading(false);
      setLastFetchedQuery('');
      return undefined;
    }

    debounceRef.current = window.setTimeout(() => {
      debounceRef.current = null;
      runSearch(q);
    }, SEARCH_DEBOUNCE_MS);

    return () => {
      if (debounceRef.current) {
        window.clearTimeout(debounceRef.current);
        debounceRef.current = null;
      }
    };
  }, [inputValue, selectedMember, runSearch]);

  const pickMember = (member) => {
    setSelectedMember(member);
    setInputValue(member.pseudo);
    setResults([]);
    setHighlightIndex(-1);
    setSearchError('');
    setLastFetchedQuery('');
    setError('');
  };

  const handleInputChange = (ev) => {
    setInputValue(ev.target.value);
    setSelectedMember(null);
    setLastFetchedQuery('');
    setError('');
  };

  const handleKeyDown = (ev) => {
    if (!results.length) {
      return;
    }
    if (ev.key === 'ArrowDown') {
      ev.preventDefault();
      setHighlightIndex((i) => (i + 1 >= results.length ? 0 : i + 1));
    } else if (ev.key === 'ArrowUp') {
      ev.preventDefault();
      setHighlightIndex((i) => (i <= 0 ? results.length - 1 : i - 1));
    } else if (ev.key === 'Enter' && highlightIndex >= 0 && highlightIndex < results.length) {
      ev.preventDefault();
      pickMember(results[highlightIndex]);
    } else if (ev.key === 'Escape') {
      setResults([]);
      setHighlightIndex(-1);
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    const trimmed = inputValue.trim();
    if (!trimmed && !selectedMember) {
      setError('Indiquez un destinataire ou choisissez un membre dans la liste.');
      return;
    }

    setSubmitting(true);
    setError('');
    try {
      const payload = selectedMember ? { targetUserId: selectedMember.id } : { pseudo: trimmed };
      const data = await messagingService.createConversation(payload);
      const conversationId = data?.conversation?.id;
      if (conversationId) {
        navigate(`/messagerie/${conversationId}`, { replace: true });
      } else {
        setError('Réponse inattendue du serveur.');
      }
    } catch (err) {
      setError(err.response?.data?.error || 'Impossible de créer la conversation.');
    } finally {
      setSubmitting(false);
    }
  };

  const q = inputValue.trim();
  const stableEmpty =
    !searchLoading &&
    !searchError &&
    results.length === 0 &&
    lastFetchedQuery === q &&
    q.length >= MIN_QUERY_LEN;
  const showList =
    !selectedMember &&
    q.length >= MIN_QUERY_LEN &&
    (searchLoading || searchError || results.length > 0 || stableEmpty);

  return (
    <Layout>
      <div className={styles.content}>
        <Breadcrumb items={breadcrumbItems} />

        <header className={styles.header}>
          <div className={styles.headerContent}>
            <h1 className={styles.title}>Nouvelle conversation</h1>
            <p className={styles.description}>
              Recherchez un membre par pseudo (au moins {MIN_QUERY_LEN} caractères) ou saisissez le pseudo exact. Une
              conversation existante avec cette personne sera rouverte automatiquement.
            </p>
          </div>
        </header>

        <section className={styles.panel}>
          <form className={`${styles.form} ${styles.formPanel}`} onSubmit={handleSubmit}>
            <div className={styles.memberSearchWrap}>
              <label className={styles.label} htmlFor="messaging-new-recipient">
                Destinataire
                <input
                  id="messaging-new-recipient"
                  className={styles.input}
                  value={inputValue}
                  onChange={handleInputChange}
                  onKeyDown={handleKeyDown}
                  autoComplete="off"
                  disabled={submitting}
                  placeholder="Ex. Hal…"
                  role="combobox"
                  aria-expanded={showList}
                  aria-controls="messaging-member-search-list"
                  aria-autocomplete="list"
                />
              </label>
              <p className={styles.memberSearchHint}>
                Suggestions parmi les comptes actifs du forum. Flèches haut / bas puis Entrée pour choisir.
              </p>
              {showList && (
                <ul
                  ref={listRef}
                  id="messaging-member-search-list"
                  className={styles.memberSearchList}
                  role="listbox"
                  aria-label="Membres correspondants"
                >
                  {searchLoading && (
                    <li className={styles.memberSearchEmpty} role="presentation">
                      Recherche…
                    </li>
                  )}
                  {!searchLoading && searchError && (
                    <li className={styles.memberSearchEmpty} role="presentation">
                      {searchError}
                    </li>
                  )}
                  {!searchLoading && !searchError && results.length === 0 && stableEmpty && (
                    <li className={styles.memberSearchEmpty} role="presentation">
                      Aucun membre ne correspond à cette recherche.
                    </li>
                  )}
                  {!searchLoading &&
                    !searchError &&
                    results.length > 0 &&
                    results.map((m, index) => (
                      <li key={m.id} className={styles.memberSearchItem} role="none">
                        <button
                          type="button"
                          className={`${styles.memberSearchBtn} ${index === highlightIndex ? styles.memberSearchBtnHighlighted : ''}`}
                          role="option"
                          aria-selected={index === highlightIndex}
                          onMouseDown={(ev) => ev.preventDefault()}
                          onClick={() => pickMember(m)}
                          onMouseEnter={() => setHighlightIndex(index)}
                        >
                          {m.avatar ? (
                            <img src={m.avatar} alt="" className={styles.memberSearchAvatar} />
                          ) : (
                            <span className={styles.memberSearchAvatarFallback} aria-hidden>
                              {memberInitial(m.pseudo)}
                            </span>
                          )}
                          <span className={styles.memberSearchMeta}>
                            <span className={styles.memberSearchPseudo}>{m.pseudo}</span>
                            <span className={styles.memberSearchStatus}>Membre du forum</span>
                          </span>
                        </button>
                      </li>
                    ))}
                </ul>
              )}
            </div>
            {error && <p className={styles.error}>{error}</p>}
            <div className={styles.toolbar}>
              <button type="submit" className={styles.sendBtn} disabled={submitting}>
                {submitting ? 'Ouverture…' : 'Ouvrir la conversation'}
              </button>
              <Link to="/messagerie" className={styles.btnGhost}>
                Annuler
              </Link>
            </div>
          </form>
        </section>
      </div>
    </Layout>
  );
};

export default MessagingNew;
