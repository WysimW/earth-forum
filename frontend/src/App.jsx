import React from 'react';
import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import { AuthProvider, useAuth } from './contexts/AuthContext';
import { UniverseThemeProvider } from './contexts/UniverseThemeContext';
import Login from './pages/Login/Login';
import Register from './pages/Register/Register';
import Home from './pages/Home/Home';
import About from './pages/About/About';
import ForumDetail from './pages/ForumDetail/ForumDetail';
import ThreadDetail from './pages/ThreadDetail/ThreadDetail';
import TestComponent from './pages/TestComponent/TestComponent';
import Characters from './pages/Characters/Characters';
import CharacterForm from './pages/Characters/CharacterForm';
import NpcForm from './pages/Characters/NpcForm';
import Factions from './pages/Factions/Factions';
import FactionForm from './pages/Factions/FactionForm';
import FactionDetail from './pages/Factions/FactionDetail';
import Regulation from './pages/Regulation/Regulation';
import Guide from './pages/Guide/Guide';
import MemberOfMonth from './pages/MemberOfMonth/MemberOfMonth';
import CharacterOfMonth from './pages/CharacterOfMonth/CharacterOfMonth';
import RpActivities from './pages/RpActivities/RpActivities';
import RpActivityDetail from './pages/RpActivityDetail/RpActivityDetail';
import RpActivityCreate from './pages/RpActivityCreate/RpActivityCreate';
import RpActivityEdit from './pages/RpActivityEdit/RpActivityEdit';
import RpActivityThreadCreate from './pages/RpActivityThreadCreate/RpActivityThreadCreate';
import Profile from './pages/Profile/Profile';
import Dashboard from './pages/Dashboard/Dashboard';
import MessagingList from './pages/Messaging/MessagingList';
import MessagingModeration from './pages/Messaging/MessagingModeration';
import MessagingNew from './pages/Messaging/MessagingNew';
import MessagingThread from './pages/Messaging/MessagingThread';
import Cards from './pages/Components/Cards/Cards';
import Sidebar from './pages/Components/Sidebar/Sidebar';
import ThreadHeader from './pages/Components/ThreadHeader/ThreadHeader';
import CharacterHeader from './pages/Components/CharacterHeader/CharacterHeader';
import ThreadList from './pages/Components/ThreadList/ThreadList';
import ForumDetailTest from './pages/Components/ForumDetail/ForumDetail';
import ForumDetailHeader from './pages/Components/ForumDetailHeader/ForumDetailHeader';
import ThreadFilter from './pages/Components/ThreadFilter/ThreadFilter';
import ThreadPostHRP from './pages/Components/ThreadPostHRP/ThreadPostHRP';
import ThreadPostRP from './pages/Components/ThreadPostRP/ThreadPostRP';
import RpActivityCardComponent from './pages/Components/RpActivityCard/RpActivityCard';
import Components from './pages/Components/Components';
import Loading from './components/Loading/Loading';
import './styles/global.css';

const PrivateRoute = ({ children }) => {
  const { isAuthenticated, loading } = useAuth();

  if (loading) {
    return <Loading message="Vérification de l'authentification..." />;
  }

  return isAuthenticated ? children : <Navigate to="/login" />;
};

