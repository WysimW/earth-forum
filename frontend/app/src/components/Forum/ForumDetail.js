import React, { useState, useEffect } from 'react';
import { useParams, Link } from 'react-router-dom';
import './Forum.css';
import ThreadList from '../Thread/ThreadList';
import LastThreadInfo from '../Thread/LastThreadInfo';

const ForumDetail = () => {
    const { id } = useParams();
    const [forum, setForum] = useState(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        fetch(`http://localhost:8741/api/forums/${id}`) // Update this to your backend endpoint
            .then(response => response.json())
            .then(data => {
                setForum(data);
                setLoading(false);
            })
            .catch(error => {
                console.error('Error fetching forum data:', error);
                setLoading(false);
            });
    }, [id]);

    if (loading) {
        return <p>Loading...</p>;
    }

    if (!forum) {
        return <p>Forum not found</p>;
    }
    const { subForums = [], breadcrumb = [], threads = [] } = forum;
    return (
        <div className="forum-detail forum-ondescription">
            <nav className="breadcrumb">
                {breadcrumb.map((crumb, index) => (
                    <span key={index}>
                        <Link to={crumb.url}>{crumb.name}</Link>
                        {index < breadcrumb.length - 1 && " > "}
                    </span>
                ))}
            </nav>
            {/* Button to create a new thread */}
            <div className="create-thread-button">
                <Link to={`/forum/${id}/create-thread`} className="button">
                    Create New Thread
                </Link>
            </div>
            <div className="forum-item">
                <div className="forum-layout">

                    <div className="forum-details">
                        <h3 className="forum-title">
                            {forum.forumName}
                        </h3>
                        <p>{forum.description}</p>
                    </div>

                </div>
            </div>

            {subForums.length > 0 && (
                <div className="subforums">
                    <h3>Subforums</h3>
                    {subForums.map(subForum => (
                        <div key={subForum.id} className="forum-item">
                            <div className="forum-layout">
                                <div className="forum-status-image">
                                    <img 
                                        src={subForum.hasNewPosts 
                                            ? "https://i.servimg.com/u/f87/19/93/27/84/new10.png" 
                                            : "https://i.postimg.cc/QCk8w9S4/superman.png"} 
                                        alt={subForum.hasNewPosts ? "New posts" : "No new posts"} 
                                        className="status-image" 
                                    />
                                </div>
                                <div className="forum-details">
                                    <h4 className="forum-title">
                                        <Link to={`/forum/${subForum.id}`} className="forum-link">{subForum.name}</Link>
                                    </h4>
                                    <div className="forum-banner">
                                        <img src={subForum.bannerImage} alt={`${subForum.name} banner`} className="forum-banner-image" />
                                    </div>
                                    <p>{subForum.description}</p>
                                </div>
                                <div className="forum-stats-and-last-thread">
                                    <div className="forum-stats">
                                        <p>{subForum.numThreads} Threads</p>
                                        <p>{subForum.numMessages} Messages</p>
                                    </div>
                                    <div className="forum-last-thread">
                                        <LastThreadInfo lastThread={subForum.lastThread} />
                                    </div>
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
            )}

            <div className="threads">
                <h3>Threads</h3>
                <ThreadList forumId={forum.forumId} />
            </div>
        </div>
    );
};

export default ForumDetail;
