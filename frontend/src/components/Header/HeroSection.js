import React from 'react';
import './HeroSection.css';

const HeroSection = () => {
    return (
        <section className="hero-section">
            <div className="overlay"></div>
            <div className="hero-content">
                <h1 className="hero-title">Welcome to DC Earth</h1>
                <p className="hero-description">
                    Dive into the world of heroes and villains. Explore the latest events, join the community, and become part of the story.
                </p>
            </div>
        </section>
    );
};

export default HeroSection;
