import React, { useState } from 'react';
import Layout from '../../../components/Layout/Layout';
import Breadcrumb from '../../../components/Breadcrumb/Breadcrumb';
import styles from './ThreadPostRP.module.css';

const ThreadPostRP = () => {
  const [selectedStyle, setSelectedStyle] = useState('style1');

  // Données de test pour des posts RP avec informations de personnages
  const mockPosts = [
    {
      postId: 1,
      character: {
        name: 'Batman',
        alias: 'Bruce Wayne',
        avatar: 'https://via.placeholder.com/80',
        moralAlignment: 'Héros',
        factions: ['Justice League', 'Bat Family'],
      },
      date: 'Il y a 2 heures',
      content: `La pluie tombait dru sur les toits de Gotham, créant une symphonie de gouttes qui résonnait dans la nuit. Batman se tenait immobile sur le bord d'un immeuble, scrutant les rues sombres en contrebas.

*Il ajusta ses gants et prit une profonde inspiration.*

"Oracle, tu m'entends ?" demanda-t-il dans son micro.

*Une voix féminine répondit dans son oreillette.*

"Oui, Batman. J'ai repéré une activité suspecte près du port. Tu veux que je t'envoie les coordonnées ?"

"Fais-le. Et reste en contact."

*Il se lança dans le vide, sa cape se déployant comme des ailes de chauve-souris alors qu'il planait vers sa destination.*

La ville ne dormait jamais, et lui non plus. Chaque nuit était une nouvelle bataille contre le chaos qui rongeait Gotham de l'intérieur.`,
    },
    {
      postId: 2,
      character: {
        name: 'Harley Quinn',
        alias: 'Harleen Quinzel',
        avatar: 'https://via.placeholder.com/80',
        moralAlignment: 'Antihéros',
        factions: ['Birds of Prey'],
      },
      date: 'Il y a 1 heure',
      content: `*Harley sautillait joyeusement dans les rues de Gotham, son marteau sur l'épaule.*

"Hey, Batsy !" cria-t-elle en apercevant une silhouette sombre sur un toit. "Tu veux jouer ?"

*Elle éclata de rire, un son qui résonnait étrangement dans la nuit.*

"Parce que moi, j'ai envie de m'amuser ce soir ! Et tu sais quoi ? J'ai même apporté des amis !"

*Elle fit un geste vers l'ombre où se tenaient plusieurs silhouettes menaçantes.*

"Alors, qu'est-ce que tu dis ? On fait une petite fête ?"

*Son rire maniaque résonna à nouveau alors qu'elle brandissait son marteau.*

"Parce que moi, j'ai toujours aimé les fêtes ! Et celle-ci va être... explosive !"`,
    },
    {
      postId: 3,
      character: {
        name: 'Wonder Woman',
        alias: 'Diana Prince',
        avatar: 'https://via.placeholder.com/80',
        moralAlignment: 'Héros',
        factions: ['Justice League', 'Themyscira'],
      },
      date: 'Il y a 45 minutes',
      content: `*Diana se tenait fièrement au centre de la salle de conférence de la Justice League, ses bras croisés sur sa poitrine.*

"Mes amis," commença-t-elle d'une voix ferme mais bienveillante, "nous avons été témoins de trop de souffrance ces derniers temps. Il est temps d'agir."

*Elle parcourut la pièce du regard, rencontrant les yeux de chaque membre présent.*

"Gotham n'est qu'un exemple parmi tant d'autres. Partout dans le monde, l'injustice prospère. Mais nous sommes là pour changer cela."

*Elle déroula son lasso de vérité, qui brillait d'une lumière dorée.*

"Ensemble, nous pouvons faire la différence. Mais cela nécessite de l'unité, de la confiance, et surtout... de l'espoir."

*Elle fit une pause, laissant ses mots résonner.*

"L'espoir que nous pouvons créer un monde meilleur. Un monde où la justice triomphe, où les innocents sont protégés, où les tyrans sont défaits."

*Elle serra son poing.*

"Qui est avec moi ?"`,
    },
    {
      postId: 4,
      character: {
        name: 'The Joker',
        alias: 'Inconnu',
        avatar: 'https://via.placeholder.com/80',
        moralAlignment: 'Vilain',
        factions: [],
      },
      date: 'Il y a 30 minutes',
      content: `*Le Joker éclata de rire, un son qui glaçait le sang.*

"Ha ha ha ! Vous savez ce qui est drôle ?" demanda-t-il, les larmes aux yeux. "Tout ! Absolument tout !"

*Il se pencha en avant, son sourire déformé s'élargissant.*

"La vie est une blague, mes amis. Une grande, grande blague. Et moi ? Je suis le punchline !"

*Il fit tourner un couteau dans sa main.*

"Batman pense qu'il peut sauver cette ville. Il pense qu'il peut apporter la justice. Mais vous savez quoi ?"

*Il s'arrêta, son regard devenant soudainement sérieux.*

"Il ne peut pas. Parce que la vraie blague, c'est que cette ville est déjà perdue. Et nous ? Nous sommes tous des clowns dans ce cirque."

*Il éclata à nouveau de rire.*

"Alors, pourquoi ne pas s'amuser un peu avant la fin ?"`,
    },
  ];

  const stylesList = [
    { id: 'style1', name: 'Style 1 - Classique avec infos personnage' },
    { id: 'style2', name: 'Style 2 - Sidebar personnage' },
    { id: 'style3', name: 'Style 3 - Card élégante' },
    { id: 'style4', name: 'Style 4 - Moderne avec badges' },
    { id: 'style5', name: 'Style 5 - Minimaliste RP' },
    { id: 'style6', name: 'Style 6 - Immersif' },
  ];

  const renderPost = (post, styleId) => {
    const { character } = post;
    
    switch (styleId) {
      case 'style1':
        return (
          <div key={post.postId} className={styles.style1Post}>
            <div className={styles.postHeader}>
              <div className={styles.characterSection}>
                <img src={character.avatar} alt={character.name} className={styles.avatar} />
                <div className={styles.characterInfo}>
                  <div className={styles.characterNameRow}>
                    <span className={styles.characterName}>{character.name}</span>
                    <span className={styles.characterAlias}>({character.alias})</span>
                  </div>
                  <div className={styles.characterMeta}>
                    <span className={styles.alignment}>{character.moralAlignment}</span>
                    {character.factions.length > 0 && (
                      <span className={styles.factions}>{character.factions.join(', ')}</span>
                    )}
                  </div>
                  <span className={styles.postDate}>{post.date}</span>
                </div>
              </div>
              <div className={styles.postActions}>
                <button className={styles.actionBtn}>Citer</button>
                <button className={styles.actionBtn}>Éditer</button>
              </div>
            </div>
            <div className={styles.postContent}>{post.content}</div>
          </div>
        );

      case 'style2':
        return (
          <div key={post.postId} className={styles.style2Post}>
            <div className={styles.characterSidebar}>
              <img src={character.avatar} alt={character.name} className={styles.avatarLarge} />
              <div className={styles.characterDetails}>
                <div className={styles.characterName}>{character.name}</div>
                <div className={styles.characterAlias}>{character.alias}</div>
                <div className={styles.alignmentBadge}>{character.moralAlignment}</div>
                {character.factions.length > 0 && (
                  <div className={styles.factionsList}>
                    {character.factions.map((faction, idx) => (
                      <span key={idx} className={styles.factionTag}>{faction}</span>
                    ))}
                  </div>
                )}
                <div className={styles.postDate}>{post.date}</div>
              </div>
            </div>
            <div className={styles.postMain}>
              <div className={styles.postContent}>{post.content}</div>
              <div className={styles.postActions}>
                <button className={styles.actionBtn}>Citer</button>
                <button className={styles.actionBtn}>Éditer</button>
              </div>
            </div>
          </div>
        );

      case 'style3':
        return (
          <div key={post.postId} className={styles.style3Post}>
            <div className={styles.postCard}>
              <div className={styles.postHeader}>
                <div className={styles.characterHeader}>
                  <img src={character.avatar} alt={character.name} className={styles.avatar} />
                  <div>
                    <div className={styles.characterName}>{character.name}</div>
                    <div className={styles.characterAlias}>{character.alias}</div>
                  </div>
                </div>
                <div className={styles.postDate}>{post.date}</div>
              </div>
              <div className={styles.characterBadges}>
                <span className={styles.alignmentBadge}>{character.moralAlignment}</span>
                {character.factions.map((faction, idx) => (
                  <span key={idx} className={styles.factionBadge}>{faction}</span>
                ))}
              </div>
              <div className={styles.postContent}>{post.content}</div>
            </div>
          </div>
        );

      case 'style4':
        return (
          <div key={post.postId} className={styles.style4Post}>
            <div className={styles.postHeader}>
              <div className={styles.characterSection}>
                <img src={character.avatar} alt={character.name} className={styles.avatar} />
                <div className={styles.characterInfo}>
                  <div className={styles.characterName}>{character.name}</div>
                  <div className={styles.characterAlias}>{character.alias}</div>
                </div>
              </div>
              <div className={styles.postMeta}>
                <span className={styles.postDate}>{post.date}</span>
                <div className={styles.badgesRow}>
                  <span className={styles.alignmentBadge}>{character.moralAlignment}</span>
                  {character.factions.map((faction, idx) => (
                    <span key={idx} className={styles.factionBadge}>{faction}</span>
                  ))}
                </div>
              </div>
            </div>
            <div className={styles.postContent}>{post.content}</div>
            <div className={styles.postActions}>
              <button className={styles.actionBtn}>Citer</button>
              <button className={styles.actionBtn}>Éditer</button>
            </div>
          </div>
        );

      case 'style5':
        return (
          <div key={post.postId} className={styles.style5Post}>
            <div className={styles.postMinimal}>
              <div className={styles.characterLine}>
                <img src={character.avatar} alt={character.name} className={styles.avatarSmall} />
                <span className={styles.characterName}>{character.name}</span>
                <span className={styles.characterAlias}>({character.alias})</span>
                <span className={styles.postDate}>{post.date}</span>
              </div>
              <div className={styles.postContent}>{post.content}</div>
            </div>
          </div>
        );

      case 'style6':
        return (
          <div key={post.postId} className={styles.style6Post}>
            <div className={styles.immersiveHeader}>
              <div className={styles.characterPortrait}>
                <img src={character.avatar} alt={character.name} className={styles.avatarPortrait} />
                <div className={styles.portraitOverlay}>
                  <div className={styles.characterName}>{character.name}</div>
                  <div className={styles.characterAlias}>{character.alias}</div>
                </div>
              </div>
              <div className={styles.characterStats}>
                <div className={styles.statItem}>
                  <span className={styles.statLabel}>Alignement</span>
                  <span className={styles.statValue}>{character.moralAlignment}</span>
                </div>
                {character.factions.length > 0 && (
                  <div className={styles.statItem}>
                    <span className={styles.statLabel}>Factions</span>
                    <div className={styles.factionsRow}>
                      {character.factions.map((faction, idx) => (
                        <span key={idx} className={styles.factionTag}>{faction}</span>
                      ))}
                    </div>
                  </div>
                )}
                <div className={styles.postDate}>{post.date}</div>
              </div>
            </div>
            <div className={styles.postContent}>{post.content}</div>
            <div className={styles.postActions}>
              <button className={styles.actionBtn}>Citer</button>
              <button className={styles.actionBtn}>Éditer</button>
            </div>
          </div>
        );

      default:
        return null;
    }
  };

  const breadcrumbItems = [
    { name: 'Accueil', url: '/' },
    { name: 'Composants', url: '/components' },
    { name: 'Posts RP', url: null }
  ];

  return (
    <Layout>
      <div className={styles.container}>
        <Breadcrumb items={breadcrumbItems} />
        
        <div className={styles.controls}>
          <h1 className={styles.pageTitle}>Styles de Posts RP</h1>
          <div className={styles.styleSelector}>
            {stylesList.map((style) => (
              <button
                key={style.id}
                className={`${styles.styleBtn} ${selectedStyle === style.id ? styles.active : ''}`}
                onClick={() => setSelectedStyle(style.id)}
              >
                {style.name}
              </button>
            ))}
          </div>
        </div>

        <div className={styles.preview}>
          <h2 className={styles.previewTitle}>Aperçu - {stylesList.find(s => s.id === selectedStyle)?.name}</h2>
          <div className={styles.postsContainer}>
            {mockPosts.map((post) => renderPost(post, selectedStyle))}
          </div>
        </div>
      </div>
    </Layout>
  );
};

export default ThreadPostRP;


