import React, { useCallback, useEffect, useLayoutEffect, useRef, useState } from 'react';
import styles from './FilterToggle.module.css';

const FilterToggle = ({ options, value, onChange, ariaLabel }) => {
  const trackRef = useRef(null);
  const [indicator, setIndicator] = useState({ width: 0, x: 0 });

  const updateIndicator = useCallback(() => {
    const track = trackRef.current;
    if (!track) return;

    const activeButton = track.querySelector(`[data-value="${value}"]`);
    if (!activeButton) return;

    setIndicator({
      width: activeButton.offsetWidth,
      x: activeButton.offsetLeft,
    });
  }, [value]);

  useLayoutEffect(() => {
    updateIndicator();
  }, [updateIndicator, options]);

  useEffect(() => {
    window.addEventListener('resize', updateIndicator);
    return () => window.removeEventListener('resize', updateIndicator);
  }, [updateIndicator]);

  return (
    <div className={styles.track} ref={trackRef} role="group" aria-label={ariaLabel}>
      <span
        className={styles.indicator}
        style={{
          width: indicator.width,
          transform: `translateX(${indicator.x}px)`,
        }}
        aria-hidden="true"
      />
      {options.map((option) => (
        <button
          key={option.value}
          type="button"
          data-value={option.value}
          className={`${styles.tab} ${value === option.value ? styles.tabActive : ''}`}
          onClick={() => onChange(option.value)}
          aria-pressed={value === option.value}
        >
          {option.label}
        </button>
      ))}
    </div>
  );
};

export default FilterToggle;
