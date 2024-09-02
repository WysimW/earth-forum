import React, { useState } from 'react';
import { useParams } from 'react-router-dom';
import { Editor } from '@tinymce/tinymce-react';

const ThreadForm = ({ onSubmit }) => {
    const { id: forumId } = useParams();
    const [title, setTitle] = useState('');
    const [content, setContent] = useState('');
    const [error, setError] = useState(null);
    const [success, setSuccess] = useState(null);

    const handleSubmit = (e) => {
        e.preventDefault();

        if (!title || !content) {
            setError("Both title and content are required.");
            return;
        }

        const payload = {
            title,
            content,
            forum_id: forumId,
        };

        onSubmit(payload)
            .then(() => {
                setSuccess("Thread created successfully!");
                setTitle('');
                setContent('');
                setError(null);
            })
            .catch((err) => {
                setError("An error occurred while creating the thread.");
                setSuccess(null);
            });
    };

    return (
        <form onSubmit={handleSubmit} className="thread-form">
            <h2>Create a New Thread</h2>

            {error && <div className="error-message">{error}</div>}
            {success && <div className="success-message">{success}</div>}

            <label>
                Title:
                <input 
                    type="text" 
                    value={title} 
                    onChange={(e) => setTitle(e.target.value)} 
                    required 
                />
            </label>

            <label>
                Content:
                <Editor
                    apiKey="6ikn2uggs5pcmbmnqykc73x0bycevtswtv9y3fv4icpx795g" // Replace with your TinyMCE API key
                    value={content}
                    onEditorChange={(newContent) => setContent(newContent)}
                    init={{
                        height: 300,
                        menubar: false,
                        plugins: [
                            'advlist autolink lists link image charmap print preview anchor',
                            'searchreplace visualblocks code fullscreen',
                            'insertdatetime media table paste code help wordcount'
                        ],
                        toolbar:
                            'undo redo | formatselect | bold italic backcolor | \
                            alignleft aligncenter alignright alignjustify | \
                            bullist numlist outdent indent | removeformat | help'
                    }}
                />
            </label>

            <button type="submit" className="btn">Create Thread</button>
        </form>
    );
};

export default ThreadForm;
