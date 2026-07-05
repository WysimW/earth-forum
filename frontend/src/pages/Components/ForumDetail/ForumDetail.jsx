import React, { useState } from 'react';
import { Link } from 'react-router-dom';
import Layout from '../../../components/Layout/Layout';
import Breadcrumb from '../../../components/Breadcrumb/Breadcrumb';
import styles from './ForumDetail.module.css';

const ForumDetail = () => {
  const [selectedStyle, setSelectedStyle] = useState('style1');

  // Données de test pour des forums
  const mockForums = [
    {
      id: 19,
      slug: 'gcpd',
      name: 'GCPD',
      description: 'Le département de police de Gotham City',
      banner: 'https://oaa-test-bucket-s3.s3.eu-north-1.amazonaws.com/media/2025/11/media_69287d5f85ced5.23167966.png?X-Amz-Content-Sha256=UNSIGNED-PAYLOAD&X-Amz-Algorithm=AWS4-HMAC-SHA256&X-Amz-Credential=AKIA2HOURBANDTBSPJOS%2F20251127%2Feu-north-1%2Fs3%2Faws4_request&X-Amz-Date=20251127T163336Z&X-Amz-SignedHeaders=host&X-Amz-Expires=604800&X-Amz-Signature=d09bcd165e656ccc25f180bd161ee7df1cec2bfa0fd51d57c12f43d04ffbdd9c',
      type: 'hrp',
      isRoleplay: false,
      stats: {
        totalThreads: 12,
        totalPosts: 145,
        subforumCount: 2
      },
      lastPost: {
        threadId: 6,
        threadSlug: 'scene-rp-une-crucial-menace-au-crepuscule',
        threadTitle: 'Scène RP: Une Crucial Menace au crépuscule',
        author: 'Lois Lane',
        character: 'Lois Lane',
        avatar: 'https://oaa-test-bucket-s3.s3.eu-north-1.amazonaws.com/media/2025/11/media_6925dfecad5156.46810465.png',
        date: '2025-11-02 11:42:16'
      },
      subforums: [
        { id: 1, slug: 'gcpd-hrp', name: 'GCPD Hors-RP' },
        { id: 2, slug: 'gcpd-rp', name: 'GCPD Roleplay' }
      ]
    },
    {
      id: 20,
      slug: 'arkham-asylum',
      name: 'Arkham Asylum',
      description: 'L\'asile psychiatrique où sont enfermés les criminels les plus dangereux',
      banner: 'https://cdn.midjourney.com/1685b7a5-56a3-4b2e-b79a-821b8bc548bc/0_2.png',
      type: 'hrp',
      isRoleplay: false,
      stats: {
        totalThreads: 8,
        totalPosts: 67,
        subforumCount: 0
      },
      lastPost: {
        threadId: 14,
        threadSlug: 'belle-reeve',
        threadTitle: 'Infiltration à Belle Reve',
        author: 'The Joker',
        character: 'The Joker',
        avatar: 'https://oaa-test-bucket-s3.s3.eu-north-1.amazonaws.com/media/2025/11/media_6925dfb5490710.88539145.png',
        date: '2025-11-12 11:42:16'
      },
      subforums: []
    },
    {
      id: 21,
      slug: 'wayne-enterprises',
      name: 'Wayne Enterprises',
      description: 'Le siège de l\'entreprise de Bruce Wayne',
      banner: null,
      type: 'important',
      isRoleplay: false,
      stats: {
        totalThreads: 0,
        totalPosts: 0,
        subforumCount: 0
      },
      lastPost: null,
      subforums: []
    },
    {
      id: 22,
      slug: 'batcave',
      name: 'Batcave',
      description: 'Le repaire secret de Batman sous le manoir Wayne',
      banner: 'https://via.placeholder.com/800x200?text=Batcave',
      type: 'roleplay',
      isRoleplay: true,
      stats: {
        totalThreads: 5,
        totalPosts: 23,
        subforumCount: 1
      },
      lastPost: {
        threadId: 13,
        threadSlug: 'mission-titans',
        threadTitle: 'Première mission des Teen Titans',
        author: 'Batman',
        character: 'Batman',
        avatar: 'https://i.pravatar.cc/150?img=3',
        date: '2025-11-16 11:42:16'
      },
      subforums: [
        { id: 3, slug: 'batcave-equipment', name: 'Équipement' }
      ]
    }
  ];

  const breadcrumbItems = [
    { name: 'Accueil', url: '/' },
    { name: 'Forums', url: '/forums' },
    { name: 'Gotham City', url: null }
  ];

  const stylesList = [
    { id: 'style1', name: 'Compact - Avatar dernier post' },
    { id: 'style2', name: 'Bannière mise en avant' },
    { id: 'style3', name: 'Minimaliste avec icône' },
    { id: 'style4', name: 'Statistiques proéminentes' },
    { id: 'style5', name: 'Dernier post détaillé' },
    { id: 'style6', name: 'Sous-forums visibles' },
    { id: 'style7', name: 'Bannière en fond total' },
    { id: 'style8', name: 'Bannière fond total + Dernier message droite + Sous-forums' },
    { id: 'style9', name: 'Bannière full + Sidebar droite' },
  ];

  const formatDate = (dateString) => {
    if (!dateString) return '';
    try {
      const date = new Date(dateString);
      return date.toLocaleDateString('fr-FR', { 
        day: '2-digit', 
        month: '2-digit', 
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      });
    } catch {
      return dateString;
    }
  };

  const renderStyle1 = () => (
    <div className={styles.style1}>
      <Breadcrumb items={breadcrumbItems} />
      <div className={styles.forumHeader}>
        <h1 className={styles.forumTitle}>Gotham City</h1>
        <p className={styles.forumDescription}>Sous-forums de Gotham City</p>
      </div>
      <div className={styles.forumsList}>
        {mockForums.map((forum) => (
          <Link 
            key={forum.id} 
            to={`/forums/${forum.slug}`}
            className={styles.forumCard}
          >
            <div className={styles.forumMain}>
              <div className={styles.forumIcon}>
                {forum.type === 'roleplay' ? (
                  <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                    <circle cx="9" cy="7" r="4" />
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                    <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                  </svg>
                ) : forum.type === 'important' ? (
                  <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                  </svg>
                ) : (
                  <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                  </svg>
                )}
              </div>
              <div className={styles.forumContent}>
                <div className={styles.forumHeaderRow}>
                  <h3 className={styles.forumName}>{forum.name}</h3>
                  <span className={styles.forumType}>{forum.type === 'roleplay' ? 'RP' : forum.type === 'important' ? 'Important' : 'Hors-RP'}</span>
                </div>
                {forum.description && (
                  <p className={styles.forumDescription}>{forum.description}</p>
                )}
                <div className={styles.forumStats}>
                  <span>{forum.stats.totalThreads} discussions</span>
                  <span>•</span>
                  <span>{forum.stats.totalPosts} messages</span>
                </div>
              </div>
            </div>
            {forum.lastPost && (
              <div className={styles.lastPostCompact}>
                {forum.lastPost.avatar && (
                  <img src={forum.lastPost.avatar} alt={forum.lastPost.character || forum.lastPost.author} className={styles.lastPostAvatarSmall} />
                )}
                <div className={styles.lastPostInfoCompact}>
                  <div className={styles.lastPostAuthorCompact}>{forum.lastPost.character || forum.lastPost.author}</div>
                  <div className={styles.lastPostDateCompact}>{formatDate(forum.lastPost.date)}</div>
                </div>
              </div>
            )}
          </Link>
        ))}
      </div>
    </div>
  );

  const renderStyle2 = () => (
    <div className={styles.style2}>
      <Breadcrumb items={breadcrumbItems} />
      <div className={styles.forumHeader}>
        <h1 className={styles.forumTitle}>Gotham City</h1>
        <p className={styles.forumDescription}>Sous-forums de Gotham City</p>
      </div>
      <div className={styles.forumsList}>
        {mockForums.map((forum) => (
          <Link 
            key={forum.id} 
            to={`/forums/${forum.slug}`}
            className={styles.forumCard}
          >
            {forum.banner && (
              <div className={styles.forumBanner}>
                <img src={forum.banner} alt={forum.name} />
              </div>
            )}
            <div className={styles.forumCardBody}>
              <div className={styles.forumHeaderRow}>
                <h3 className={styles.forumName}>{forum.name}</h3>
                <span className={styles.forumType}>{forum.type === 'roleplay' ? 'RP' : forum.type === 'important' ? 'Important' : 'Hors-RP'}</span>
              </div>
              {forum.description && (
                <p className={styles.forumDescription}>{forum.description}</p>
              )}
              <div className={styles.forumStats}>
                <span>{forum.stats.totalThreads} discussions</span>
                <span>•</span>
                <span>{forum.stats.totalPosts} messages</span>
              </div>
              {forum.lastPost && (
                <div className={styles.lastPostSection}>
                  <div className={styles.lastPostLabel}>Dernier message</div>
                  <div className={styles.lastPostContent}>
                    {forum.lastPost.avatar && (
                      <img src={forum.lastPost.avatar} alt={forum.lastPost.character || forum.lastPost.author} className={styles.lastPostAvatar} />
                    )}
                    <div className={styles.lastPostDetails}>
                      <div className={styles.lastPostAuthor}>{forum.lastPost.character || forum.lastPost.author}</div>
                      <div className={styles.lastPostDate}>{formatDate(forum.lastPost.date)}</div>
                    </div>
                  </div>
                </div>
              )}
            </div>
          </Link>
        ))}
      </div>
    </div>
  );

  const renderStyle3 = () => (
    <div className={styles.style3}>
      <Breadcrumb items={breadcrumbItems} />
      <div className={styles.forumHeader}>
        <h1 className={styles.forumTitle}>Gotham City</h1>
        <p className={styles.forumDescription}>Sous-forums de Gotham City</p>
      </div>
      <div className={styles.forumsList}>
        {mockForums.map((forum) => (
          <Link 
            key={forum.id} 
            to={`/forums/${forum.slug}`}
            className={styles.forumCard}
          >
            <div className={styles.forumIconLarge}>
              {forum.type === 'roleplay' ? (
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                  <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                  <circle cx="9" cy="7" r="4" />
                  <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                  <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                </svg>
              ) : forum.type === 'important' ? (
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                  <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                </svg>
              ) : (
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                  <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                </svg>
              )}
            </div>
            <div className={styles.forumContent}>
              <h3 className={styles.forumName}>{forum.name}</h3>
              {forum.description && (
                <p className={styles.forumDescription}>{forum.description}</p>
              )}
              <div className={styles.forumStats}>
                <span>{forum.stats.totalThreads} discussions</span>
                <span>•</span>
                <span>{forum.stats.totalPosts} messages</span>
              </div>
            </div>
            {forum.lastPost && (
              <div className={styles.lastPostArrow}>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                  <polyline points="9 18 15 12 9 6" />
                </svg>
              </div>
            )}
          </Link>
        ))}
      </div>
    </div>
  );

  const renderStyle4 = () => (
    <div className={styles.style4}>
      <Breadcrumb items={breadcrumbItems} />
      <div className={styles.forumHeader}>
        <h1 className={styles.forumTitle}>Gotham City</h1>
        <p className={styles.forumDescription}>Sous-forums de Gotham City</p>
      </div>
      <div className={styles.forumsList}>
        {mockForums.map((forum) => (
          <Link 
            key={forum.id} 
            to={`/forums/${forum.slug}`}
            className={styles.forumCard}
          >
            <div className={styles.forumContent}>
              <div className={styles.forumHeaderRow}>
                <h3 className={styles.forumName}>{forum.name}</h3>
                <span className={styles.forumType}>{forum.type === 'roleplay' ? 'RP' : forum.type === 'important' ? 'Important' : 'Hors-RP'}</span>
              </div>
              {forum.description && (
                <p className={styles.forumDescription}>{forum.description}</p>
              )}
            </div>
            <div className={styles.statsGrid}>
              <div className={styles.statItem}>
                <div className={styles.statValue}>{forum.stats.totalThreads}</div>
                <div className={styles.statLabel}>Discussions</div>
              </div>
              <div className={styles.statItem}>
                <div className={styles.statValue}>{forum.stats.totalPosts}</div>
                <div className={styles.statLabel}>Messages</div>
              </div>
              {forum.stats.subforumCount > 0 && (
                <div className={styles.statItem}>
                  <div className={styles.statValue}>{forum.stats.subforumCount}</div>
                  <div className={styles.statLabel}>Sous-forums</div>
                </div>
              )}
            </div>
            {forum.lastPost && (
              <div className={styles.lastPostSection}>
                {forum.lastPost.avatar && (
                  <img src={forum.lastPost.avatar} alt={forum.lastPost.character || forum.lastPost.author} className={styles.lastPostAvatar} />
                )}
                <div className={styles.lastPostInfo}>
                  <div className={styles.lastPostAuthor}>{forum.lastPost.character || forum.lastPost.author}</div>
                  <div className={styles.lastPostDate}>{formatDate(forum.lastPost.date)}</div>
                </div>
              </div>
            )}
          </Link>
        ))}
      </div>
    </div>
  );

  const renderStyle5 = () => (
    <div className={styles.style5}>
      <Breadcrumb items={breadcrumbItems} />
      <div className={styles.forumHeader}>
        <h1 className={styles.forumTitle}>Gotham City</h1>
        <p className={styles.forumDescription}>Sous-forums de Gotham City</p>
      </div>
      <div className={styles.forumsList}>
        {mockForums.map((forum) => (
          <Link 
            key={forum.id} 
            to={`/forums/${forum.slug}`}
            className={styles.forumCard}
          >
            <div className={styles.forumContent}>
              <div className={styles.forumHeaderRow}>
                <h3 className={styles.forumName}>{forum.name}</h3>
                <span className={styles.forumType}>{forum.type === 'roleplay' ? 'RP' : forum.type === 'important' ? 'Important' : 'Hors-RP'}</span>
              </div>
              {forum.description && (
                <p className={styles.forumDescription}>{forum.description}</p>
              )}
              <div className={styles.forumStats}>
                <span>{forum.stats.totalThreads} discussions</span>
                <span>•</span>
                <span>{forum.stats.totalPosts} messages</span>
              </div>
            </div>
            {forum.lastPost && (
              <div className={styles.lastPostDetailed}>
                <div className={styles.lastPostHeader}>
                  <div className={styles.lastPostLabel}>Dernier message</div>
                  {forum.lastPost.avatar && (
                    <img src={forum.lastPost.avatar} alt={forum.lastPost.character || forum.lastPost.author} className={styles.lastPostAvatar} />
                  )}
                </div>
                <div className={styles.lastPostTitle}>{forum.lastPost.threadTitle}</div>
                <div className={styles.lastPostMeta}>
                  <span className={styles.lastPostAuthor}>{forum.lastPost.character || forum.lastPost.author}</span>
                  <span className={styles.lastPostDate}>{formatDate(forum.lastPost.date)}</span>
                </div>
              </div>
            )}
          </Link>
        ))}
      </div>
    </div>
  );

  const renderStyle6 = () => (
    <div className={styles.style6}>
      <Breadcrumb items={breadcrumbItems} />
      <div className={styles.forumHeader}>
        <h1 className={styles.forumTitle}>Gotham City</h1>
        <p className={styles.forumDescription}>Sous-forums de Gotham City</p>
      </div>
      <div className={styles.forumsList}>
        {mockForums.map((forum) => (
          <Link 
            key={forum.id} 
            to={`/forums/${forum.slug}`}
            className={styles.forumCard}
          >
            <div className={styles.forumContent}>
              <div className={styles.forumHeaderRow}>
                <h3 className={styles.forumName}>{forum.name}</h3>
                <span className={styles.forumType}>{forum.type === 'roleplay' ? 'RP' : forum.type === 'important' ? 'Important' : 'Hors-RP'}</span>
              </div>
              {forum.description && (
                <p className={styles.forumDescription}>{forum.description}</p>
              )}
              {forum.subforums.length > 0 && (
                <div className={styles.subforumsList}>
                  <div className={styles.subforumsLabel}>Sous-forums :</div>
                  <div className={styles.subforumsTags}>
                    {forum.subforums.map((subforum) => (
                      <span key={subforum.id} className={styles.subforumTag}>{subforum.name}</span>
                    ))}
                  </div>
                </div>
              )}
              <div className={styles.forumStats}>
                <span>{forum.stats.totalThreads} discussions</span>
                <span>•</span>
                <span>{forum.stats.totalPosts} messages</span>
              </div>
            </div>
            {forum.lastPost && (
              <div className={styles.lastPostSection}>
                {forum.lastPost.avatar && (
                  <img src={forum.lastPost.avatar} alt={forum.lastPost.character || forum.lastPost.author} className={styles.lastPostAvatar} />
                )}
                <div className={styles.lastPostInfo}>
                  <div className={styles.lastPostAuthor}>{forum.lastPost.character || forum.lastPost.author}</div>
                  <div className={styles.lastPostDate}>{formatDate(forum.lastPost.date)}</div>
                </div>
              </div>
            )}
          </Link>
        ))}
      </div>
    </div>
  );

  const renderStyle7 = () => (
    <div className={styles.style7}>
      <Breadcrumb items={breadcrumbItems} />
      <div className={styles.forumHeader}>
        <h1 className={styles.forumTitle}>Gotham City</h1>
        <p className={styles.forumDescription}>Sous-forums de Gotham City</p>
      </div>
      <div className={styles.forumsList}>
        {mockForums.map((forum) => (
          <Link 
            key={forum.id} 
            to={`/forums/${forum.slug}`}
            className={styles.forumCard}
            style={forum.banner ? {
              backgroundImage: `linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.7)), url("${forum.banner}")`,
              backgroundSize: 'cover',
              backgroundPosition: 'center',
            } : {}}
          >
            <div className={styles.forumOverlay}>
              <div className={styles.forumContent}>
                <div className={styles.forumHeaderRow}>
                  <h3 className={styles.forumName}>{forum.name}</h3>
                  <span className={styles.forumType}>{forum.type === 'roleplay' ? 'RP' : forum.type === 'important' ? 'Important' : 'Hors-RP'}</span>
                </div>
                {forum.description && (
                  <p className={styles.forumDescription}>{forum.description}</p>
                )}
                <div className={styles.forumStats}>
                  <span>{forum.stats.totalThreads} discussions</span>
                  <span>•</span>
                  <span>{forum.stats.totalPosts} messages</span>
                  {forum.stats.subforumCount > 0 && (
                    <>
                      <span>•</span>
                      <span>{forum.stats.subforumCount} sous-forum{forum.stats.subforumCount > 1 ? 's' : ''}</span>
                    </>
                  )}
                </div>
              </div>
              {forum.lastPost && (
                <div className={styles.lastPostSection}>
                  <div className={styles.lastPostLabel}>Dernier message</div>
                  <div className={styles.lastPostContent}>
                    {forum.lastPost.avatar && (
                      <img src={forum.lastPost.avatar} alt={forum.lastPost.character || forum.lastPost.author} className={styles.lastPostAvatar} />
                    )}
                    <div className={styles.lastPostDetails}>
                      <div className={styles.lastPostAuthor}>{forum.lastPost.character || forum.lastPost.author}</div>
                      <div className={styles.lastPostDate}>{formatDate(forum.lastPost.date)}</div>
                    </div>
                  </div>
                </div>
              )}
            </div>
          </Link>
        ))}
      </div>
    </div>
  );

  const renderStyle8 = () => (
    <div className={styles.style8}>
      <Breadcrumb items={breadcrumbItems} />
      <div className={styles.forumHeader}>
        <h1 className={styles.forumTitle}>Gotham City</h1>
        <p className={styles.forumDescription}>Sous-forums de Gotham City</p>
      </div>
      <div className={styles.forumsList}>
        {mockForums.map((forum) => (
          <Link 
            key={forum.id} 
            to={`/forums/${forum.slug}`}
            className={styles.forumCard}
            style={forum.banner ? {
              backgroundImage: `linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.7)), url("${forum.banner}")`,
              backgroundSize: 'cover',
              backgroundPosition: 'center',
            } : {}}
          >
            <div className={styles.forumOverlay}>
              <div className={styles.forumMainContent}>
                <div className={styles.forumContent}>
                  <div className={styles.forumHeaderRow}>
                    <h3 className={styles.forumName}>{forum.name}</h3>
                    <span className={styles.forumType}>{forum.type === 'roleplay' ? 'RP' : forum.type === 'important' ? 'Important' : 'Hors-RP'}</span>
                  </div>
                  {forum.description && (
                    <p className={styles.forumDescription}>{forum.description}</p>
                  )}
                  {forum.subforums.length > 0 && (
                    <div className={styles.subforumsList}>
                      <div className={styles.subforumsGrid}>
                        {forum.subforums.map((subforum) => (
                          <div key={subforum.id} className={styles.subforumItem}>
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                              <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z" />
                            </svg>
                            <span className={styles.subforumName}>{subforum.name}</span>
                          </div>
                        ))}
                      </div>
                    </div>
                  )}
                  <div className={styles.forumStats}>
                    <span>{forum.stats.totalThreads} discussions</span>
                    <span>•</span>
                    <span>{forum.stats.totalPosts} messages</span>
                    {forum.stats.subforumCount > 0 && (
                      <>
                        <span>•</span>
                        <span>{forum.stats.subforumCount} sous-forum{forum.stats.subforumCount > 1 ? 's' : ''}</span>
                      </>
                    )}
                  </div>
                </div>
                {forum.lastPost && (
                  <div className={styles.lastPostSectionRight}>
                    <div className={styles.lastPostLabel}>Dernier message</div>
                    <div className={styles.lastPostContent}>
                      {forum.lastPost.avatar && (
                        <img src={forum.lastPost.avatar} alt={forum.lastPost.character || forum.lastPost.author} className={styles.lastPostAvatar} />
                      )}
                      <div className={styles.lastPostDetails}>
                        <div className={styles.lastPostAuthor}>{forum.lastPost.character || forum.lastPost.author}</div>
                        <div className={styles.lastPostDate}>{formatDate(forum.lastPost.date)}</div>
                      </div>
                    </div>
                  </div>
                )}
              </div>
            </div>
          </Link>
        ))}
      </div>
    </div>
  );

  const renderStyle9 = () => (
    <div className={styles.style9}>
      <Breadcrumb items={breadcrumbItems} />
      <div className={styles.forumHeader}>
        <h1 className={styles.forumTitle}>Gotham City</h1>
        <p className={styles.forumDesc}>Sous-forums de Gotham City</p>
      </div>
      <div className={styles.forumsList}>
        {mockForums.map((forum) => (
          <Link 
            key={forum.id} 
            to={`/forums/${forum.slug}`}
            className={styles.forumCard}
          >
            {/* Background banner */}
            {forum.banner && (
              <div 
                className={styles.cardBanner}
                style={{ backgroundImage: `url("${forum.banner}")` }}
              />
            )}
            <div className={styles.cardOverlay} />
            
            {/* Main content */}
            <div className={styles.cardContent}>
              {/* Left: Info */}
              <div className={styles.cardInfo}>
                <div className={styles.cardHeader}>
                  <h3 className={styles.cardTitle}>{forum.name}</h3>
                  <span className={`${styles.cardBadge} ${styles[forum.type]}`}>
                    {forum.type === 'roleplay' ? 'RP' : forum.type === 'important' ? '★' : 'HRP'}
                  </span>
                </div>
                
                {forum.description && (
                  <p className={styles.cardDesc}>{forum.description}</p>
                )}
                
                {/* Subforums */}
                {forum.subforums.length > 0 && (
                  <div className={styles.subforumsRow}>
                    {forum.subforums.map((sub) => (
                      <div key={sub.id} className={styles.subforumChip}>
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
                          <path d="M10 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2h-8l-2-2z"/>
                        </svg>
                        {sub.name}
                      </div>
                    ))}
                  </div>
                )}
                
                <div className={styles.cardMeta}>
                  <span>{forum.stats.totalThreads} discussions</span>
                  <span className={styles.metaDot}>•</span>
                  <span>{forum.stats.totalPosts} messages</span>
                </div>
              </div>
              
              {/* Right: Last post sidebar */}
              {forum.lastPost && (
                <div className={styles.cardSidebar}>
                  <div className={styles.sidebarLabel}>Dernier message</div>
                  {forum.lastPost.avatar && (
                    <img 
                      src={forum.lastPost.avatar} 
                      alt="" 
                      className={styles.sidebarAvatar}
                    />
                  )}
                  <div className={styles.sidebarAuthor}>
                    {forum.lastPost.character || forum.lastPost.author}
                  </div>
                  <div className={styles.sidebarDate}>
                    {formatDate(forum.lastPost.date)}
                  </div>
                </div>
              )}
            </div>
          </Link>
        ))}
      </div>
    </div>
  );

  const renderSelectedStyle = () => {
    switch (selectedStyle) {
      case 'style1': return renderStyle1();
      case 'style2': return renderStyle2();
      case 'style3': return renderStyle3();
      case 'style4': return renderStyle4();
      case 'style5': return renderStyle5();
      case 'style6': return renderStyle6();
      case 'style7': return renderStyle7();
      case 'style8': return renderStyle8();
      case 'style9': return renderStyle9();
      default: return renderStyle1();
    }
  };

  return (
    <Layout>
      <div className={styles.container}>
        <div className={styles.controls}>
          <h2 className={styles.controlsTitle}>Styles de cartes de forums</h2>
          <div className={styles.styleSelector}>
            {stylesList.map((style) => (
              <button
                key={style.id}
                className={`${styles.styleButton} ${selectedStyle === style.id ? styles.styleButtonActive : ''}`}
                onClick={() => setSelectedStyle(style.id)}
              >
                {style.name}
              </button>
            ))}
          </div>
        </div>
        {renderSelectedStyle()}
      </div>
    </Layout>
  );
};

export default ForumDetail;

