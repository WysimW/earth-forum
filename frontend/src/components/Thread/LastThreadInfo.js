import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import './LastThreadInfo.css';

const LastThreadInfo = ({ lastThread }) => {
    const [maxChars, setMaxChars] = useState(50); // Default value

    useEffect(() => {
        const updateMaxChars = () => {
            const width = window.innerWidth;
            if (width < 600) {
                setMaxChars(10); // Smaller screens
            } else if (width <= 1024) {
                setMaxChars(12); // Medium screens
            } else {
                setMaxChars(20); // Larger screens
            }
        };

        // Initial check
        updateMaxChars();

        // Add event listener
        window.addEventListener('resize', updateMaxChars);

        // Clean up event listener on unmount
        return () => {
            window.removeEventListener('resize', updateMaxChars);
        };
    }, []);

    if (!lastThread) return null;

    return (
        <Link to={`/thread/${lastThread.id}`} className="last-thread__link">
            <div className="last-thread__info">
                <div className="last-thread__details">
                    <p>{lastThread.date}</p>
                    <p>Sujet : <em>{lastThread.title.length > maxChars ? `${lastThread.title.substring(0, maxChars)}...` : lastThread.title}</em></p>
                    </div>
                <div className="last-thread__author_details">
                <img src={lastThread.avatar} alt={`${lastThread.author}'s avatar`} className="last-thread__avatar" />

                </div>
            </div>
        </Link>
    );
};

export default LastThreadInfo;
