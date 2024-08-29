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
        <div>
            <h2>{thread.title}</h2>
            <p><em>by {thread.author} on {thread.date}</em></p>
            <div>
                <h3>Posts</h3>
                <ul>
                    {thread.posts.map(post => (
                        <li key={post.id}>
                            <p>{post.content}</p>
                            <p><em>by {post.author} on {post.date}</em></p>
                        </li>
                    ))}
                </ul>
            </div>
        </div>
    );
};

export default ThreadDetail;