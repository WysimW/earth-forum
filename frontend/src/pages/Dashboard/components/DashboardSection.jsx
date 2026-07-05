import React from 'react';
import { Link } from 'react-router-dom';
import styles from '../Dashboard.module.css';

const DashboardSection = ({
  title,
  actionLabel,
  actionTo,
  onAction,
  emptyTitle,
  emptyMessage,
  emptyActionLabel,
  emptyActionTo,
  children,
  isEmpty,
}) => (
  <section className={styles.section}>
    <header className={styles.sectionHeader}>
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
          {emptyTitle && <p className={styles.emptyTitle}>{emptyTitle}</p>}
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
  </section>
);

export default DashboardSection;
