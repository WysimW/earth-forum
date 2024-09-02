import React from "react";
import { Link } from "react-router-dom";
import LastThreadInfo from "../Thread/LastThreadInfo";
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faMapMarkerAlt } from '@fortawesome/free-solid-svg-icons'; // Import the location icon
import "./Forum.css";

const ForumPage = ({ categories }) => {
  if (!categories || categories.length === 0) {
    return <p>Loading forums...</p>;
  }

  // Sort categories by their order property in ascending order
  const sortedCategories = [...categories].sort((a, b) => a.order - b.order);

  return (
    <div className="forum-page">
      {sortedCategories.map(category => {
        let categoryClass = "forum-category";

        // Apply different classes based on categoryType
        switch (category.categoryType) {
          case "special":
            categoryClass += " forum-category--special";
            break;
          case "information":
            categoryClass += " forum-category--information";
            break;
          case "featured":
            categoryClass += " forum-category--featured";
            break;
          case "archived":
            categoryClass += " forum-category--archived";
            break;
          case "roleplay":
            categoryClass += " forum-category--roleplay";
            break;
          default:
            categoryClass += " forum-category--default";
            break;
        }

        return (
          <div key={category.categoryName} className={categoryClass}>
            <div className="forum-category__header">
              <h2 className="forum-category__title">
                {category.categoryName}
              </h2>
            </div>
            <div className="forum-category__content">
              {category.forums.map(forum =>
                <div key={forum.id} className="forum-item">
                  <div className="forum-item__layout">
                    <div className="forum-item__details">
                      <h3 className="forum-item__title">
                        <Link
                          to={`/forum/${forum.id}`}
                          className="forum-item__link"
                        >
                          {forum.name}
                        </Link>
                      </h3>
                      
                      <div className="forum-item__description">
                      <p >{forum.description}</p>
                      </div>
                      <div className="forum-item__subforums">
                        {forum.subforums &&
                          forum.subforums.length > 0 &&
                          <ul className="forum-item__subforums-list">
                            {forum.subforums.map((subForum, index) =>
                              <li key={subForum.id} className="subforum-item">
                                <Link to={`/forum/${subForum.id}`} className="subforum-item__link">

                              <FontAwesomeIcon icon={faMapMarkerAlt} className="subforum-item__icon" />
                                  {subForum.name}
                              </Link>
                              {index < forum.subforums.length - 1 && <span className="subforum-item__separator"> | </span>}
                              </li>
                            )}
                          </ul>}
                      </div>
                    </div>
                  </div>
                  <div
                    className="forum-item__banner"
                    style={{
                      backgroundImage: `url(${forum.banner})`,
                    }}
                  >
                    <div className="forum-item__banner-overlay" />
                  </div>
                  <div className="forum-item__stats">
                    <p className="forum-item__stat">
                      {forum.stats.totalThreads} Sujets
                    </p>
                    <p className="forum-item__stat">
                      {forum.stats.totalPosts} Messages
                    </p>
                  </div>
                  <div className="forum-item__stats-and-last-thread">
                    <div className="forum-item__last-thread">
                      {Array.isArray(forum.lastThread) ||
                        <LastThreadInfo lastThread={forum.lastThread} />}
                    </div>
                  </div>
                  <div className="category-hero-logo"> 
                  {forum.heroLogo && <img src={forum.heroLogo} alt="Hero Logo" />}

          </div>
                </div>
                
              )}
            </div>

          </div>
        );
      })}
    </div>
  );
};

export default ForumPage;
