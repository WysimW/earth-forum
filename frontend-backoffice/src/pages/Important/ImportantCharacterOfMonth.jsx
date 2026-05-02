import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { PictureOutlined } from '@ant-design/icons';
import { Alert, Button, Card, Form, Image, Input, Select, Space, Table, message } from 'antd';
import { useUniverse } from '../../contexts/UniverseContext';
import MediaLibrary from '../../components/MediaLibrary/MediaLibrary';
import importantAdminService from '../../services/importantAdminService';

const ALIGNMENT_OPTIONS = [
  { value: 'hero', label: 'Super-héros' },
  { value: 'villain', label: 'Super-vilains' },
  { value: 'antihero', label: 'Anti-héros' },
  { value: 'vigilante', label: 'Vigilante' },
  { value: 'neutral', label: 'Neutre' },
  { value: 'other', label: 'Autre' },
];

const ImportantCharacterOfMonth = () => {
  const { selectedUniverseId } = useUniverse();
  const [history, setHistory] = useState([]);
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [mediaLibraryOpen, setMediaLibraryOpen] = useState(false);
  const [selectedImage, setSelectedImage] = useState(null);
  const [form] = Form.useForm();
  const canLoad = useMemo(() => Boolean(selectedUniverseId), [selectedUniverseId]);

  const loadHistory = useCallback(async () => {
    if (!canLoad) return;
    try {
      setLoading(true);
      const data = await importantAdminService.getCharacterHistory(selectedUniverseId);
      setHistory(Array.isArray(data?.items) ? data.items : []);
    } catch (err) {
      message.error('Impossible de charger l’historique.');
    } finally {
      setLoading(false);
    }
  }, [canLoad, selectedUniverseId]);

  useEffect(() => {
    loadHistory();
  }, [loadHistory]);

  const onSubmit = async (values) => {
    if (!canLoad) return;
    try {
      setSaving(true);
      await importantAdminService.createCharacter({
        universe_id: Number(selectedUniverseId),
        year: Number(values.year),
        month: Number(values.month),
        first_name: values.first_name,
        last_name: values.last_name,
        nickname: values.nickname,
        alignment: values.alignment,
        powers: values.powers,
        weaknesses: values.weaknesses,
        image_url: values.image_url || null,
        who_is_text: values.who_is_text,
        why_play_text: values.why_play_text,
        quick_create_payload: {
          name: `${values.first_name} ${values.last_name}`.trim(),
          firstName: values.first_name,
          lastName: values.last_name,
          nickname: values.nickname,
          alignment: values.alignment,
          powers: values.powers,
          weaknesses: values.weaknesses,
          whoIsText: values.who_is_text,
          whyPlayText: values.why_play_text,
          imageUrl: values.image_url || null,
        },
      });
      message.success('Personnage du mois enregistré.');
      form.resetFields();
      setSelectedImage(null);
      await loadHistory();
    } catch (err) {
      message.error('Erreur lors de la sauvegarde.');
    } finally {
      setSaving(false);
    }
  };

  return (
    <Space direction="vertical" size={16} style={{ width: '100%' }}>
      {!canLoad ? <Alert type="info" message="Sélectionne un univers pour gérer ce module." /> : null}
      <Card title="Éditeur personnage du mois">
        <Form
          form={form}
          layout="vertical"
          onFinish={onSubmit}
          initialValues={{ month: new Date().getMonth() + 1, year: new Date().getFullYear(), alignment: 'neutral' }}
        >
          <Space wrap style={{ width: '100%' }}>
            <Form.Item label="Mois" name="month" rules={[{ required: true }]}>
              <Input type="number" min={1} max={12} />
            </Form.Item>
            <Form.Item label="Année" name="year" rules={[{ required: true }]}>
              <Input type="number" min={2000} />
            </Form.Item>
            <Form.Item label="Prénom" name="first_name" rules={[{ required: true }]}>
              <Input />
            </Form.Item>
            <Form.Item label="Nom" name="last_name" rules={[{ required: true }]}>
              <Input />
            </Form.Item>
          </Space>
          <Space wrap style={{ width: '100%' }}>
            <Form.Item label="Pseudo" name="nickname" rules={[{ required: true }]}>
              <Input />
            </Form.Item>
            <Form.Item label="Alignement" name="alignment" rules={[{ required: true }]}>
              <Select options={ALIGNMENT_OPTIONS} />
            </Form.Item>
          </Space>
          <Form.Item label="Image" name="image_url">
            <div>
              {selectedImage ? (
                <div style={{ marginBottom: 8 }}>
                  <Image
                    src={selectedImage.url}
                    alt={selectedImage.originalFilename || 'Image personnage du mois'}
                    style={{ maxWidth: '200px', maxHeight: '120px', objectFit: 'contain' }}
                  />
                  <div style={{ marginTop: 4, fontSize: '12px', color: '#666' }}>
                    {selectedImage.originalFilename || selectedImage.url}
                  </div>
                </div>
              ) : null}
              <Space>
                <Button icon={<PictureOutlined />} onClick={() => setMediaLibraryOpen(true)}>
                  {selectedImage ? "Changer l'image" : 'Sélectionner une image'}
                </Button>
                {selectedImage && (
                  <Button
                    onClick={() => {
                      setSelectedImage(null);
                      form.setFieldsValue({ image_url: null });
                    }}
                  >
                    Supprimer
                  </Button>
                )}
              </Space>
            </div>
          </Form.Item>
          <Form.Item label="Pouvoirs" name="powers" rules={[{ required: true }]}>
            <Input.TextArea rows={3} />
          </Form.Item>
          <Form.Item label="Faiblesses" name="weaknesses" rules={[{ required: true }]}>
            <Input.TextArea rows={3} />
          </Form.Item>
          <Form.Item label="Qui est-ce ?" name="who_is_text" rules={[{ required: true }]}>
            <Input.TextArea rows={4} />
          </Form.Item>
          <Form.Item label="Pourquoi l'incarner ?" name="why_play_text" rules={[{ required: true }]}>
            <Input.TextArea rows={4} />
          </Form.Item>
          <Button type="primary" htmlType="submit" loading={saving} disabled={!canLoad}>
            Enregistrer
          </Button>
        </Form>
      </Card>

      <Card title="Historique">
        <Table
          rowKey="id"
          loading={loading}
          dataSource={history}
          columns={[
            { title: 'Date', render: (_, row) => `${row.month}/${row.year}` },
            { title: 'Nom', render: (_, row) => `${row.firstName} ${row.lastName}` },
            { title: 'Pseudo', dataIndex: 'nickname' },
            { title: 'Alignement', dataIndex: 'alignment' },
          ]}
        />
      </Card>
      <MediaLibrary
        open={mediaLibraryOpen}
        onClose={() => setMediaLibraryOpen(false)}
        onSelect={(media) => {
          setSelectedImage(media);
          form.setFieldsValue({ image_url: media.url });
        }}
        value={selectedImage}
      />
    </Space>
  );
};

export default ImportantCharacterOfMonth;

