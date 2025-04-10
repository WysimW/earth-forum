import React from 'react';
import './Footer.css';

const Footer = () => {
    const recentlyConnected = ["User1", "User2", "User3", "User4", "User5"];
    const roles = {
        Heroes: ["Hero1", "Hero2"],
        Villains: ["Villain1", "Villain2"],
        Civilians: ["Civilian1", "Civilian2"],
    };
    const currentlyConnected = ["User6", "User7"];
    const forumStats = {
        threads: 1023,
        posts: 45367,
        members: 1234,
    };

    return (
        <footer className="site-footer">
            <div className="footer-container">
                <div className="footer-card">
                    <h3>Recently Connected</h3>
                    <ul className="member-list">
                        {recentlyConnected.map((member, index) => (
                            <li key={index}>{member}</li>
                        ))}
                    </ul>
                </div>

                <div className="footer-card">
                    <h3>Roles</h3>
                    <div className="role-list">
                        {Object.keys(roles).map((role, index) => (
                            <div key={index} className={`role-card ${role.toLowerCase()}`}>
                                <h4>{role}</h4>
                            </div>
                        ))}
                    </div>
                </div>

                <div className="footer-card">
                    <h3>Currently Connected</h3>
                    <ul className="member-list">
                        {currentlyConnected.map((member, index) => (
                            <li key={index}>{member}</li>
                        ))}
                    </ul>
                </div>

                <div className="footer-card">
                    <h3>Forum Stats</h3>
                    <ul className="stats-list">
                        <li>Threads: {forumStats.threads}</li>
                        <li>Posts: {forumStats.posts}</li>
                        <li>Members: {forumStats.members}</li>
                    </ul>
                </div>
            </div>
        </footer>
    );
};

export default Footer;
