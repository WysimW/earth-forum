import React from 'react';
import { Link } from 'react-router-dom';
import './Forum.css';  // Fichier CSS partagé

const ForumPage = ({ categories }) => {
    if (!categories || categories.length === 0) {
        return <p>Loading forums...</p>;
    }

    return (
        <div className="forum-page">
            {categories.map(category => (
                <div key={category.categoryName} className="forum-category-section">
                    <h2 className="category-title">{category.categoryName}</h2>
                    {category.forums.map(forum => (
                        <div key={forum.id} className="forum">
                            {/* Bannière du forum */}
                            <div className="forum-banner">
                                <h3>
                                    <Link to={`/forum/${forum.id}`}>{forum.name}</Link>
                                </h3>
                            </div>
                            
                            <p>{forum.description}</p>

                            {/* Sous-forums en ligne */}
                            <ul className="subforums-horizontal">
                                {forum.subForums.map(subForum => (
                                    <li key={subForum.id}>
                                        <Link to={`/subforum/${subForum.id}`}>{subForum.name}</Link>
                                    </li>
                                ))}
                            </ul>

                            <div className="last-thread">
                                <img src={forum.lastThread?.avatar} alt="Avatar" className="avatar" />
                                <div>
                                    <p><strong>Last thread:</strong> {forum.lastThread?.title}</p>
                                    <p><em>by {forum.lastThread?.author} on {forum.lastThread?.date}</em></p>
                                    <p>
                                        <Link to={`/thread/${forum.lastThread?.threadId}`}>
                                            View last thread
                                        </Link>
                                    </p>
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
            ))}
        </div>
    );
};

export default ForumPage;
