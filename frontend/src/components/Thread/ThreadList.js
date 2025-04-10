import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faComments, faEye, faCircle, faEnvelopeOpenText, faThumbtack } from '@fortawesome/free-solid-svg-icons';
import { faReadme } from '@fortawesome/free-brands-svg-icons';
import './ThreadList.css';

const ThreadList = ({ forumId }) => {
    const [threads, setThreads] = useState([]);
    const [currentPage, setCurrentPage] = useState(1);
    const threadsPerPage = 10;

    const [unreadThreads, setUnreadThreads] = useState({});

    const grpClasses = ['grp-staff', 'grp-hero', 'grp-villain', 'grp-civil'];
    const randomGrpClass = grpClasses[Math.floor(Math.random() * grpClasses.length)];
    

    useEffect(() => {
        fetch(`http://localhost:8741/api/forums/${forumId}`)
            .then(response => response.json())
            .then(data => {
                setThreads(data.threads);
                const initialUnread = {};
                data.threads.forEach(thread => {
                    initialUnread[thread.threadId] = Math.random() < 0.5;
                });
                setUnreadThreads(initialUnread);
            })
            .catch(error => console.error('Error fetching threads:', error));
    }, [forumId]);

    if (!threads || threads.length === 0) {
        return <p>No threads available</p>;
    }

    const totalPages = Math.ceil(threads.length / threadsPerPage);

    const indexOfLastThread = currentPage * threadsPerPage;
    const indexOfFirstThread = indexOfLastThread - threadsPerPage;
    const currentThreads = threads.slice(indexOfFirstThread, indexOfLastThread);

    const handlePageChange = (pageNumber) => {
        setCurrentPage(pageNumber);
    };

    const handleMarkAsRead = (threadId) => {
        setUnreadThreads(prevState => ({
            ...prevState,
            [threadId]: false
        }));
    };

    return (
        <div className="thread-list">
            <ul className="thread-list__items">
                {currentThreads.map((thread, index) => {
                    const isHighlighted = index === 0 && currentPage === 1;
                    const isUnread = unreadThreads[thread.threadId];

                    return (
                        <Link to={`/thread/${thread.threadId}`} className='thread-link'>
                            <li
                                key={thread.threadId}
                                className={`thread-item ${isUnread ? 'thread-item--unread' : ''} ${isHighlighted ? 'thread-item--highlight' : ''}`}
                                onClick={() => handleMarkAsRead(thread.threadId)}
                            >
                                <div className='thread-item--column'>
                                <div className="thread-item__header">
                                    <Link to={`/thread/${thread.threadId}`} className="thread-item__title">
                                        <div className='thread-item__title--header'>
                                            {isUnread && (
                                                <div className="thread-item__title--newmessage">Nouveau message :</div>
                                            )}

                                        </div>
                                        {isHighlighted && <FontAwesomeIcon icon={faThumbtack} className="thread-item__highlight-icon" />}
                                        {isUnread && <FontAwesomeIcon icon={faReadme} className="thread-item__unread-icon" />}
                                        {thread.title}
                                    </Link>

                                </div>
                                <div className="thread-item__body">
                                    <div className="thread-item__author">
                                        <img src={thread.authorAvatar} alt={thread.author} className="thread-item__avatar" />
                                        <div className='thread-item__author--aside'>
                                            <span>Lancé par <strong>{thread.author} </strong>
                                                le {thread.createdAt}</span>
                                            <div className="thread-item__stats">
                                                <span className="thread-item__stat">
                                                    <FontAwesomeIcon icon={faComments} /> {thread.numReplies} 100 Réponses
                                                </span>
                                                <span className="thread-item__stat">
                                                    <FontAwesomeIcon icon={faEye} /> {thread.views} 500 Vues
                                                </span>
                                            </div>
                                        </div>

                                    </div>

        
                                </div>

                                </div>
                            {thread.lastPost && (
                                        <div className="thread-item__last-post">
                                            <h5>Dernier message :</h5>
                                            <div className='thread-item__last-post--infos'>
                                            
                                            <img src={thread.lastPost.avatar} alt={thread.lastPost.author} className="thread-item__avatar" />
                                            <div className="thread-item__details">
                                                <span className="thread-item__author"><strong className={randomGrpClass}>{thread.lastPost.author}</strong></span>
                                                <span className="thread-item__date">{thread.lastPost.date}</span>
                                            </div>
                                            </div>

                                        </div>

                                    )}
                            </li>
                        </Link>
                    );
                })}
            </ul>

            <div className="thread-list__pagination">
                {Array.from({ length: totalPages }, (_, index) => (
                    <button
                        key={index + 1}
                        onClick={() => handlePageChange(index + 1)}
                        className={`thread-list__page-button ${currentPage === index + 1 ? 'thread-list__page-button--active' : ''}`}
                    >
                        {index + 1}
                    </button>
                ))}
            </div>
        </div>
    );
};

export default ThreadList;
