import React, { useState, useEffect } from 'react';
import { useParams, Link, useLocation } from 'react-router-dom';
import './Forum.css';
import './ForumDetail.css';
import ThreadList from '../Thread/ThreadList';
import LastThreadInfo from '../Thread/LastThreadInfo';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faPenNib, faPlus, faBookOpenReader } from '@fortawesome/free-solid-svg-icons';

const ForumDetail = () => {
    const { id } = useParams(); 
    const [forum, setForum] = useState(null);
    const [loading, setLoading] = useState(true);
    const [subForums, setSubForums] = useState([]);
    const location = useLocation();
    const forumDetailClass = "forum-detail";
    
    // Define breadcrumb data
    const breadcrumb = [
        { name: "Accueil", url: "/" },
        { name: "Forums", url: "/forums" },
        { name: forum?.forumName || "Forum", url: `/forum/${id}` }
    ];

    useEffect(() => {
        fetch(`http://localhost:8741/api/forums/${id}`)
            .then(response => response.json())
            .then(data => {
                setForum(data);
                // If the forum has subForums property, set it
                if (data.subForums) {
                    setSubForums(data.subForums);
                }
                setLoading(false);
            })
            .catch(error => {
                console.error('Error fetching forum data:', error);
                setLoading(false);
            });
    }, [id]);

    console.log(forum)

    if (loading) {
        return <p>Loading...</p>;
    }

    if (!forum) {
        return <p>Forum not found</p>;
    }
    
    return (
        <div className={forumDetailClass}>
            <nav className="breadcrumb">
                {breadcrumb.map((crumb, index) => {
                    const isActive = location.pathname === crumb.url;
                    return (
                        <span key={index} className={isActive ? "breadcrumb__item--active" : ""}>
                            <Link to={crumb.url} className="breadcrumb__link">
                                {crumb.name}
                            </Link>
                            {index < breadcrumb.length - 1 && " > "}
                        </span>
                    );
                })}
            </nav>

            <div className="forum-detail__create-thread">
                <Link to={`/forum/${id}/create-thread`} className="forum-detail__create-button btn btn-primary">
                    <FontAwesomeIcon icon={faPlus} className='btn-icon' /> Nouveau Sujet
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
                    <div className="forum-detail__subforums--title">
                        <h3>Sous-Forums</h3>
                    </div>
                    <div className='subforums--content'>
                        {subForums.map(subForum => (
                            <div key={subForum.id} className="forum-item">
                                <div className="forum-item__layout">
                                    <div className="forum-item__details">
                                        <h4 className="forum-item__title">
                                            <Link to={`/forum/${subForum.id}`} className="forum-item__link">
                                                {subForum.name}
                                            </Link>
                                        </h4>
                                        <p className="forum-item__description">{subForum.description}</p>
                                        <div className="forum-item__subforums">
                                            {subForum.latestThreads && subForum.latestThreads.length > 0 && (
                                                <ul className="forum-item__subforums-list">
                                                    {subForum.latestThreads.map((latestThread, index) => {
                                                        // Set the maximum number of characters allowed
                                                        const maxLength = 20;

                                                        // Truncate the title if it exceeds maxLength
                                                        const truncatedTitle = latestThread.title.length > maxLength
                                                            ? latestThread.title.slice(0, maxLength) + '...'
                                                            : latestThread.title;

                                                        return (
                                                            <li key={latestThread.id} className="subforum-item">
                                                                <Link to={`/thread/${latestThread.id}`} className="subforum-item__link">
                                                                    <FontAwesomeIcon icon={faBookOpenReader} className="subforum-item__icon" />
                                                                    {truncatedTitle}
                                                                </Link>
                                                                {index < subForum.latestThreads.length - 1 && <span className="subforum-item__separator"> | </span>}
                                                            </li>
                                                        );
                                                    })}
                                                </ul>
                                            )}
                                            {subForum.latestThreads && subForum.latestThreads.length == 0 && (
                                                <ul className="forum-item__subforums-list">

                                                    <li className="subforum-item subforum-item__no-thread">
                                                    <p>Pas encore de sujet : </p>
                                                        <Link to={`/forum/${subForum.id}/create-thread`} className="subforum-item__link">
                                                            <FontAwesomeIcon icon={faPenNib} className="subforum-item__icon" />
                                                            Rédiger le premier !
                                                        </Link>
                                                    </li>

                                                </ul>
                                            )}
                                        </div>
                                    </div>
                                </div>
                                <div className="forum-item__stats">
                                    <p className="forum-item__stat">
                                        {subForum.stats.totalThreads} Sujets
                                    </p>
                                    <p className="forum-item__stat">
                                        {subForum.stats.totalPosts} Messages
                                    </p>
                                </div>
                                <div className="forum-item__stats-and-last-thread">

                                    <div className="forum-item__last-thread">
                                        {!Array.isArray(subForum.lastThread) && subForum.lastThread && (
                                            <LastThreadInfo lastThread={subForum.lastThread} />
                                        )}
                                    </div>

                                </div>
                                <div
                                    className="forum-item__banner"
                                    style={{
                                        backgroundImage: `url(${subForum.banner})`,
                                    }}
                                >
                                    <div className="forum-item__banner-overlay" />
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            )}

            <div className="forum-detail__threads">
            <div className="forum-detail__subforums--title">
                        <h3>Liste des sujets</h3>
                    </div>
                <ThreadList forumId={forum.forumId} />
            </div>
        </div>
    );
};

export default ForumDetail;
