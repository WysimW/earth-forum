import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { Alert, Button, Card, Space, Typography, message } from 'antd';
import SeoFieldsPanel from '../../components/Seo/SeoFieldsPanel';
import { EMPTY_SEO } from '../../components/Seo/seoConstants';
import { useUniverse } from '../../contexts/UniverseContext';
import seoAdminService from '../../services/seoAdminService';

const { Text, Title } = Typography;
const FRONTEND_URL = process.env.REACT_APP_FRONTEND_URL || 'http://localhost:3003';

const ImportantSeoUniverses = () => {
  const { universes, selectedUniverseId } = useUniverse();
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [seo, setSeo] = useState(EMPTY_SEO);
  const [memberOfMonthSeo, setMemberOfMonthSeo] = useState(EMPTY_SEO);
  const [characterOfMonthSeo, setCharacterOfMonthSeo] = useState(EMPTY_SEO);

  const selectedUniverse = useMemo(
    () => universes.find((item) => String(item.id) === String(selectedUniverseId)) || null,
    [universes, selectedUniverseId]
  );

  const loadSeo = useCallback(async () => {
    if (!selectedUniverseId) return;
    try {
      setLoading(true);
      const data = await seoAdminService.getUniverseSeo(selectedUniverseId);
      setSeo({ ...EMPTY_SEO, ...(data?.seo || {}) });
      setMemberOfMonthSeo({ ...EMPTY_SEO, ...(data?.memberOfMonthSeo || {}) });
      setCharacterOfMonthSeo({ ...EMPTY_SEO, ...(data?.characterOfMonthSeo || {}) });
    } catch (err) {
      message.error('Impossible de charger le SEO de l\'univers.');
    } finally {
      setLoading(false);
    }
  }, [selectedUniverseId]);

  useEffect(() => {
    loadSeo();
  }, [loadSeo]);

  const save = async () => {
    if (!selectedUniverseId) return;
    try {
      setSaving(true);
      await seoAdminService.saveUniverseSeo(selectedUniverseId, {
        seo,
        memberOfMonthSeo,
        characterOfMonthSeo,
      });
      message.success('SEO univers enregistré.');
    } catch (err) {
      message.error('Erreur lors de la sauvegarde.');
    } finally {
      setSaving(false);
    }
  };

  if (!selectedUniverseId) {
    return (
      <Alert
        type="info"
        message="Sélectionnez un univers dans le header pour configurer son SEO."
        showIcon
      />
    );
  }

  const slug = selectedUniverse?.slug || '';

  return (
    <Card loading={loading} title={`SEO — ${selectedUniverse?.name || 'Univers'}`}>
      <Space direction="vertical" size={24} style={{ width: '100%' }}>
        <Text type="secondary">
          Configurez le référencement des pages publiques liées à cet univers.
        </Text>

        <div>
          <Title level={5}>Page forums univers</Title>
          <SeoFieldsPanel
            value={seo}
            onChange={setSeo}
            collapsible={false}
            pageUrl={`${FRONTEND_URL}/univers/${slug}`}
            defaults={{
              metaTitle: `Forums ${selectedUniverse?.name || ''} | Earth Forum`,
              metaDescription: selectedUniverse?.description || '',
            }}
          />
        </div>

        <div>
          <Title level={5}>Membre du mois</Title>
          <SeoFieldsPanel
            value={memberOfMonthSeo}
            onChange={setMemberOfMonthSeo}
            collapsible={false}
            pageUrl={`${FRONTEND_URL}/member-of-month/${slug}`}
            defaults={{
              metaTitle: `Membre du mois — ${selectedUniverse?.name || ''}`,
              metaDescription: `Découvrez le membre du mois de l'univers ${selectedUniverse?.name || ''}.`,
            }}
          />
        </div>

        <div>
          <Title level={5}>Personnage du mois</Title>
          <SeoFieldsPanel
            value={characterOfMonthSeo}
            onChange={setCharacterOfMonthSeo}
            collapsible={false}
            pageUrl={`${FRONTEND_URL}/character-of-month/${slug}`}
            defaults={{
              metaTitle: `Personnage du mois — ${selectedUniverse?.name || ''}`,
              metaDescription: `Découvrez le personnage du mois de l'univers ${selectedUniverse?.name || ''}.`,
            }}
          />
        </div>

        <Button type="primary" onClick={save} loading={saving}>
          Enregistrer le SEO de l'univers
        </Button>
      </Space>
    </Card>
  );
};

export default ImportantSeoUniverses;
