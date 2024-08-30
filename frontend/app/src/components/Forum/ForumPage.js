import React from 'react';
import { Link } from 'react-router-dom';
import LastThreadInfo from '../Thread/LastThreadInfo';
import './Forum.css';

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
                        <div key={forum.id} className="forum-item">
                            <div className="forum-layout">
                                <div className="forum-status-image">
                                    <img 
                                        src={forum.hasNewPosts 
                                            ? "https://i.servimg.com/u/f87/19/93/27/84/new10.png" 
                                            : "https://i.postimg.cc/QCk8w9S4/superman.png"} 
                                        alt={forum.hasNewPosts ? "New posts" : "No new posts"} 
                                        className="status-image" 
                                    />
                                </div>
                                <div className="forum-details">
                                    <h3 className="forum-title">
                                        <Link to={`/forum/${forum.id}`} className="forum-link">{forum.name}</Link>
                                    </h3>
                                    <div className="forum-banner">
                                        <img src={forum.bannerImage} alt={`${forum.name} banner`} className="forum-banner-image" />
                                    </div>
                                </div>
                                <div className="forum-stats-and-last-thread">
                                    <div className="forum-stats">
                                        <p>{forum.numThreads} Threads</p>
                                        <p>{forum.numMessages} Messages</p>
                                    </div>
                                    <div className="forum-last-thread">
                                        <LastThreadInfo lastThread={forum.lastThread} />
                                    </div>
                                </div>
                            </div>
                            <div className="subforums-list">
                                {forum.subForums && forum.subForums.length > 0 && (
                                    <ul>
                                        {forum.subForums.map(subForum => (
                                            <li key={subForum.id} className="subforum-item">
                                                <Link to={`/forum/${subForum.id}`} className="subforum-link">
                                                    {subForum.name}
                                                </Link>
                                                <p>{subForum.description}</p>
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </div>
                        </div>
                    ))}
                </div>
            ))}
        </div>
    );
};

export default ForumPage;
