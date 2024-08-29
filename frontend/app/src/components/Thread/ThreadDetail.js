import React from 'react';
import { useParams } from 'react-router-dom';
import './ThreadDetail.css';

const ThreadDetail = ({ threads }) => {
    const { id } = useParams();
    const thread = threads.find(thread => thread.id === parseInt(id));

    if (!thread) {
        return <p>Thread not found</p>;
    }

    return (
        <div className="thread-detail">
            <h2>{thread.title}</h2>
            <p><em>by {thread.author} on {thread.date}</em></p>
            <div className="posts">
                <h3>Posts</h3>
                {thread.posts.length > 0 ? (
                    <ul>
                        {thread.posts.map(post => (
                            <li key={post.id}>
                                <p>{post.content}</p>
                                <p><em>by {post.author} on {post.date}</em></p>
                            </li>
                        ))}
                    </ul>
                ) : (
                    <p>No posts available</p>
                )}
            </div>
        </div>
    );
};

export default ThreadDetail;
