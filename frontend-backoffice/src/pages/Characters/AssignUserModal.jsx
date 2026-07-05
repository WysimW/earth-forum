import React, { useEffect, useState } from 'react';
import { Modal, Select, message } from 'antd';
import api from '../../services/api';

const AssignUserModal = ({ open, character, onClose, onAssigned }) => {
  const [users, setUsers] = useState([]);
  const [loading, setLoading] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [selectedUserId, setSelectedUserId] = useState(null);
  const [selectedStatus, setSelectedStatus] = useState(null);

  useEffect(() => {
    if (!open) {
      return;
    }

    const fetchUsers = async () => {
      setLoading(true);
      try {
        const response = await api.get('/api/admin/users');
        setUsers(response.data || []);
      } catch (error) {
        message.error('Erreur lors du chargement des utilisateurs');
      } finally {
        setLoading(false);
      }
    };

    fetchUsers();
    setSelectedUserId(character?.user_id || null);
    setSelectedStatus(character?.status === 'abandoned' ? 'validated' : null);
  }, [open, character]);

  const requiresStatus = character?.status === 'abandoned';

  const handleSubmit = async () => {
    if (!selectedUserId) {
      message.warning('Sélectionnez un utilisateur');
      return;
    }

    if (requiresStatus && !selectedStatus) {
      message.warning('Choisissez un statut pour réactiver le personnage');
      return;
    }

    setSubmitting(true);
    try {
      const payload = { user_id: selectedUserId };
      if (requiresStatus) {
        payload.status = selectedStatus;
      }

      await api.put(`/api/admin/characters/${character.id}`, payload);
      message.success('Utilisateur assigné avec succès');
      onAssigned?.();
      onClose();
    } catch (error) {
      message.error(error.response?.data?.error || 'Erreur lors de l\'assignation');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <Modal
      title="Assigner à un utilisateur"
      open={open}
      onCancel={onClose}
      onOk={handleSubmit}
      okText="Assigner"
      confirmLoading={submitting}
      cancelText="Annuler"
    >
      <Select
        showSearch
        placeholder="Sélectionner un utilisateur"
        style={{ width: '100%', marginBottom: 16 }}
        loading={loading}
        value={selectedUserId}
        onChange={setSelectedUserId}
        optionFilterProp="label"
        options={users.map((user) => ({
          value: user.id,
          label: `${user.pseudo || user.username} (${user.email})`,
        }))}
      />

      {requiresStatus && (
        <Select
          placeholder="Nouveau statut requis"
          style={{ width: '100%' }}
          value={selectedStatus}
          onChange={setSelectedStatus}
          options={[
            { value: 'validated', label: 'Validé' },
            { value: 'draft', label: 'Brouillon' },
            { value: 'pending', label: 'En attente' },
            { value: 'editing', label: 'En édition' },
          ]}
        />
      )}
    </Modal>
  );
};

export default AssignUserModal;
