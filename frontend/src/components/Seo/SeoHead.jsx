import React from 'react';
import { Helmet } from 'react-helmet-async';

const SeoHead = ({ seo }) => {
  if (!seo) {
    return null;
  }

  const {
    metaTitle,
    metaDescription,
    ogImage,
    canonical,
    robotsIndex,
  } = seo;

  const robots = robotsIndex === false ? 'noindex, nofollow' : 'index, follow';

  return (
    <Helmet>
      {metaTitle && <title>{metaTitle}</title>}
      {metaDescription && <meta name="description" content={metaDescription} />}
      {metaTitle && <meta property="og:title" content={metaTitle} />}
      {metaDescription && <meta property="og:description" content={metaDescription} />}
      {ogImage && <meta property="og:image" content={ogImage} />}
      {canonical && <meta property="og:url" content={canonical} />}
      {canonical && <link rel="canonical" href={canonical} />}
      <meta name="robots" content={robots} />
      <meta name="twitter:card" content={ogImage ? 'summary_large_image' : 'summary'} />
      {metaTitle && <meta name="twitter:title" content={metaTitle} />}
      {metaDescription && <meta name="twitter:description" content={metaDescription} />}
      {ogImage && <meta name="twitter:image" content={ogImage} />}
    </Helmet>
  );
};

export default SeoHead;
