import React, { useState, useEffect } from 'react';
import './AdminPanel.css';

const CreateForumForm = ({ onSubmit }) => {
    const [name, setName] = useState('');
    const [description, setDescription] = useState('');
    const [categories, setCategories] = useState([]);
    const [forums, setForums] = useState([]);
    const [isCategory, setIsCategory] = useState(true);  // State to toggle between category and forum
    const [selectedOption, setSelectedOption] = useState('');
    const [banner, setBanner] = useState('');
    const [error, setError] = useState(null);
    const [success, setSuccess] = useState(null);

    useEffect(() => {
        fetch('http://localhost:8741/api/categories/list')
            .then(response => response.json())
            .then(data => setCategories(data))
            .catch(error => setError("Failed to load categories."));

        fetch('http://localhost:8741/api/forumslist')
            .then(response => response.json())
            .then(data => setForums(data))
            .catch(error => setError("Failed to load forums."));
    }, []);

    const handleSubmit = (e) => {
        e.preventDefault();

        if (!name || !selectedOption) {
            setError("Name and selection are required.");
            return;
        }

        const payload = {
            name,
            description,
            banner,
            [isCategory ? 'category_id' : 'parent_forum_id']: selectedOption,
        };

        onSubmit(payload)
            .then(() => {
                setSuccess("Forum created successfully!");
                setName('');
                setDescription('');
                setSelectedOption('');
                setBanner('');
                setError(null);
            })
            .catch(() => {
                setError("An error occurred while creating the forum.");
                setSuccess(null);
            });
    };

    return (
        <form onSubmit={handleSubmit} className="admin-panel-form">
            <h2>Create a New Forum</h2>

            {error && <div className="error-message">{error}</div>}
            {success && <div className="success-message">{success}</div>}

            <label>
                Name:
                <input 
                    type="text" 
                    value={name} 
                    onChange={(e) => setName(e.target.value)} 
                    required 
                />
            </label>

            <label>
                Description:
                <textarea 
                    value={description} 
                    onChange={(e) => setDescription(e.target.value)} 
                />
            </label>

            <label>
                Create as:
                <div>
                    <button 
                        type="button" 
                        className={`toggle-button ${isCategory ? 'active' : ''}`} 
                        onClick={() => setIsCategory(true)}
                    >
                        Category
                    </button>
                    <button 
                        type="button" 
                        className={`toggle-button ${!isCategory ? 'active' : ''}`} 
                        onClick={() => setIsCategory(false)}
                    >
                        Forum Parent
                    </button>
                </div>
            </label>

            <label>
                {isCategory ? 'Select Category:' : 'Select Parent Forum:'}
                <select 
                    value={selectedOption} 
                    onChange={(e) => setSelectedOption(e.target.value)} 
                    required
                >
                    <option value="" disabled>Select an option</option>
                    {isCategory ? categories.map(category => (
                        <option key={category.id} value={category.id}>
                            {category.name}
                        </option>
                    )) : forums.map(forum => (
                        <option key={forum.id} value={forum.id}>
                            {forum.name}
                        </option>
                    ))}
                </select>
            </label>

            <label>
                Banner URL:
                <input 
                    type="text" 
                    value={banner} 
                    onChange={(e) => setBanner(e.target.value)} 
                />
            </label>

            <button type="submit">Create</button>
        </form>
    );
};

export default CreateForumForm;
