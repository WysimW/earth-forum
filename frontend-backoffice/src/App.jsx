import React from 'react';
import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import MainLayout from './components/Layout/MainLayout';
import Login from './pages/Login/Login';
import Dashboard from './pages/Dashboard/Dashboard';
import Users from './pages/Users/Users';
import UserForm from './pages/Users/UserForm';
import UserDetail from './pages/Users/UserDetail';
import Forums from './pages/Forums/Forums';
import ForumForm from './pages/Forums/ForumForm';
import Characters from './pages/Characters/Characters';
import CharacterForm from './pages/Characters/CharacterForm';
import CharacterDetail from './pages/Characters/CharacterDetail';
import Factions from './pages/Factions/Factions';
import FactionForm from './pages/Factions/FactionForm';
import ImportantRegulation from './pages/Important/ImportantRegulation';
import ImportantGuide from './pages/Important/ImportantGuide';
import ImportantVote from './pages/Important/ImportantVote';
import ImportantMemberOfMonth from './pages/Important/ImportantMemberOfMonth';
import ImportantCharacterOfMonth from './pages/Important/ImportantCharacterOfMonth';
import { AuthProvider, useAuth } from './contexts/AuthContext';
import { UniverseProvider } from './contexts/UniverseContext';

const ProtectedRoute = ({ children }) => {
  const { isAuthenticated, loading } = useAuth();

  if (loading) {
    return <div>Chargement...</div>;
  }

  if (!isAuthenticated) {
    return <Navigate to="/login" replace />;
  }

  return children;
};

const AppRoutes = () => {
  return (
    <Routes>
      <Route path="/login" element={<Login />} />
      <Route
        path="/*"
        element={
          <ProtectedRoute>
            <MainLayout>
              <Routes>
                <Route path="/" element={<Dashboard />} />
                <Route path="/users" element={<Users />} />
                <Route path="/users/new" element={<UserForm />} />
                <Route path="/users/edit/:id" element={<UserForm />} />
                <Route path="/users/:id" element={<UserDetail />} />
                <Route path="/forums" element={<Forums />} />
                <Route path="/forums/new" element={<ForumForm />} />
                <Route path="/forums/edit/:id" element={<ForumForm />} />
                <Route path="/characters" element={<Characters />} />
                <Route path="/characters/new" element={<CharacterForm />} />
                <Route path="/characters/edit/:id" element={<CharacterForm />} />
                <Route path="/characters/detail/:id" element={<CharacterDetail />} />
                <Route path="/factions" element={<Factions />} />
                <Route path="/factions/new" element={<FactionForm />} />
                <Route path="/factions/edit/:id" element={<FactionForm />} />
                <Route path="/important/regulation" element={<ImportantRegulation />} />
                <Route path="/important/guide" element={<ImportantGuide />} />
                <Route path="/important/vote" element={<ImportantVote />} />
                <Route path="/important/member-of-month" element={<ImportantMemberOfMonth />} />
                <Route path="/important/character-of-month" element={<ImportantCharacterOfMonth />} />
              </Routes>
            </MainLayout>
          </ProtectedRoute>
        }
      />
    </Routes>
  );
};

function App() {
  return (
    <Router>
      <AuthProvider>
        <UniverseProvider>
          <AppRoutes />
        </UniverseProvider>
      </AuthProvider>
    </Router>
  );
}

export default App;

