import React from 'react';
import styles from './Badge.module.css';

const Badge = ({ 
  type = 'default', 
  children, 
  variant = 'filled',
  className = '' 
}) => {
  const badgeClasses = [
    styles.badge,
    styles[type],
    styles[variant],
    className,
  ].filter(Boolean).join(' ');

  return (
    <span className={badgeClasses}>
      {children}
    </span>
  );
};

export default Badge;

