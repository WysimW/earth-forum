import React, { useEffect, useState } from 'react';
import Editor, {
  BtnBold,
  BtnBulletList,
  BtnClearFormatting,
  BtnItalic,
  BtnLink,
  BtnNumberedList,
  BtnRedo,
  BtnUnderline,
  BtnUndo,
  HtmlButton,
  Separator,
  Toolbar,
  createDropdown,
} from 'react-simple-wysiwyg';
import { Alert, Button, Card, Space, Typography, message } from 'antd';
import SeoFieldsPanel from '../../components/Seo/SeoFieldsPanel';
import { EMPTY_SEO } from '../../components/Seo/seoConstants';
import importantAdminService from '../../services/importantAdminService';
import seoAdminService from '../../services/seoAdminService';

const { Text } = Typography;
const FRONTEND_URL = process.env.REACT_APP_FRONTEND_URL || 'http://localhost:3003';
const BtnHeadings = createDropdown('Format', [
  ['Paragraphe', 'formatBlock', 'DIV'],
  ['H1 - Section card', 'formatBlock', 'H1'],
  ['H2 - Titre', 'formatBlock', 'H2'],
  ['H3 - Sous-titre', 'formatBlock', 'H3'],
]);

const ImportantRegulation = () => {
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');
  const [content, setContent] = useState('');
  const [seo, setSeo] = useState(EMPTY_SEO);

  const loadRegulation = async () => {
    try {
      setLoading(true);
      setError('');
      const [data, seoData] = await Promise.all([
        importantAdminService.getRegulation(),
        seoAdminService.getSitePage('regulation'),
      ]);
      setContent(data?.content || '');
      setSeo({ ...EMPTY_SEO, ...(seoData?.seo || {}) });
    } catch (err) {
      setError('Impossible de charger le règlement.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadRegulation();
  }, []);

  const saveRegulation = async () => {
    try {
      setSaving(true);
      await Promise.all([
        importantAdminService.saveRegulation(content),
        seoAdminService.saveSitePage('regulation', { seo }),
      ]);
      message.success('Règlement enregistré.');
    } catch (err) {
      message.error('Erreur lors de la sauvegarde.');
    } finally {
      setSaving(false);
    }
  };

  return (
    <Card loading={loading} title="Règlement global">
      <Space direction="vertical" size={16} style={{ width: '100%' }}>
        <Text type="secondary">
          Rédige le règlement avec l’éditeur standard. Utilise les titres pour les catégories et les listes pour structurer les règles.
        </Text>

        {error ? <Alert type="error" message={error} /> : null}

        <Editor
          value={content}
          onChange={(event) => setContent(event.target.value)}
          containerProps={{ style: { minHeight: 460 } }}
        >
          <Toolbar>
            <BtnUndo />
            <BtnRedo />
            <Separator />
            <BtnHeadings />
            <Separator />
            <BtnBold />
            <BtnItalic />
            <BtnUnderline />
            <Separator />
            <BtnNumberedList />
            <BtnBulletList />
            <Separator />
            <BtnLink />
            <BtnClearFormatting />
            <HtmlButton />
          </Toolbar>
        </Editor>

        <SeoFieldsPanel
          value={seo}
          onChange={setSeo}
          pageUrl={`${FRONTEND_URL}/reglement`}
          defaults={{
            metaTitle: 'Règlement',
            metaDescription: 'Consultez le règlement d\'Earth Forum.',
          }}
        />

        <Button type="primary" onClick={saveRegulation} loading={saving}>
          Enregistrer le règlement
        </Button>
      </Space>
    </Card>
  );
};

export default ImportantRegulation;

