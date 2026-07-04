import React from 'react';
import { Link } from 'react-router-dom';
import styles from '../Dashboard.module.css';

const DashboardSection = ({
  title,
  accent = 'primary',
  actionLabel,
  actionTo,
  onAction,
  emptyIcon,
  emptyTitle,
  emptyMessage,
  emptyActionLabel,
  emptyActionTo,
  children,
  isEmpty,
}) => (
  <section className={styles.section}>
    <header className={`${styles.sectionHeader} ${styles[`sectionHeader${accent}`]}`}>
      <h2 className={styles.sectionTitle}>{title}</h2>
      {actionLabel && actionTo && (
        <Link to={actionTo} className={styles.sectionAction}>
          {actionLabel}
        </Link>
      )}
      {actionLabel && onAction && !actionTo && (
        <button type="button" className={styles.sectionActionButton} onClick={onAction}>
          {actionLabel}
        </button>
      )}
    </header>

    <div className={styles.sectionBody}>
      {isEmpty ? (
        <div className={styles.emptyState}>
          {emptyIcon && <div className={styles.emptyIcon}>{emptyIcon}</div>}
          {emptyTitle && <h3 className={styles.emptyTitle}>{emptyTitle}</h3>}
          {emptyMessage && <p className={styles.emptyMessage}>{emptyMessage}</p>}
          {emptyActionLabel && emptyActionTo && (
            <Link to={emptyActionTo} className={styles.emptyAction}>
              {emptyActionLabel}
            </Link>
          )}
        </div>
      ) : (
        children
      )}
    </div>

    {actionLabel && actionTo && !isEmpty && (
      <footer className={styles.sectionFooter}>
        <Link to={actionTo} className={styles.sectionFooterLink}>
          Voir tout {actionLabel.replace(/^\+?\s*/, '').toLowerCase()} →
        </Link>
      </footer>
    )}
  </section>
);

export default DashboardSection;
