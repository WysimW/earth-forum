import React, { useEffect, useState } from 'react';
import { Button, Card, Form, Input, Select, Space, message } from 'antd';
import { useNavigate, useParams } from 'react-router-dom';
import api from '../../services/api';

const ALIGNMENT_OPTIONS = [
  { value: 'hero', label: 'Super-héros' },
  { value: 'villain', label: 'Super-vilains' },
  { value: 'antihero', label: 'Anti-héros' },
  { value: 'vigilante', label: 'Vigilante' },
  { value: 'neutral', label: 'Neutre' },
  { value: 'other', label: 'Autre' },
];

const SCOPE_OPTIONS = [
  { value: 'galaxy', label: 'Galaxie' },
  { value: 'international', label: 'International' },
  { value: 'national', label: 'National' },
  { value: 'regional', label: 'Régional' },
  { value: 'local', label: 'Local' },
];

const FactionForm = () => {
  const [form] = Form.useForm();
  const navigate = useNavigate();
  const { id } = useParams();
  const isEditing = Boolean(id);
  const [loading, setLoading] = useState(false);
  const [metaLoading, setMetaLoading] = useState(false);
  const [users, setUsers] = useState([]);
  const [universes, setUniverses] = useState([]);
  const [locations, setLocations] = useState([]);

  useEffect(() => {
    fetchMeta();
    if (isEditing) {
      fetchFaction();
    }
  }, [id, isEditing]);

  const fetchMeta = async () => {
    setMetaLoading(true);
    try {
      const response = await api.get('/api/admin/factions/meta');
      setUsers(response.data?.users || []);
      setUniverses(response.data?.universes || []);
      setLocations(response.data?.locations || []);
    } catch (error) {
      message.error(error.response?.data?.error || 'Erreur lors du chargement des métadonnées');
    } finally {
      setMetaLoading(false);
    }
  };

  const fetchFaction = async () => {
    setLoading(true);
    try {
      const response = await api.get(`/api/admin/factions/${id}`);
      const faction = response.data;
      form.setFieldsValue({
        name: faction.name,
        universe_id: faction.universe?.id,
        founder_id: faction.founder?.id,
        alignment: faction.alignment || undefined,
        scope: faction.scope || undefined,
        status: faction.status || 'open',
        description: faction.description || '',
        objectives: faction.objectives || '',
        headquartersDescription: faction.headquartersDescription || '',
        headquarters_id: faction.headquarters?.id ?? null,
        logo: faction.logo || '',
        icon: faction.icon || '',
      });
    } catch (error) {
      message.error(error.response?.data?.error || 'Erreur lors du chargement de la faction');
      navigate('/factions');
    } finally {
      setLoading(false);
    }
  };

  const handleSubmit = async (values) => {
    setLoading(true);
    try {
      const payload = {
        ...values,
        headquarters_id: values.headquarters_id ?? null,
      };

      if (isEditing) {
        await api.put(`/api/admin/factions/${id}`, payload);
        message.success('Faction mise à jour avec succès');
      } else {
        await api.post('/api/admin/factions', payload);
        message.success('Faction créée avec succès');
      }
      navigate('/factions');
    } catch (error) {
      message.error(error.response?.data?.error || 'Erreur lors de l’enregistrement');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="faction-form-page">
      <Card title={isEditing ? 'Modifier la faction' : 'Créer une faction'}>
        <Form
          form={form}
          layout="vertical"
          onFinish={handleSubmit}
          initialValues={{ status: 'open' }}
        >
          <Form.Item name="name" label="Nom" rules={[{ required: true, message: 'Le nom est requis' }]}>
            <Input />
          </Form.Item>

          <Form.Item name="universe_id" label="Univers" rules={[{ required: true, message: 'L’univers est requis' }]}>
            <Select
              showSearch
              loading={metaLoading}
              options={universes.map((u) => ({ value: u.id, label: u.name }))}
              optionFilterProp="label"
            />
          </Form.Item>

          <Form.Item name="founder_id" label="Chef de faction" rules={[{ required: true, message: 'Le chef est requis' }]}>
            <Select
              showSearch
              loading={metaLoading}
              options={users.map((u) => ({ value: u.id, label: `${u.pseudo} (${u.email})` }))}
              optionFilterProp="label"
            />
          </Form.Item>

          <Form.Item name="alignment" label="Alignement">
            <Select allowClear options={ALIGNMENT_OPTIONS} />
          </Form.Item>

          <Form.Item name="scope" label="Portée">
            <Select allowClear options={SCOPE_OPTIONS} />
          </Form.Item>

          <Form.Item name="status" label="Statut" rules={[{ required: true }]}>
            <Select
              options={[
                { value: 'open', label: 'Ouverte' },
                { value: 'closed', label: 'Fermée' },
              ]}
            />
          </Form.Item>

          <Form.Item name="headquarters_id" label="Lieu du QG">
            <Select
              allowClear
              showSearch
              loading={metaLoading}
              options={locations.map((location) => ({ value: location.id, label: location.name }))}
              optionFilterProp="label"
            />
          </Form.Item>

          <Form.Item name="headquartersDescription" label="Description du QG">
            <Input.TextArea rows={3} />
          </Form.Item>

          <Form.Item name="description" label="Description">
            <Input.TextArea rows={4} />
          </Form.Item>

          <Form.Item name="objectives" label="Objectifs">
            <Input.TextArea rows={3} />
          </Form.Item>

          <Form.Item name="logo" label="Logo (URL)">
            <Input />
          </Form.Item>

          <Form.Item name="icon" label="Icône (URL)">
            <Input />
          </Form.Item>

          <Form.Item>
            <Space>
              <Button type="primary" htmlType="submit" loading={loading}>
                {isEditing ? 'Mettre à jour' : 'Créer'}
              </Button>
              <Button onClick={() => navigate('/factions')}>
                Annuler
              </Button>
            </Space>
          </Form.Item>
        </Form>
      </Card>
    </div>
  );
};

export default FactionForm;

