import React from 'react';
import { Breadcrumb } from 'antd';
import { HomeOutlined } from '@ant-design/icons';
import { Link } from 'react-router-dom';

const ListPageBreadcrumbs = ({ items = [] }) => {
  const breadcrumbItems = [
    {
      title: (
        <Link to="/">
          <HomeOutlined /> Accueil
        </Link>
      ),
    },
    ...items.map((item) => ({
      title: item.link ? <Link to={item.link}>{item.label}</Link> : item.label,
    })),
  ];

  return <Breadcrumb items={breadcrumbItems} style={{ marginBottom: 16 }} />;
};

export default ListPageBreadcrumbs;






