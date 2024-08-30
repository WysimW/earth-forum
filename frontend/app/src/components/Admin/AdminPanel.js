import React, { useState } from 'react';
import ForumTable from './ForumTable';
import CreateForumForm from './CreateForumForm';
import EditForumForm from './EditForumForm';
import './AdminPanel.css';

const AdminPanel = () => {
    const [activeTab, setActiveTab] = useState('index');
    const [selectedForum, setSelectedForum] = useState(null);

    const handleCreate = async (payload) => {
        const response = await fetch('http://localhost:8741/api/forums', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        });
        return response;
    };

    const handleEdit = async (payload) => {
        console.log('Editing Forum:', payload);  // Should log the payload
    
        const url = `http://localhost:8741/api/forums/${payload.id}`;
        try {
            const response = await fetch(url, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
            });
    
            console.log('Response:', response);  // Should log the response object
    
            if (!response.ok) {
                throw new Error('Failed to update forum');
            }
    
            return response;
        } catch (error) {
            console.error('Edit failed:', error);
            throw error;
        }
    };
    
    return (
        <div className="admin-panel">
            <div className="sidebar">
                <ul className="nav">
                    <li className={activeTab === 'index' ? 'active' : ''} onClick={() => setActiveTab('index')}>
                        Index
                    </li>
                    <li className={activeTab === 'forums' ? 'active' : ''} onClick={() => setActiveTab('forums')}>
                        Forums
                    </li>
                </ul>
            </div>

            <div className="main-content">
                {activeTab === 'index' && (
                    <div className="admindashboard">
                        <h2>Admin Dashboard</h2>
                        <p>Welcome to the admin panel. Use the tabs to manage the forum.</p>
                    </div>
                )}

                {activeTab === 'forums' && (
                    <div>
                        <h2>Manage Forums</h2>
                        <ForumTable onEdit={(forum) => { setSelectedForum(forum); setActiveTab('edit-forum'); }} />
                        <button onClick={() => setActiveTab('add-forum')} className="add-button">Add Forum</button>
                    </div>
                )}

                {activeTab === 'add-forum' && (
                    <div>
                        <h2>Add Forum</h2>
                        <CreateForumForm onSubmit={handleCreate} />
                    </div>
                )}

                {activeTab === 'edit-forum' && (
                    <div>
                        <h2>Edit Forum</h2>
                        <EditForumForm forum={selectedForum} onSubmit={handleEdit} />
                    </div>
                )}
            </div>
        </div>
    );
};

export default AdminPanel;
