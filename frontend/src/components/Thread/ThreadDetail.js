import React, { useState, useEffect } from 'react';
import { useParams, Link } from 'react-router-dom';
import './Thread.css';

const ThreadDetail = () => {
    const { id } = useParams();
    const [threadDetail, setThreadDetail] = useState(null);

    useEffect(() => {
        fetch(`http://localhost:8741/api/threads/${id}`)
            .then(response => response.json())
            .then(data => setThreadDetail(data))
            .catch(error => console.error('Error fetching thread detail:', error));
    }, [id]);

    if (!threadDetail) {
        return <p>Loading thread details...</p>;
    }

    return (
        <div className="thread-detail">
            <h2>{threadDetail.title}</h2>

            {/* Breadcrumb */}
            <div className="breadcrumb">
                {threadDetail.breadcrumb.map((crumb, index) => (
                    <span key={index}>
                        <Link to={crumb.url}>{crumb.name}</Link>
                        {index < threadDetail.breadcrumb.length - 1 && " > "}
                    </span>
                ))}
            </div>

            <div className="posts">
                {threadDetail.posts.map(post => (
                    <div key={post.postId} className="post">
                        <div className="user-info">
                            <img src={post.avatar} alt={`${post.author}'s avatar`} className="avatar" />
                            <p><strong>{post.author}</strong></p>
                            <p>{post.date}</p>
                        </div>
                        <div className="post-content" dangerouslySetInnerHTML={{ __html: post.content }} />
                    </div>
                ))}
            </div>
        </div>
    );
};

export default ThreadDetail;
