import React, { useState, useEffect } from 'react';
import { useParams, Link } from 'react-router-dom';
import { Editor } from '@tinymce/tinymce-react';
import './ThreadDetail.css';
import { plugins } from "../../constants/plugins";
import { toolbars } from "../../constants/toolbars";

const ThreadDetail = () => {
    const { id } = useParams();
    const [threadDetail, setThreadDetail] = useState(null);
    const [postContent, setPostContent] = useState(""); // Contenu du post
    const [isHTMLView, setIsHTMLView] = useState(false);  // Switch entre TinyMCE et HTML
    const [customClass, setCustomClass] = useState('');  // Stockage des classes personnalisées

    // Récupération des détails du fil de discussion
    useEffect(() => {
        fetch(`http://localhost:8741/api/threads/${id}`)
            .then(response => response.json())
            .then(data => setThreadDetail(data))
            .catch(error => console.error('Erreur lors de la récupération des détails du thread:', error));
    }, [id]);

    const handleEditorChange = (content) => {
        setPostContent(content); // Capture le contenu de l'éditeur
    };

    const handlePostSubmit = async () => {
        try {
            const response = await fetch(`http://localhost:8741/api/threads/${id}/posts`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    content: postContent, // Envoyer le contenu WYSIWYG ou HTML
                }),
            });
            if (response.ok) {
                // Optionnellement, rafraîchir ou récupérer à nouveau les détails du fil
            } else {
                console.error("Échec de la création du post");
            }
        } catch (error) {
            console.error("Erreur lors de la création du post:", error);
        }
    };

    // Ajout de classes CSS personnalisées à l'élément sélectionné
    const addCustomClass = () => {
        if (!customClass) return;

        // Ajouter la classe au contenu HTML
        const newContent = postContent.replace(
            /(<[^>]+)>/g,
            (match) => match.replace('>', ` class="${customClass}">`)
        );
        setPostContent(newContent);
        setCustomClass('');  // Réinitialiser l'entrée
    };

    if (!threadDetail) {
        return <p>Chargement des détails du fil de discussion...</p>;
    }

    return (
        <div className="thread-detail">
            {/* Fil d'Ariane */}
            <div className="breadcrumb">
                {threadDetail.breadcrumb.map((crumb, index) => (
                    <span key={index} className={index === threadDetail.breadcrumb.length - 1 ? 'breadcrumb__item--active' : ''}>
                        <Link to={crumb.url} className="breadcrumb__link">{crumb.name}</Link>
                        {index < threadDetail.breadcrumb.length - 1 && " > "}
                    </span>
                ))}
            </div>
            <h2 className="thread-detail__title">{threadDetail.title}</h2>
            <Link to={`/thread/${id}/create-post`} className="thread-detail__create-button">
                        <button className='btn btn-primary'>
                        Créer un nouveau post
                            </button> 
                    </Link>
            <div className="thread-detail__posts">
                
                {threadDetail.posts.map(post => (
                    <div key={post.postId} className="post">
                        <div className="post__user-info">
                            <img src={post.avatar} alt={`${post.author}'s avatar`} className="post__avatar" />
                            <p className="post__author"><strong>{post.author}</strong></p>
                            <p className="post__date">{post.date}</p>
                        </div>
                        <div className="post__content" dangerouslySetInnerHTML={{ __html: post.content }} />
                    </div>
                ))}
            </div>

            {/* Éditeur de Post */}
            <div className="thread-detail__editor">
            <div className="thread-detail__editor--title">
            <h3>Créer un nouveau post :</h3>

            </div>


                {!isHTMLView ? (
                    <Editor
                        apiKey={process.env.REACT_APP_TINYMCE_API_KEY} // Clé API TinyMCE
                        value={postContent}
                        onEditorChange={handleEditorChange}
                        init={{
                            height: 300,
                            menubar: true,
                            toolbar: toolbars,
                            plugins: plugins,
                            skin: 'oxide-dark',
                            content_css: "/assets/css/tinymce.css"
                        }}
                    />
                ) : (
                    <textarea
                        value={postContent}
                        onChange={(e) => setPostContent(e.target.value)} // Permet l'édition directe du HTML
                        style={{ width: '100%', height: '300px' }}
                    />
                )}

                {/* Entrée pour les classes personnalisées */}
                {isHTMLView && (
                    <div>
                        <input
                            type="text"
                            value={customClass}
                            onChange={(e) => setCustomClass(e.target.value)}
                            placeholder="Ajouter une classe CSS personnalisée"
                        />
                        <button onClick={addCustomClass}>
                            Ajouter la classe
                        </button>
                    </div>
                )}
                <div className='thread-detail__editor--button-list'>
                    <button onClick={handlePostSubmit} className="thread-detail__submit-button btn btn-primary">
                        Soumettre le post
                    </button>
                    <button onClick={() => setIsHTMLView(!isHTMLView)} className='btn btn-secondary'>
                        {isHTMLView ? "Retour à l'éditeur" : "Voir et modifier HTML"}
                    </button>

                </div>

            </div>
        </div>
    );
};

export default ThreadDetail;
