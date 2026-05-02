import React from 'react';
import ReactDOM from 'react-dom/client';
import { ConfigProvider } from 'antd';
import frFR from 'antd/locale/fr_FR';
import App from './App';
import './styles/index.css';

// Configuration du thème avec couleur primary dark pour le menu
const theme = {
  token: {
    colorPrimary: '#003366', // primary-dark
  },
  components: {
    Menu: {
      darkItemBg: '#003366',
      darkSubMenuItemBg: '#003366',
      darkItemSelectedBg: '#0066CC',
      darkItemHoverBg: 'rgba(0, 102, 204, 0.2)',
    },
    Layout: {
      siderBg: '#003366',
    },
  },
};

const root = ReactDOM.createRoot(document.getElementById('root'));
root.render(
  <React.StrictMode>
    <ConfigProvider locale={frFR} theme={theme}>
      <App />
    </ConfigProvider>
  </React.StrictMode>
);

