import React, { useCallback, useEffect, useState } from 'react';
import { Button, Card, Form, Input, message } from 'antd';
import importantAdminService from '../../services/importantAdminService';

const ImportantVote = () => {
  const [form] = Form.useForm();
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);

  const loadVoteUrl = useCallback(async () => {
    try {
      setLoading(true);
      const url = await importantAdminService.getVoteUrl();
      form.setFieldsValue({ url });
    } catch (err) {
      message.error('Impossible de charger le lien de vote.');
    } finally {
      setLoading(false);
    }
  }, [form]);

  useEffect(() => {
    loadVoteUrl();
  }, [loadVoteUrl]);

  const onFinish = async (values) => {
    try {
      setSaving(true);
      await importantAdminService.saveVoteUrl(values.url || '');
      message.success('Lien de vote enregistré.');
    } catch (err) {
      message.error('Erreur lors de la sauvegarde.');
    } finally {
      setSaving(false);
    }
  };

  return (
    <Card loading={loading} title="Lien global Votez pour nous">
      <Form form={form} layout="vertical" onFinish={onFinish}>
        <Form.Item
          label="URL de vote"
          name="url"
          rules={[
            {
              validator: (_, value) => {
                if (!value) return Promise.resolve();
                try {
                  new URL(value);
                  return Promise.resolve();
                } catch (error) {
                  return Promise.reject(new Error('URL invalide'));
                }
              },
            },
          ]}
        >
          <Input placeholder="https://..." />
        </Form.Item>
        <Button type="primary" htmlType="submit" loading={saving}>
          Enregistrer
        </Button>
      </Form>
    </Card>
  );
};

export default ImportantVote;

