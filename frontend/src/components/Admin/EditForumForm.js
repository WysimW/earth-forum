import React, { useState, useEffect } from 'react';
import './AdminPanel.css';

const EditForumForm = ({ forum, onSubmit }) => {
    const [name, setName] = useState(forum.name || '');
    const [description, setDescription] = useState(forum.description || '');
    const [categories, setCategories] = useState([]);
    const [forums, setForums] = useState([]);
    const [isCategory, setIsCategory] = useState(!!forum.category_id); // Initialize based on whether there's a category_id
    const [selectedOption, setSelectedOption] = useState(forum.category_id || forum.parent_forum_id || '');
    const [banner, setBanner] = useState(forum.banner || '');
    const [error, setError] = useState(null);
    const [success, setSuccess] = useState(null);

    console.log(forum)

    useEffect(() => {
        // Fetch categories and forums from the backend
        fetch(`http://localhost:8741/api/forums/${forum.id}/edit-data`)
            .then(response => response.json())
            .then(data => {
                setCategories(data.categories);
                setForums(data.forums);

                // Determine initial selection and toggle state
                if (forum.category_id) {
                    setIsCategory(true);
                    setSelectedOption(forum.category_id);
                } else if (forum.parent_forum_id) {
                    setIsCategory(false);
                    setSelectedOption(forum.parent_forum_id);
                }
            })
            .catch(error => setError("Failed to load data."));
    }, [forum.id]);

    const handleSubmit = (e) => {
        e.preventDefault();

        if (!name || !selectedOption) {
            setError("Name and selection are required.");
            return;
        }

        const payload = {
            id: forum.id,
            name,
            description,
            banner,
            [isCategory ? 'category_id' : 'parent_forum_id']: selectedOption,
        };

        onSubmit(payload)
            .then(() => {
                setSuccess("Forum updated successfully!");
                setError(null);
            })
            .catch(() => {
                setError("An error occurred while updating the forum.");
                setSuccess(null);
            });
    };

    return (
        <form onSubmit={handleSubmit} className="admin-panel-form">
            <h2>Edit Forum</h2>

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
                Edit as:
                <div>
                    <button 
                        type="button" 
                        className={`toggle-button ${isCategory ? 'active' : ''}`} 
                        onClick={() => {
                            setIsCategory(true);
                            setSelectedOption(forum.category_id || '');
                        }}
                    >
                        Category
                    </button>
                    <button 
                        type="button" 
                        className={`toggle-button ${!isCategory ? 'active' : ''}`} 
                        onClick={() => {
                            setIsCategory(false);
                            setSelectedOption(forum.parent_forum_id || '');
                        }}
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

            <button type="submit">Update</button>
        </form>
    );
};

export default EditForumForm;
