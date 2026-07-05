import React from 'react';
import { Typography } from 'antd';
import { buildPreviewDescription, buildPreviewTitle } from './seoConstants';
import styles from './SeoSerpPreview.module.css';

const { Text } = Typography;

const SeoSerpPreview = ({ seo, defaults = {}, pageUrl = 'https://earth-forum.example/' }) => {
  const title = buildPreviewTitle(seo, defaults);
  const description = buildPreviewDescription(seo, defaults);
  const displayUrl = pageUrl.replace(/^https?:\/\//, '');

  return (
    <div className={styles.preview}>
      <Text type="secondary" className={styles.label}>
        Aperçu Google
      </Text>
      <div className={styles.snippet}>
        <div className={styles.url}>{displayUrl}</div>
        <div className={styles.title}>{title}</div>
        <div className={styles.description}>{description}</div>
      </div>
    </div>
  );
};

export default SeoSerpPreview;
