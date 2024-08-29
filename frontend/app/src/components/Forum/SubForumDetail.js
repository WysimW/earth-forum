import React from 'react';
import { Link, useParams } from 'react-router-dom';
import './Forum.css';
import ThreadList from '../Thread/ThreadList';  // Import de ThreadList

const SubForumDetail = ({ subForums }) => {
    const { id } = useParams();
    const subForum = subForums.find(subForum => subForum.id === parseInt(id));

    if (!subForum) {
        return <p>SubForum not found</p>;
    }

    const { threads = [] } = subForum;

    return (
        <div className="subforum-detail">
            <h2>{subForum.name}</h2>
            <p>{subForum.description}</p>
            <div className="threads">
                <h3>Threads</h3>
                <ThreadList threads={threads} /> {/* Utilisation de ThreadList ici */}
            </div>
        </div>
    );
};

export default SubForumDetail;
