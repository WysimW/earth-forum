import React from 'react';
import { Link } from 'react-router-dom';
import './ThreadList.css';

const ThreadList = ({ threads }) => {
    if (!threads || threads.length === 0) {
        return <p>No threads available</p>;
    }

    return (
        <div className="thread-list">
            <h3>Threads</h3>
            <ul>
                {threads.map((thread) => (
                    <li key={thread.id}>
                        <h4>
                            <Link to={`/thread/${thread.id}`}>{thread.title}</Link>
                        </h4>
                        <p><em>by {thread.author} on {thread.date}</em></p>
                    </li>
                ))}
            </ul>
        </div>
    );
};

export default ThreadList;
