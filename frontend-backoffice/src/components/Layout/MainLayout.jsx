import React, { useState } from 'react';
import { Layout, Menu, Avatar, Dropdown, Space, Select, Tag } from 'antd';
import {
  MenuFoldOutlined,
  MenuUnfoldOutlined,
  DashboardOutlined,
  UserOutlined,
  MessageOutlined,
  LogoutOutlined,
  TeamOutlined,
  GlobalOutlined,
  CrownOutlined,
  FileTextOutlined,
  StarOutlined,
  TrophyOutlined,
  HighlightOutlined,
  SearchOutlined,
  HomeOutlined,
  InfoCircleOutlined,
  FolderOutlined,
  AppstoreOutlined,
} from '@ant-design/icons';
import { useNavigate, useLocation } from 'react-router-dom';
import { useAuth } from '../../contexts/AuthContext';
import { useUniverse } from '../../contexts/UniverseContext';
import './MainLayout.css';

const { Header, Sider, Content } = Layout;

const MainLayout = ({ children }) => {
  const [collapsed, setCollapsed] = useState(false);
  const navigate = useNavigate();
  const location = useLocation();
  const { user, logout } = useAuth();
  const { universes, selectedUniverseId, setSelectedUniverseId, isSuperAdmin } = useUniverse();
  const ALL_UNIVERSES_VALUE = '__all__';

  const handleUniverseContextChange = (value) => {
    if (!value || value === ALL_UNIVERSES_VALUE) {
      setSelectedUniverseId(null);
      return;
    }
    setSelectedUniverseId(value);
  };

  const menuItems = [
    {
      key: '/',
      icon: <DashboardOutlined />,
      label: 'Tableau de bord',
    },
    {
      key: '/users',
      icon: <UserOutlined />,
      label: 'Utilisateurs',
    },
    {
      key: '/forums',
      icon: <MessageOutlined />,
      label: 'Forums',
    },
    {
      key: '/forum-categories',
      icon: <FolderOutlined />,
      label: 'Catégories forums',
    },
    {
      key: '/characters',
      icon: <TeamOutlined />,
      label: 'Personnages',
    },
    {
      key: '/factions',
      icon: <CrownOutlined />,
      label: 'Factions',
    },
    {
      key: '/univers',
      icon: <AppstoreOutlined />,
      label: 'Univers',
    },
    {
      key: '/important',
      icon: <StarOutlined />,
      label: 'Important',
      children: [
        {
          key: '/important/portal-seo',
          icon: <HomeOutlined />,
          label: 'Page d’accueil (SEO)',
        },
        {
          key: '/important/about',
          icon: <InfoCircleOutlined />,
          label: 'Qui sommes-nous',
        },
        {
          key: '/important/universe-seo',
          icon: <SearchOutlined />,
          label: 'SEO Univers',
        },
        {
          key: '/important/regulation',
          icon: <FileTextOutlined />,
          label: 'Règlement',
        },
        {
          key: '/important/guide',
          icon: <FileTextOutlined />,
          label: 'Mode d’emploi',
        },
        {
          key: '/important/vote',
          icon: <HighlightOutlined />,
          label: 'Votez pour nous',
        },
        {
          key: '/important/member-of-month',
          icon: <TrophyOutlined />,
          label: 'Membre du mois',
        },
        {
          key: '/important/character-of-month',
          icon: <TrophyOutlined />,
          label: 'Personnage du mois',
        },
      ],
    },
  ];

  const userMenuItems = [
    {
      key: 'logout',
      icon: <LogoutOutlined />,
      label: 'Déconnexion',
      onClick: () => {
        logout();
        navigate('/login');
      },
    },
  ];

  return (
    <Layout style={{ minHeight: '100vh' }}>
      <Sider 
        trigger={null} 
        collapsible 
        collapsed={collapsed} 
        theme="dark" 
        style={{ 
          padding: '16px 8px',
          position: 'fixed',
          left: 0,
          top: 0,
          bottom: 0,
          overflow: 'auto',
          height: '100vh',
          display: 'flex',
          flexDirection: 'column',
        }}
      >
        <div className="logo">
          {!collapsed && <span>Earth Forum</span>}
          {collapsed && <span>EF</span>}
        </div>
        <Menu
          theme="dark"
          mode="inline"
          selectedKeys={[location.pathname]}
          items={menuItems}
          onClick={({ key }) => navigate(key)}
          style={{ padding: '8px 0', flex: 1 }}
        />
        <div style={{ 
          padding: '16px 8px', 
          borderTop: '1px solid rgba(255, 255, 255, 0.1)',
          marginTop: 'auto',
        }}>
          <a
            href={process.env.REACT_APP_FRONTEND_URL || 'http://localhost:3003'}
            target="_blank"
            rel="noopener noreferrer"
            style={{
              display: 'flex',
              alignItems: 'center',
              color: 'rgba(255, 255, 255, 0.85)',
              textDecoration: 'none',
              padding: '8px',
              borderRadius: '4px',
              transition: 'all 0.3s',
            }}
            onMouseEnter={(e) => {
              e.currentTarget.style.background = 'rgba(255, 255, 255, 0.1)';
              e.currentTarget.style.color = '#fff';
            }}
            onMouseLeave={(e) => {
              e.currentTarget.style.background = 'transparent';
              e.currentTarget.style.color = 'rgba(255, 255, 255, 0.85)';
            }}
          >
            <GlobalOutlined style={{ fontSize: '16px', marginRight: collapsed ? 0 : '8px' }} />
            {!collapsed && <span>Voir le site</span>}
          </a>
        </div>
      </Sider>
      <Layout style={{ marginLeft: collapsed ? 80 : 200, transition: 'margin-left 0.2s' }}>
        <Header style={{ 
          padding: 0, 
          background: '#fff', 
          display: 'flex', 
          alignItems: 'center', 
          justifyContent: 'space-between', 
          paddingRight: '24px',
          position: 'sticky',
          top: 0,
          zIndex: 1000,
          boxShadow: '0 2px 8px rgba(0,0,0,0.1)',
        }}>
          <div>
            {React.createElement(collapsed ? MenuUnfoldOutlined : MenuFoldOutlined, {
              className: 'trigger',
              onClick: () => setCollapsed(!collapsed),
              style: { fontSize: '18px', padding: '0 24px', cursor: 'pointer' },
            })}
          </div>
          <Space size="middle">
            <Space>
              <span style={{ color: '#666', fontSize: 13 }}>Contexte univers :</span>
              <Select
                value={selectedUniverseId ?? ALL_UNIVERSES_VALUE}
                onChange={handleUniverseContextChange}
                placeholder={isSuperAdmin ? 'Tous les univers' : 'Tous mes univers'}
                style={{ width: 260 }}
                options={[
                  {
                    value: ALL_UNIVERSES_VALUE,
                    label: isSuperAdmin ? 'Tous les univers' : 'Tous mes univers',
                  },
                  ...universes.map((universe) => ({
                    value: String(universe.id),
                    label: universe.name,
                  })),
                ]}
              />
            </Space>
            <Dropdown menu={{ items: userMenuItems }} placement="bottomRight">
              <Space style={{ cursor: 'pointer' }}>
                <Avatar icon={<UserOutlined />} />
                <span>{user?.pseudo || 'Utilisateur'}</span>
                {isSuperAdmin && <Tag color="gold">SUPER ADMIN</Tag>}
              </Space>
            </Dropdown>
          </Space>
        </Header>
        <Content
          style={{
            margin: '24px 16px',
            padding: 24,
            minHeight: 280,
            background: '#fff',
          }}
        >
          {children}
        </Content>
      </Layout>
    </Layout>
  );
};

export default MainLayout;

