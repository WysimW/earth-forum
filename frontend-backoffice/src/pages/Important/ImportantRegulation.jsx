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
import importantAdminService from '../../services/importantAdminService';

const { Text } = Typography;
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

  const loadRegulation = async () => {
    try {
      setLoading(true);
      setError('');
      const data = await importantAdminService.getRegulation();
      setContent(data?.content || '');
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
      await importantAdminService.saveRegulation(content);
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

        <Button type="primary" onClick={saveRegulation} loading={saving}>
          Enregistrer le règlement
        </Button>
      </Space>
    </Card>
  );
};

export default ImportantRegulation;

