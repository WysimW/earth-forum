import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { EditOutlined, PlusOutlined } from '@ant-design/icons';
import { Alert, Button, Card, Collapse, Form, Input, Select, Space, Table, message } from 'antd';
import { useUniverse } from '../../contexts/UniverseContext';
import importantAdminService from '../../services/importantAdminService';
import styles from './ImportantMemberOfMonth.module.css';

const ImportantMemberOfMonth = () => {
  const { universes, selectedUniverseId, setSelectedUniverseId } = useUniverse();
  const [scope, setScope] = useState('year');
  const [stats, setStats] = useState([]);
  const [history, setHistory] = useState([]);
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [selectedUserId, setSelectedUserId] = useState(null);
  const [highlightText, setHighlightText] = useState('');
  const [selectedHistoryEntryId, setSelectedHistoryEntryId] = useState(null);

  const [form] = Form.useForm();
  const canLoad = useMemo(() => Boolean(selectedUniverseId), [selectedUniverseId]);
  const selectedHistoryEntry = useMemo(
    () => history.find((item) => item.id === selectedHistoryEntryId) || null,
    [history, selectedHistoryEntryId]
  );
  const memberOptions = useMemo(() => {
    const map = new Map();

    stats.forEach((item) => {
      map.set(item.id, {
        value: item.id,
        label: `${item.pseudo} (${item.postsCount} messages)`,
      });
    });

    history.forEach((item) => {
      if (item.user?.id && !map.has(item.user.id)) {
        map.set(item.user.id, {
          value: item.user.id,
          label: item.user.pseudo || `Utilisateur #${item.user.id}`,
        });
      }
    });

    return Array.from(map.values());
  }, [history, stats]);

  const loadEntryInForm = useCallback((entry) => {
    if (!entry) {
      return;
    }
    form.setFieldsValue({
      user_id: entry.user?.id,
      month: entry.month,
      year: entry.year,
    });
    setSelectedUserId(entry.user?.id ?? null);
    setHighlightText(entry.highlightText || '');
    setSelectedHistoryEntryId(entry.id);
  }, [form]);

  const loadData = useCallback(async () => {
    if (!canLoad) return;
    try {
      setLoading(true);
      const [statsResponse, historyResponse] = await Promise.all([
        importantAdminService.getMemberStats(selectedUniverseId, scope),
        importantAdminService.getMemberHistory(selectedUniverseId),
      ]);

      const statsItems = Array.isArray(statsResponse?.items) ? statsResponse.items : [];
      const historyItems = Array.isArray(historyResponse?.items) ? historyResponse.items : [];
      setStats(statsItems);
      setHistory(historyItems);
      if (statsItems.length > 0) {
        setSelectedUserId((prev) => (
          statsItems.some((item) => item.id === prev) ? prev : statsItems[0].id
        ));
      } else {
        setSelectedUserId(null);
        form.setFieldValue('user_id', undefined);
      }

      setSelectedHistoryEntryId((prev) => (
        historyItems.some((item) => item.id === prev) ? prev : (historyItems[0]?.id || null)
      ));
    } catch (err) {
      message.error('Impossible de charger les données.');
    } finally {
      setLoading(false);
    }
  }, [canLoad, form, scope, selectedUniverseId]);

  useEffect(() => {
    loadData();
  }, [loadData]);

  useEffect(() => {
    if (selectedUserId) {
      form.setFieldValue('user_id', selectedUserId);
    }
  }, [form, selectedUserId]);

  useEffect(() => {
    if (selectedHistoryEntry) {
      loadEntryInForm(selectedHistoryEntry);
    }
  }, [loadEntryInForm, selectedHistoryEntry]);

  const onSubmit = async (values) => {
    if (!canLoad) {
      return;
    }
    try {
      setSaving(true);
      await importantAdminService.selectMember({
        universe_id: Number(selectedUniverseId),
        user_id: Number(values.user_id),
        year: Number(values.year),
        month: Number(values.month),
        highlight_text: highlightText || null,
      });
      message.success('Membre du mois enregistré.');
      await loadData();
    } catch (err) {
      message.error('Erreur lors de la sélection.');
    } finally {
      setSaving(false);
    }
  };

  const statsColumns = [
    { title: 'Pseudo', dataIndex: 'pseudo', key: 'pseudo' },
    { title: 'Messages', dataIndex: 'postsCount', key: 'postsCount' },
  ];

  const historyColumns = [
    { title: 'Date', key: 'period', render: (_, row) => `${row.month}/${row.year}` },
    { title: 'Membre', key: 'member', render: (_, row) => row.user?.pseudo || '-' },
    { title: 'Mise en avant', dataIndex: 'highlightText', key: 'highlightText' },
    {
      title: 'Action',
      key: 'action',
      render: (_, row) => (
        <Button type="primary" ghost icon={<EditOutlined />} className={styles.actionButton} onClick={() => loadEntryInForm(row)}>
          Charger
        </Button>
      ),
    },
  ];

  return (
    <Space direction="vertical" size={16} style={{ width: '100%' }}>
      <Alert
        type={canLoad ? 'info' : 'warning'}
        message={canLoad ? 'Univers sélectionné' : 'Sélectionne un univers pour gérer ce module.'}
        description={
          <Select
            value={selectedUniverseId}
            onChange={setSelectedUniverseId}
            placeholder="Choisir un univers"
            style={{ minWidth: 260, marginTop: 8 }}
            options={universes.map((universe) => ({
              value: String(universe.id),
              label: universe.name,
            }))}
          />
        }
        showIcon
      />
      <Card title="Historique (à visualiser avant ajout/modification)">
        <Space direction="vertical" size={12} style={{ width: '100%' }}>
          {canLoad ? (
            <Table
              className={styles.customTable}
              rowKey="id"
              loading={loading}
              columns={historyColumns}
              dataSource={history}
              onRow={(record) => ({
                onClick: () => loadEntryInForm(record),
              })}
              rowClassName={(record) => (record.id === selectedHistoryEntryId ? 'ant-table-row-selected' : '')}
              locale={{
                emptyText: 'Aucune entrée membre du mois pour cet univers.',
              }}
            />
          ) : (
            <Alert
              type="info"
              showIcon
              message="Sélectionne d'abord un univers pour charger l'historique."
            />
          )}

          {selectedHistoryEntry ? (
            <Card type="inner" title="Visualisation de l'entrée sélectionnée">
              <p><strong>Membre :</strong> {selectedHistoryEntry.user?.pseudo || '-'}</p>
              <p><strong>Période :</strong> {selectedHistoryEntry.month}/{selectedHistoryEntry.year}</p>
              <p><strong>Mise en avant :</strong> {selectedHistoryEntry.highlightText || 'Aucune'}</p>
            </Card>
          ) : (
            <Alert type="info" showIcon message="Aucune entrée existante. Tu peux en ajouter une nouvelle." />
          )}

          <Button
            icon={<PlusOutlined />}
            onClick={() => {
              setSelectedHistoryEntryId(null);
              setHighlightText('');
              form.setFieldsValue({
                month: new Date().getMonth() + 1,
                year: new Date().getFullYear(),
                user_id: selectedUserId || undefined,
              });
            }}
            disabled={!canLoad}
          >
            Ajouter une nouvelle entrée
          </Button>
        </Space>
      </Card>

      <Card title="Statistiques d'activité">
        <Collapse
          className={styles.statsAccordion}
          defaultActiveKey={['stats']}
          items={[
            {
              key: 'stats',
              label: 'Afficher les statistiques d’activité',
              children: (
                <Space direction="vertical" size={12} style={{ width: '100%' }}>
                  <Select
                    value={scope}
                    onChange={setScope}
                    options={[
                      { label: 'Mois en cours', value: 'month' },
                      { label: 'Année en cours', value: 'year' },
                      { label: 'Total', value: 'total' },
                    ]}
                    style={{ width: 220 }}
                  />
                  <Table
                    className={styles.customTable}
                    rowKey="id"
                    loading={loading}
                    columns={statsColumns}
                    dataSource={stats}
                    pagination={false}
                    locale={{
                      emptyText: canLoad
                        ? 'Aucune activité sur cette période. Essaie Année en cours ou Total.'
                        : 'Choisis un univers pour charger les statistiques.',
                    }}
                  />
                </Space>
              ),
            },
          ]}
        />
      </Card>

      <Card title={selectedHistoryEntry ? 'Modifier le membre du mois' : 'Ajouter un membre du mois'}>
        <Form
          form={form}
          layout="vertical"
          onFinish={onSubmit}
          initialValues={{ month: new Date().getMonth() + 1, year: new Date().getFullYear(), user_id: selectedUserId }}
        >
          <Form.Item label="Membre" name="user_id" rules={[{ required: true }]}>
            <Select options={memberOptions} />
          </Form.Item>
          <Space>
            <Form.Item label="Mois" name="month" rules={[{ required: true }]}>
              <Input type="number" min={1} max={12} />
            </Form.Item>
            <Form.Item label="Année" name="year" rules={[{ required: true }]}>
              <Input type="number" min={2000} />
            </Form.Item>
          </Space>
          <Form.Item label="Texte de mise en avant (optionnel)">
            <Input.TextArea value={highlightText} onChange={(event) => setHighlightText(event.target.value)} rows={3} />
          </Form.Item>
          <Button type="primary" htmlType="submit" loading={saving} disabled={!canLoad}>
            Enregistrer la sélection
          </Button>
        </Form>
      </Card>
    </Space>
  );
};

export default ImportantMemberOfMonth;

