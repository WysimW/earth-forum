import React, { useEffect, useState } from 'react';
import { Button, Card, Form, Input, InputNumber, Select, Space, message } from 'antd';
import { useNavigate, useParams } from 'react-router-dom';
import api from '../../services/api';
import './CategoryForm.css';

const CategoryForm = () => {
  const [form] = Form.useForm();
  const navigate = useNavigate();
  const { id } = useParams();
  const isEditing = Boolean(id);
  const [loading, setLoading] = useState(false);
  const [types, setTypes] = useState([]);

  useEffect(() => {
    fetchMeta();
    if (isEditing) {
      fetchCategory();
    }
  }, [id, isEditing]);

  const fetchMeta = async () => {
    try {
      const response = await api.get('/api/admin/categories/meta');
      setTypes(response.data?.types || []);
    } catch (error) {
      message.error(error.response?.data?.error || 'Erreur lors du chargement des types');
    }
  };

  const fetchCategory = async () => {
    setLoading(true);
    try {
      const response = await api.get(`/api/admin/categories/${id}`);
      const category = response.data;
      form.setFieldsValue({
        name: category.name,
        description: category.description || '',
        type_id: category.type_id,
        home_order: category.home_order,
      });
    } catch (error) {
      message.error(error.response?.data?.error || 'Erreur lors du chargement de la catégorie');
      navigate('/forum-categories');
    } finally {
      setLoading(false);
    }
  };

  const handleSubmit = async (values) => {
    setLoading(true);
    try {
      const data = {
        name: values.name,
        description: values.description || null,
        type_id: values.type_id,
        home_order: values.home_order ?? null,
      };

      if (isEditing) {
        await api.put(`/api/admin/categories/${id}`, data);
        message.success('Catégorie modifiée avec succès');
      } else {
        await api.post('/api/admin/categories', data);
        message.success('Catégorie créée avec succès');
      }

      navigate('/forum-categories');
    } catch (error) {
      message.error(
        error.response?.data?.error ||
        (isEditing ? 'Erreur lors de la modification' : 'Erreur lors de la création')
      );
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="category-form-page">
      <Card title={isEditing ? 'Modifier la catégorie' : 'Créer une catégorie'}>
        <Form
          form={form}
          layout="vertical"
          onFinish={handleSubmit}
        >
          <Form.Item
            name="name"
            label="Nom"
            rules={[{ required: true, message: 'Le nom est requis' }]}
          >
            <Input placeholder="Nom de la catégorie" />
          </Form.Item>

          <Form.Item name="description" label="Description">
            <Input.TextArea rows={4} placeholder="Description (optionnel)" />
          </Form.Item>

          <Form.Item
            name="type_id"
            label="Type de catégorie"
            rules={[{ required: true, message: 'Le type est requis' }]}
          >
            <Select placeholder="Sélectionnez un type">
              {types.map((type) => (
                <Select.Option key={type.id} value={type.id}>
                  {type.name}
                </Select.Option>
              ))}
            </Select>
          </Form.Item>

          <Form.Item
            name="home_order"
            label="Home order"
            tooltip="Ordre d'affichage sur la page d'accueil du forum. Laissez vide pour placer en fin de liste."
          >
            <InputNumber min={0} style={{ width: '100%' }} placeholder="Automatique" />
          </Form.Item>

          <Form.Item>
            <Space>
              <Button type="primary" htmlType="submit" loading={loading}>
                {isEditing ? 'Enregistrer' : 'Créer'}
              </Button>
              <Button onClick={() => navigate('/forum-categories')}>
                Annuler
              </Button>
            </Space>
          </Form.Item>
        </Form>
      </Card>
    </div>
  );
};

export default CategoryForm;
