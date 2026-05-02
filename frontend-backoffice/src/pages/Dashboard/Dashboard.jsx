import React from 'react';
import { Row, Col, Card, Statistic } from 'antd';
import { UserOutlined, MessageOutlined, FileTextOutlined } from '@ant-design/icons';

const Dashboard = () => {
  return (
    <div>
      <h1>Tableau de bord</h1>
      <Row gutter={16} style={{ marginTop: 24 }}>
        <Col span={8}>
          <Card>
            <Statistic
              title="Utilisateurs"
              value={1128}
              prefix={<UserOutlined />}
            />
          </Card>
        </Col>
        <Col span={8}>
          <Card>
            <Statistic
              title="Forums"
              value={26}
              prefix={<MessageOutlined />}
            />
          </Card>
        </Col>
        <Col span={8}>
          <Card>
            <Statistic
              title="Posts"
              value={8934}
              prefix={<FileTextOutlined />}
            />
          </Card>
        </Col>
      </Row>
    </div>
  );
};

export default Dashboard;

