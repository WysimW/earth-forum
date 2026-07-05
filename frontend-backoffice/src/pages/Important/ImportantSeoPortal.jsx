import React, { useEffect, useState } from 'react';
import { Button, Card, Space, Typography, message } from 'antd';
import SeoFieldsPanel from '../../components/Seo/SeoFieldsPanel';
import { EMPTY_SEO } from '../../components/Seo/seoConstants';
import seoAdminService from '../../services/seoAdminService';

const { Text } = Typography;
const FRONTEND_URL = process.env.REACT_APP_FRONTEND_URL || 'http://localhost:3003';

const ImportantSeoPortal = () => {
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [seo, setSeo] = useState(EMPTY_SEO);

  useEffect(() => {
    const load = async () => {
      try {
        setLoading(true);
        const data = await seoAdminService.getSitePage('portal');
        setSeo({ ...EMPTY_SEO, ...(data?.seo || {}) });
      } catch (err) {
        message.error('Impossible de charger le SEO de la page d\'accueil.');
      } finally {
        setLoading(false);
      }
    };
    load();
  }, []);

  const save = async () => {
    try {
      setSaving(true);
      await seoAdminService.saveSitePage('portal', { seo });
      message.success('SEO de la page d\'accueil enregistré.');
    } catch (err) {
      message.error('Erreur lors de la sauvegarde.');
    } finally {
      setSaving(false);
    }
  };

  return (
    <Card loading={loading} title="SEO — Page d'accueil">
      <Space direction="vertical" size={16} style={{ width: '100%' }}>
        <Text type="secondary">
          Optimisez le référencement de la page portail ({FRONTEND_URL}/).
        </Text>
        <SeoFieldsPanel
          value={seo}
          onChange={setSeo}
          collapsible={false}
          pageUrl={`${FRONTEND_URL}/`}
          defaults={{
            metaTitle: 'Earth Forum — Forum de roleplay',
            metaDescription: 'Explorez les univers DC, Marvel, Star Wars et plus encore.',
          }}
        />
        <Button type="primary" onClick={save} loading={saving}>
          Enregistrer
        </Button>
      </Space>
    </Card>
  );
};

export default ImportantSeoPortal;
