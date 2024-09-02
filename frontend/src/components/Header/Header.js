import React from 'react';
import { NavLink } from 'react-router-dom';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faUser, faEnvelope, faCog } from '@fortawesome/free-solid-svg-icons';
import './Header.css';

const Header = () => {
    return (
        <header className="header">
            <div className="header__container">
                <div className="header__brand">
                    <img className="header__logo" src="/assets/svg/dc-comics-seeklogo.svg" alt="My Icon" />
                    <h1 className="header__title">Earth</h1>
                </div>
                <nav className="header__nav">
                    <ul className="header__nav-list">
                        <li className="header__nav-item">
                            <NavLink exact to="/" activeClassName="header__nav-link--active" className="header__nav-link">
                                Home
                            </NavLink>
                        </li>
                        <li className="header__nav-item">
                            <NavLink to="/forums" activeClassName="header__nav-link--active" className="header__nav-link">
                                Forums
                            </NavLink>
                        </li>
                        <li className="header__nav-item">
                            <NavLink to="/about" activeClassName="header__nav-link--active" className="header__nav-link">
                                About
                            </NavLink>
                        </li>
                        <li className="header__nav-item">
                            <NavLink to="/contact" activeClassName="header__nav-link--active" className="header__nav-link">
                                Contact
                            </NavLink>
                        </li>
                        <li className="header__nav-item">
                            <NavLink to="/admin" activeClassName="header__nav-link--active" className="header__nav-link">
                                Admin Panel
                            </NavLink>
                        </li>
                    </ul>
                </nav>
                <div className="header__icons">
                    <NavLink to="/messages" className="header__icon-link">
                        <FontAwesomeIcon icon={faEnvelope} />
                    </NavLink>
                    <NavLink to="/account" className="header__icon-link">
                        <FontAwesomeIcon icon={faUser} />
                    </NavLink>
                    <NavLink to="/settings" className="header__icon-link">
                        <FontAwesomeIcon icon={faCog} />
                    </NavLink>
                    <img className="header__avatar" src="https://cdn.midjourney.com/ffd57225-de42-41a8-9134-155bbb58daa7/0_2.png" alt="User Avatar" />
                </div>
            </div>
        </header>
    );
};

export default Header;
