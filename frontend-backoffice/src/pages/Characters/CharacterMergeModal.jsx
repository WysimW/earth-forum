import React, { useEffect, useMemo, useState } from 'react';
import { Modal, Select, Radio, Space, Typography, message, Spin } from 'antd';
import api from '../../services/api';

const { Text, Paragraph } = Typography;

const CharacterMergeModal = ({ open, character, onClose, onMerged }) => {
  const [loading, setLoading] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [candidates, setCandidates] = useState([]);
  const [selectedCharacterId, setSelectedCharacterId] = useState(null);
  const [survivorChoice, setSurvivorChoice] = useState('current');

  useEffect(() => {
    if (!open || !character?.universe_id) {
      return;
    }

    const fetchCandidates = async () => {
      setLoading(true);
      try {
        const response = await api.get('/api/admin/characters', {
          params: {
            universe_id: character.universe_id,
            search: '',
          },
        });
        const filtered = (response.data || []).filter(
          (item) => item.id !== character.id
        );
        setCandidates(filtered);
      } catch (error) {
        console.error(error);
        message.error('Erreur lors du chargement des personnages');
      } finally {
        setLoading(false);
      }
    };

    fetchCandidates();
    setSelectedCharacterId(null);
    setSurvivorChoice('current');
  }, [open, character]);

  const selectedCharacter = useMemo(
    () => candidates.find((item) => item.id === selectedCharacterId) || null,
    [candidates, selectedCharacterId]
  );

  const survivor = survivorChoice === 'current' ? character : selectedCharacter;
  const absorbed = survivorChoice === 'current' ? selectedCharacter : character;

  const handleMerge = async () => {
    if (!selectedCharacter || !character) {
      message.warning('Sélectionnez un second personnage');
      return;
    }

    setSubmitting(true);
    try {
      const response = await api.post('/api/admin/characters/merge', {
        survivorId: survivor.id,
        absorbedId: absorbed.id,
      });
      message.success('Fusion effectuée avec succès');
      onMerged?.(response.data);
      onClose();
    } catch (error) {
      message.error(error.response?.data?.error || 'Erreur lors de la fusion');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <Modal
      title="Fusionner des personnages"
      open={open}
      onCancel={onClose}
      onOk={handleMerge}
      okText="Confirmer la fusion"
      okButtonProps={{ danger: true, disabled: !selectedCharacter, loading: submitting }}
      cancelText="Annuler"
      width={640}
    >
      {loading ? (
        <div style={{ textAlign: 'center', padding: 24 }}>
          <Spin />
        </div>
      ) : (
        <Space direction="vertical" size="large" style={{ width: '100%' }}>
          <Paragraph type="secondary">
            Les posts et threads seront réassignés au personnage conservé. Le personnage absorbé sera supprimé.
            Les deux personnages doivent être du même univers.
          </Paragraph>

          <div>
            <Text strong>Personnage à fusionner avec</Text>
            <Select
              showSearch
              placeholder="Rechercher un personnage du même univers"
              style={{ width: '100%', marginTop: 8 }}
              value={selectedCharacterId}
              onChange={setSelectedCharacterId}
              optionFilterProp="label"
              options={candidates.map((item) => ({
                value: item.id,
                label: `${item.name}${item.user_pseudo ? ` (${item.user_pseudo})` : ''}`,
              }))}
            />
          </div>

          {selectedCharacter && (
            <div>
              <Text strong>Personnage à conserver</Text>
              <Radio.Group
                style={{ display: 'block', marginTop: 8 }}
                value={survivorChoice}
                onChange={(event) => setSurvivorChoice(event.target.value)}
              >
                <Space direction="vertical">
                  <Radio value="current">
                    Conserver {character.name} (actuel) — absorber {selectedCharacter.name}
                  </Radio>
                  <Radio value="other">
                    Conserver {selectedCharacter.name} — absorber {character.name} (actuel)
                  </Radio>
                </Space>
              </Radio.Group>
            </div>
          )}

          {survivor && absorbed && (
            <Paragraph>
              <Text type="danger">
                Récapitulatif : « {survivor.name} » sera conservé, « {absorbed.name} » sera supprimé après réassignation de son historique.
              </Text>
            </Paragraph>
          )}
        </Space>
      )}
    </Modal>
  );
};

export default CharacterMergeModal;
