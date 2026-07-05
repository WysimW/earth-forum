import React from 'react';
import './UniversePreview.css';

const UniversePreview = ({
  name,
  description,
  forumsTitle,
  portalBanner,
  forumsHeaderBanner,
  forumCount = 0,
  threadCount = 0,
}) => {
  const displayForumsTitle = forumsTitle?.trim() || (name ? `Forums de ${name}` : 'Forums');

  return (
    <div className="universe-preview">
      <h3 className="universe-preview__title">Aperçu</h3>

      <div className="universe-preview__grid">
        <div className="universe-preview__block">
          <p className="universe-preview__label">Carte portail</p>
          <div
            className="universe-preview__portal-card"
            style={portalBanner ? { backgroundImage: `url("${portalBanner}")` } : undefined}
          >
            <div className="universe-preview__portal-overlay" />
            <div className="universe-preview__portal-content">
              <h4>{name || 'Nom de l\'univers'}</h4>
              <p>{description || 'Description affichée sur le portail.'}</p>
              <span>{forumCount} forums • {threadCount} discussions</span>
            </div>
          </div>
        </div>

        <div className="universe-preview__block">
          <p className="universe-preview__label">Header page forums</p>
          <div
            className={`universe-preview__forums-header${forumsHeaderBanner ? ' universe-preview__forums-header--banner' : ''}`}
            style={forumsHeaderBanner ? { '--preview-banner': `url("${forumsHeaderBanner}")` } : undefined}
          >
            <p className="universe-preview__breadcrumb">PORTAIL › {name || 'Univers'}</p>
            <h4>{displayForumsTitle}</h4>
            <p>{description || 'Description de l\'univers.'}</p>
          </div>
        </div>
      </div>
    </div>
  );
};

export default UniversePreview;
