import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import LastThreadInfo from './LastThreadInfo';
import './Thread.css';

const ThreadList = ({ forumId }) => {
    const [threads, setThreads] = useState([]);

    useEffect(() => {
        fetch(`http://localhost:8741/api/forums/${forumId}`) // Update this to fetch threads from the forum
            .then(response => response.json())
            .then(data => setThreads(data.threads))
            .catch(error => console.error('Error fetching thread list:', error));
    }, [forumId]);

    if (!threads || threads.length === 0) {
        return <p>No threads available</p>;
    }


    return (
        <div className="thread-list">
            <ul>
                {threads.map(thread => (
                    <li key={thread.threadId} className="thread-item">
                        <div className="thread-info">
                            <div className="thread-title">
                                <Link to={`/thread/${thread.threadId}`}>{thread.title}</Link>
                            </div>
                            <div className="thread-meta">
                                <p>Started by <strong>{thread.author}</strong> on {thread.createdAt}</p>
                            </div>
                        </div>
                        <LastThreadInfo lastThread={thread.lastPost} /> {/* Using LastThreadInfo component */}
                    </li>
                ))}
            </ul>
        </div>
    );
};

export default ThreadList;
