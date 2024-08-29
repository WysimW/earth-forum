import React, { useState, useEffect } from 'react';
import { BrowserRouter as Router, Route, Routes } from 'react-router-dom';
import Header from './components/Header/Header';
import Footer from './components/Footer/Footer';
import ForumPage from './components/Forum/ForumPage';
import ForumDetail from './components/Forum/ForumDetail';
import SubForumDetail from './components/Forum/SubForumDetail';
import ThreadDetail from './components/Thread/ThreadDetail';

function App() {
    const [categories, setCategories] = useState([]);

    useEffect(() => {
        fetch('/data/forumData.json')
            .then(response => response.json())
            .then(data => setCategories(data.categories))
            .catch(error => console.error('Error fetching forum data:', error));
    }, []);

    const allForums = categories.flatMap(category => category.forums);
    const allSubForums = allForums.flatMap(forum => forum.subForums);
    const allThreads = allForums.flatMap(forum => [
        ...forum.threads || [],
        ...forum.subForums.flatMap(subForum => subForum.threads || [])
    ]);

    return (
        <Router>
            <Header />
            <main style={{ padding: '20px' }}>
                <Routes>
                    <Route path="/" element={<ForumPage categories={categories} />} />
                    <Route path="/forum/:id" element={<ForumDetail forums={allForums} />} />
                    <Route path="/subforum/:id" element={<SubForumDetail subForums={allSubForums} />} />
                    <Route path="/thread/:id" element={<ThreadDetail threads={allThreads} />} />
                </Routes>
            </main>
            <Footer />
        </Router>
    );
}

export default App;
