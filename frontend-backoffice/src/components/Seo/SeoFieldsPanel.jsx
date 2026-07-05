import React, { useState } from 'react';
import { Button, Card, Form, Image, Input, Space, Switch, Typography } from 'antd';
import { PictureOutlined } from '@ant-design/icons';
import MediaLibrary, { getMediaStorageUrl } from '../MediaLibrary/MediaLibrary';
import SeoSerpPreview from './SeoSerpPreview';
import {
  EMPTY_SEO,
  SEO_DESC_GOOD,
  SEO_DESC_MAX,
  SEO_TITLE_GOOD,
  SEO_TITLE_MAX,
  getCounterStatus,
} from './seoConstants';

const { TextArea } = Input;
const { Text } = Typography;

const Counter = ({ length, goodLimit, maxLimit }) => {
  const status = getCounterStatus(length, goodLimit, maxLimit);
  const color = status === 'error' ? '#ff4d4f' : status === 'warning' ? '#faad14' : '#52c41a';

  return (
    <Text style={{ color, fontSize: 12 }}>
      {length}/{goodLimit}
    </Text>
  );
};

const SeoFieldsPanel = ({
  value = EMPTY_SEO,
  onChange,
  defaults = {},
  pageUrl = '',
  title = 'Référencement',
  collapsible = true,
}) => {
  const [mediaLibraryOpen, setMediaLibraryOpen] = useState(false);
  const seo = { ...EMPTY_SEO, ...value };

  const updateField = (field, fieldValue) => {
    onChange?.({ ...seo, [field]: fieldValue });
  };

  const content = (
    <Space direction="vertical" size="middle" style={{ width: '100%' }}>
      <SeoSerpPreview seo={seo} defaults={defaults} pageUrl={pageUrl} />

      <Form layout="vertical">
        <Form.Item
          label={(
            <Space style={{ width: '100%', justifyContent: 'space-between' }}>
              <span>Titre SEO</span>
              <Counter
                length={(seo.metaTitle || '').length}
                goodLimit={SEO_TITLE_GOOD}
                maxLimit={SEO_TITLE_MAX}
              />
            </Space>
          )}
        >
          <Input
            value={seo.metaTitle}
            onChange={(e) => updateField('metaTitle', e.target.value)}
            placeholder={defaults.metaTitle || 'Titre affiché dans Google'}
            maxLength={SEO_TITLE_MAX}
            showCount
          />
        </Form.Item>

        <Form.Item
          label={(
            <Space style={{ width: '100%', justifyContent: 'space-between' }}>
              <span>Meta description</span>
              <Counter
                length={(seo.metaDescription || '').length}
                goodLimit={SEO_DESC_GOOD}
                maxLimit={SEO_DESC_MAX}
              />
            </Space>
          )}
        >
          <TextArea
            value={seo.metaDescription}
            onChange={(e) => updateField('metaDescription', e.target.value)}
            placeholder={defaults.metaDescription || 'Description affichée dans les résultats de recherche'}
            rows={4}
            maxLength={SEO_DESC_MAX}
            showCount
          />
        </Form.Item>

        <Form.Item label="Image Open Graph">
          <Space direction="vertical" style={{ width: '100%' }}>
            <Button icon={<PictureOutlined />} onClick={() => setMediaLibraryOpen(true)}>
              Choisir une image
            </Button>
            {seo.ogImage && (
              <Image src={seo.ogImage} alt="Open Graph" style={{ maxWidth: 240, borderRadius: 8 }} />
            )}
          </Space>
        </Form.Item>

        <Form.Item label="Indexer par les moteurs de recherche">
          <Switch
            checked={seo.robotsIndex !== false}
            onChange={(checked) => updateField('robotsIndex', checked)}
          />
        </Form.Item>
      </Form>

      <MediaLibrary
        open={mediaLibraryOpen}
        onClose={() => setMediaLibraryOpen(false)}
        onSelect={(media) => {
          updateField('ogImage', getMediaStorageUrl(media));
          setMediaLibraryOpen(false);
        }}
      />
    </Space>
  );

  if (!collapsible) {
    return content;
  }

  return (
    <Card title={title} style={{ marginTop: 16 }}>
      {content}
    </Card>
  );
};

export default SeoFieldsPanel;