const AppRoutes = () => {
  return (
        <Routes>
          <Route path="/login" element={<Login />} />
          <Route path="/register" element={<Register />} />
            <Route path="/" element={<Home />} />
            <Route path="/qui-sommes-nous" element={<About />} />
            <Route path="/reglement" element={<Regulation />} />
            <Route path="/mode-emploi" element={<Guide />} />
            <Route path="/member-of-month" element={<MemberOfMonth />} />
            <Route path="/member-of-month/:universeSlug" element={<MemberOfMonth />} />
            <Route path="/character-of-month" element={<CharacterOfMonth />} />
            <Route path="/character-of-month/:universeSlug" element={<CharacterOfMonth />} />
            <Route path="/rp-activities" element={<RpActivities />} />
            <Route path="/rp-activities/new" element={<PrivateRoute><RpActivityCreate /></PrivateRoute>} />
            <Route path="/rp-activities/:id" element={<RpActivityDetail />} />
            <Route path="/rp-activities/:id/edit" element={<PrivateRoute><RpActivityEdit /></PrivateRoute>} />
            <Route path="/rp-activities/:id/create-thread" element={<PrivateRoute><RpActivityThreadCreate /></PrivateRoute>} />
          <Route path="/tableau-de-bord" element={<PrivateRoute><Dashboard /></PrivateRoute>} />
          <Route path="/messagerie" element={<PrivateRoute><MessagingList /></PrivateRoute>} />
          <Route path="/messagerie/nouveau" element={<PrivateRoute><MessagingNew /></PrivateRoute>} />
          <Route path="/messagerie/moderation" element={<PrivateRoute><MessagingModeration /></PrivateRoute>} />
          <Route path="/messagerie/:id" element={<PrivateRoute><MessagingThread /></PrivateRoute>} />
          <Route path="/profil" element={<PrivateRoute><Profile /></PrivateRoute>} />
            <Route path="/test" element={<TestComponent />} />
            <Route path="/univers/:slug" element={<ForumDetail />} />
            <Route path="/forums" element={<ForumDetail />} />
            <Route path="/forums/:slug" element={<ForumDetail />} />
            <Route path="/threads/:slug" element={<ThreadDetail />} />
            <Route path="/characters" element={<PrivateRoute><Characters /></PrivateRoute>} />
            <Route path="/characters/new" element={<PrivateRoute><CharacterForm /></PrivateRoute>} />
            <Route path="/characters/:id/edit" element={<PrivateRoute><CharacterForm /></PrivateRoute>} />
            <Route path="/characters/npc/new" element={<PrivateRoute><NpcForm /></PrivateRoute>} />
            <Route path="/characters/npc/:id/edit" element={<PrivateRoute><NpcForm /></PrivateRoute>} />
            <Route path="/mes-factions" element={<PrivateRoute><Factions mode="mine" /></PrivateRoute>} />
            <Route path="/factions" element={<PrivateRoute><Factions mode="browse" /></PrivateRoute>} />
            <Route path="/factions/new" element={<PrivateRoute><FactionForm /></PrivateRoute>} />
            <Route path="/factions/:id" element={<PrivateRoute><FactionDetail /></PrivateRoute>} />
            <Route path="/factions/:id/edit" element={<PrivateRoute><FactionForm /></PrivateRoute>} />
            <Route path="/components" element={<Components />} />
            <Route path="/components/cards" element={<Cards />} />
            <Route path="/components/sidebar" element={<Sidebar />} />
            <Route path="/components/threadheader" element={<ThreadHeader />} />
            <Route path="/components/characterheader" element={<CharacterHeader />} />
            <Route path="/components/threadlist" element={<ThreadList />} />
            <Route path="/components/forumdetail" element={<ForumDetailTest />} />
            <Route path="/components/forumdetailheader" element={<ForumDetailHeader />} />
            <Route path="/components/threadfilter" element={<ThreadFilter />} />
            <Route path="/components/threadposthrp" element={<ThreadPostHRP />} />
            <Route path="/components/threadpostrp" element={<ThreadPostRP />} />
            <Route path="/components/rpactivitycard" element={<RpActivityCardComponent />} />
            <Route path="*" element={<Navigate to="/" replace />} />
        </Routes>
  );
};

function App() {
  return (
    <UniverseThemeProvider>
      <AuthProvider>
        <Router future={{ v7_startTransition: true, v7_relativeSplatPath: true }}>
          <AppRoutes />
        </Router>
      </AuthProvider>
    </UniverseThemeProvider>
  );
}

export default App;

