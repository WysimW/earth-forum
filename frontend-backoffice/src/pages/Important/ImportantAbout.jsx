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
import seoAdminService from '../../services/seoAdminService';

const { Text } = Typography;
const FRONTEND_URL = process.env.REACT_APP_FRONTEND_URL || 'http://localhost:3003';
const BtnHeadings = createDropdown('Format', [
  ['Paragraphe', 'formatBlock', 'DIV'],
  ['H1 - Section card', 'formatBlock', 'H1'],
  ['H2 - Titre', 'formatBlock', 'H2'],
  ['H3 - Sous-titre', 'formatBlock', 'H3'],
]);

const ImportantAbout = () => {
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');
  const [content, setContent] = useState('');
  const [seo, setSeo] = useState(EMPTY_SEO);

  const loadAbout = async () => {
    try {
      setLoading(true);
      setError('');
      const data = await seoAdminService.getSitePage('about');
      setContent(data?.content || '');
      setSeo({ ...EMPTY_SEO, ...(data?.seo || {}) });
    } catch (err) {
      setError('Impossible de charger la page Qui sommes-nous.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadAbout();
  }, []);

  const saveAbout = async () => {
    try {
      setSaving(true);
      await seoAdminService.saveSitePage('about', { content, seo });
      message.success('Page Qui sommes-nous enregistrée.');
    } catch (err) {
      message.error('Erreur lors de la sauvegarde.');
    } finally {
      setSaving(false);
    }
  };

  return (
    <Card loading={loading} title="Qui sommes-nous">
      <Space direction="vertical" size={16} style={{ width: '100%' }}>
        <Text type="secondary">
          Contenu éditorial et référencement de la page publique /qui-sommes-nous.
        </Text>

        {error ? <Alert type="error" message={error} /> : null}

        <Editor
          value={content}
          onChange={(event) => setContent(event.target.value)}
          containerProps={{ style: { minHeight: 360 } }}
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
          pageUrl={`${FRONTEND_URL}/qui-sommes-nous`}
          defaults={{
            metaTitle: 'Qui sommes-nous',
            metaDescription: 'Découvrez l\'histoire et la communauté d\'Earth Forum.',
          }}
        />

        <Button type="primary" onClick={saveAbout} loading={saving}>
          Enregistrer
        </Button>
      </Space>
    </Card>
  );
};

export default ImportantAbout;
