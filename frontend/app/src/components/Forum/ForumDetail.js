import React from 'react';
import { Link, useParams } from 'react-router-dom';
import './Forum.css';
import ThreadList from '../Thread/ThreadList';  // Import de ThreadList

const ForumDetail = ({ forums }) => {
    const { id } = useParams();
    const forum = forums.find(forum => forum.id === parseInt(id));

    if (!forum) {
        return <p>Forum not found</p>;
    }

    const { subForums = [], threads = [] } = forum;

    return (
        <div className="forum-detail">
            <h2>{forum.name}</h2>
            <p>{forum.description}</p>
            <div className="subforums">
                <h3>Subforums</h3>
                {subForums.length > 0 ? (
                    <ul>
                        {subForums.map(subForum => (
                            <li key={subForum.id}>
                                <h4>
                                    <Link to={`/subforum/${subForum.id}`}>{subForum.name}</Link>
                                </h4>
                                <p>{subForum.description}</p>
                                <div className="last-thread">
                                    <p><strong>Last thread:</strong> {subForum.lastThread?.title}</p>
                                    <p><em>by {subForum.lastThread?.author} on {subForum.lastThread?.date}</em></p>
                                    <p>
                                        <Link to={`/thread/${subForum.lastThread?.threadId}`}>
                                            View last thread
                                        </Link>
                                    </p>
                                </div>
                            </li>
                        ))}
                    </ul>
                ) : (
                    <p>No subforums available</p>
                )}
            </div>
            <div className="threads">
                <h3>Threads</h3>
                <ThreadList threads={threads} /> {/* Utilisation de ThreadList ici */}
            </div>
        </div>
    );
};

export default ForumDetail;

