import React from 'react';
import { Card, Button, Space } from 'antd';
import { PlusOutlined } from '@ant-design/icons';

const ActionsCard = ({ title, actions = [], extraContent }) => {
  if (actions.length === 0 && !extraContent) {
    return null;
  }

  return (
    <Card style={{ marginBottom: 16 }}>
      <Space style={{ width: '100%', justifyContent: 'space-between' }}>
        <Space>
          {actions.map((action, index) => (
            <Button
              key={index}
              type={action.type || 'primary'}
              icon={action.icon || <PlusOutlined />}
              onClick={action.onClick}
              disabled={action.disabled}
            >
              {action.label}
            </Button>
          ))}
        </Space>
        {extraContent && <Space>{extraContent}</Space>}
      </Space>
    </Card>
  );
};

export default ActionsCard;

