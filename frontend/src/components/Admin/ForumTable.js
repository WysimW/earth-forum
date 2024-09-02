import React, { useState, useEffect } from 'react';
import Modal from '../Modal/Modal';
import ForumForm from './EditForumForm';

const ForumTable = () => {
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [forums, setForums] = useState([]);
    const [editingForum, setEditingForum] = useState(null);

    useEffect(() => {
        fetch('http://localhost:8741/api/forumslist')
            .then(response => response.json())
            .then(data => setForums(data))
            .catch(error => console.error('Error fetching forums:', error));
    }, []);

    const handleEdit = (forum) => {
        setEditingForum(forum);
        setIsModalOpen(true);
    };

    const handleDelete = async (forumId) => {
        try {
            const response = await fetch(`http://localhost:8741/api/forums/${forumId}`, {
                method: 'DELETE',
            });

            if (response.ok) {
                setForums(forums.filter(forum => forum.id !== forumId));
                console.log('Forum deleted successfully');
            } else {
                console.error('Failed to delete forum');
            }
        } catch (error) {
            console.error('Error deleting forum:', error);
        }
    };

    const handleFormSuccess = () => {
        setEditingForum(null);
        setIsModalOpen(false);
        fetch('http://localhost:8741/api/forumslist')
            .then(response => response.json())
            .then(data => setForums(data))
            .catch(error => console.error('Error fetching forums:', error));
    };

    const handleSubmit = async (payload) => {
        const url = payload.id 
            ? `http://localhost:8741/api/forums/${payload.id}` 
            : 'http://localhost:8741/api/forums';
        const method = payload.id ? 'PUT' : 'POST';

        try {
            const response = await fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(payload),
            });

            if (!response.ok) {
                throw new Error('Failed to submit form');
            }

            handleFormSuccess(); // Call the success handler on success
        } catch (error) {
            console.error('Submission error:', error);
        }
    };

    const handleCloseModal = () => {
        setIsModalOpen(false);
        setEditingForum(null);
    };

    return (
        <div>
            <table className="forum-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Banner URL</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    {forums.map(forum => (
                        <tr key={forum.id}>
                            <td>{forum.id}</td>
                            <td>{forum.name}</td>
                            <td>{forum.description}</td>
                            <td>{forum.banner}</td>
                            <td>
                                <button onClick={() => handleEdit(forum)}>Edit</button>
                                <button onClick={() => handleDelete(forum.id)}>Delete</button>
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>

            {isModalOpen && (
                <Modal isOpen={isModalOpen} onClose={handleCloseModal}>
                    {editingForum && (
                        <ForumForm
                            forum={editingForum}
                            onSubmit={handleSubmit}
                        />
                    )}
                </Modal>
            )}
        </div>
    );
};

export default ForumTable;
