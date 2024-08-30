import React from 'react';
import { Link } from 'react-router-dom';
import './LastThreadInfo.css';

const LastThreadInfo = ({ lastThread }) => {
    if (!lastThread) return null;
    console.log(lastThread)

    return (
        <Link to={`/thread/${lastThread.id}`} className="last-thread-link">
            <div className="last-thread-info">
                <div className="last-thread-details">
                    <p><strong>{lastThread.author}</strong></p>
                    <p>{lastThread.date}</p>
                    <p><em>{lastThread.title}</em></p>
                </div>
                <img src={lastThread.avatar} alt={`${lastThread.author}'s avatar`} className="avatar" />
            </div>
        </Link>
    );
};

export default LastThreadInfo;
