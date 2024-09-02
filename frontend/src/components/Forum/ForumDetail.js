import React, { useState, useEffect } from 'react';
import { useParams, Link } from 'react-router-dom';
import './Forum.css';
import './ForumDetail.css';
import ThreadList from '../Thread/ThreadList';
import LastThreadInfo from '../Thread/LastThreadInfo';

const ForumDetail = () => {
    const { id } = useParams();
    const [forum, setForum] = useState(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        fetch(`http://localhost:8741/api/forums/${id}`)
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
        <div className="forum-detail">
            <nav className="breadcrumb">
                {breadcrumb.map((crumb, index) => (
                    <span key={index}>
                        <Link to={crumb.url} className="breadcrumb__link">{crumb.name}</Link>
                        {index < breadcrumb.length - 1 && " > "}
                    </span>
                ))}
            </nav>

            <div className="forum-detail__create-thread">
                <Link to={`/forum/${id}/create-thread`} className="forum-detail__create-button">
                    Create New Thread
                </Link>
            </div>

            <div className="forum-detail__item">
                <div className="forum-item__layout">
                    <div className="forum-item__details">
                        <h3 className="forum-item__title">{forum.forumName}</h3>
                        <p className="forum-item__description">{forum.description}</p>
                    </div>
                </div>
            </div>

            {subForums.length > 0 && (
                <div className="forum-detail__subforums">
                    <h3>Subforums</h3>
                    {subForums.map(subForum => (
                        <div key={subForum.id} className="forum-item">
                            <div className="forum-item__layout">
                                <div className="forum-item__status-image">
                                    <img
                                        src={subForum.hasNewPosts 
                                            ? "https://i.servimg.com/u/f87/19/93/27/84/new10.png" 
                                            : "https://i.postimg.cc/QCk8w9S4/superman.png"} 
                                        alt={subForum.hasNewPosts ? "New posts" : "No new posts"} 
                                        className="forum-item__status-image-img" 
                                    />
                                </div>
                                <div className="forum-item__details">
                                    <h4 className="forum-item__title">
                                        <Link to={`/forum/${subForum.id}`} className="forum-item__link">{subForum.name}</Link>
                                    </h4>
                                    <div className="forum-item__banner">
                                        <img src={subForum.bannerImage} alt={`${subForum.name} banner`} className="forum-item__banner-image" />
                                    </div>
                                    <p className="forum-item__description">{subForum.description}</p>
                                </div>
                                <div className="forum-item__stats-and-last-thread">
                                    <div className="forum-item__stats">
                                        <p className="forum-item__stat">{subForum.numThreads} Threads</p>
                                        <p className="forum-item__stat">{subForum.numMessages} Messages</p>
                                    </div>
                                    <div className="forum-item__last-thread">
                                        <LastThreadInfo lastThread={subForum.lastThread} />
                                    </div>
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
            )}

            <div className="forum-detail__threads">
                <h3>Threads</h3>
                <ThreadList forumId={forum.forumId} />
            </div>
        </div>
    );
};

export default ForumDetail;
