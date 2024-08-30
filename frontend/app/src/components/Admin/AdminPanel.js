import React from 'react';
import ForumForm from './ForumForm';

const AdminPanel = () => {
    const createForum = (forumData) => {
        return fetch('http://localhost:8741/api/forums', { // Use the correct backend port
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(forumData),
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        });
    };

    return (
        <div>
            <h1>Admin Panel</h1>
            <h2>Create a New Forum</h2>
            <ForumForm onSubmit={createForum} />
        </div>
    );
};

export default AdminPanel;
