import React from 'react';
import styles from './ErrorMessage.module.css';

const ErrorMessage = ({ message, onRetry }) => {
  return (
    <div className={styles.errorContainer}>
      <div className={styles.errorIcon}>⚠</div>
      <p className={styles.errorMessage}>{message}</p>
      {onRetry && (
        <button onClick={onRetry} className={styles.retryButton}>
          Réessayer
        </button>
      )}
    </div>
  );
};

export default ErrorMessage;

