import React, { useState, useEffect } from 'react';
import './Reset.css';  // Import the reset CSS first
import './App.css';  // Import the reset CSS first
import { BrowserRouter as Router, Route, Routes } from 'react-router-dom';
import Header from './components/Header/Header';
import Hero from './components/Header/HeroSection';
import Footer from './components/Footer/Footer';
import ForumPage from './components/Forum/ForumPage';
import ForumDetail from './components/Forum/ForumDetail';
import ThreadDetail from './components/Thread/ThreadDetail';
import AdminPanel from './components/Admin/AdminPanel'; // Import AdminPanel component
import ThreadCreate from './components/Thread/ThreadCreate'; // Import the ThreadCreate component
import HeroSection from './components/Header/HeroSection';

const createThread = (threadData) => {
    const payload = {
        ...threadData,
        author_id: 1,  // Assuming user with ID 1 is the logged-in user
    };

    return fetch('http://localhost:8741/api/threads', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(payload),
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Failed to create thread');
        }
        return response.json();
    });
};

function App() {
    const [categories, setCategories] = useState([]);

    useEffect(() => {
        fetch('http://localhost:8741/api/categories') // Update this to your backend endpoint
            .then(response => response.json())
            .then(data => setCategories(data.categories))
            .catch(error => console.error('Error fetching forum data:', error));
    }, []);
    console.log(categories)
    return (
        <Router>
            <Header />
            <HeroSection />
            <main style={{ padding: '40px' }}>
                <Routes>
                    <Route path="/" element={<ForumPage categories={categories} />} />
                    <Route path="/forum/:id" element={<ForumDetail />} />
                    <Route path="/thread/:id" element={<ThreadDetail />} />
                    <Route path="/forum/:id/create-thread" element={<ThreadCreate onSubmit={createThread} />} />
                    <Route path="/admin" element={<AdminPanel />} /> {/* Route for Admin Panel */}
                </Routes>
            </main>
            <Footer />
        </Router>
    );
}

export default App;