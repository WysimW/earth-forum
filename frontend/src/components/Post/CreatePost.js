import React, { useState, useEffect } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import { Editor } from '@tinymce/tinymce-react';
import { plugins } from "../../constants/plugins";
import { toolbars } from "../../constants/toolbars";
import './CreatePost.css';
import '../Forum/ForumDetail.css';

const CreatePost = () => {
    const { id: threadId } = useParams();  // Récupérer l'ID du sujet (thread)
    const [content, setContent] = useState('');  // Contenu du post
    const [error, setError] = useState(null);  // Gestion des erreurs
    const [success, setSuccess] = useState(null);  // Gestion du succès
    const [isHTMLView, setIsHTMLView] = useState(false);  // Basculer entre éditeur TinyMCE et éditeur HTML
    const [isPreviewVisible, setIsPreviewVisible] = useState(true);  // Gérer la visibilité de la prévisualisation
    const [customClass, setCustomClass] = useState('');  // Stockage des classes personnalisées
    const [breadcrumb, setBreadcrumb] = useState([]);  // Stockage du fil d'Ariane
    const navigate = useNavigate();  // Redirection après la soumission
    const [user, setUser] = useState(null);  // Stocker les infos de l'utilisateur

    // Valeurs par défaut si l'utilisateur n'est pas connecté
    const defaultUser = {
        name: 'Invité',
        avatar: 'https://cdn.midjourney.com/f575d1f8-fb36-43fb-942a-527f5c8fd1c6/0_2.png',  // Avatar par défaut
    };

    // Simuler la récupération de l'utilisateur (vous pouvez implémenter votre propre logique d'authentification)
    useEffect(() => {
        const fetchUser = async () => {
            try {
                const response = await fetch('/http://localhost:8741/api/current-user');
                if (response.ok) {
                    const userData = await response.json();
                    setUser(userData);
                } else {
                    setUser(defaultUser);  // Si pas de réponse, utiliser les valeurs par défaut
                }
            } catch (error) {
                console.error('Erreur lors de la récupération des informations utilisateur:', error);
                setUser(defaultUser);  // En cas d'erreur, utiliser les valeurs par défaut
            }
        };

        const fetchBreadcrumb = async () => {
            try {
                const response = await fetch(`http://localhost:8741/api/threads/${threadId}/breadcrumb`);
                if (response.ok) {
                    const breadcrumbData = await response.json();
                    setBreadcrumb(breadcrumbData);
                } else {
                    setBreadcrumb([]);
                }
            } catch (error) {
                console.error('Erreur lors de la récupération du fil d\'Ariane:', error);
                setBreadcrumb([]);
            }
        };

        fetchUser();
        fetchBreadcrumb();
    }, [threadId]);

    const handleEditorChange = (content) => {
        setContent(content); // Capture le contenu de l'éditeur
    };

    const handlePostSubmit = async (e) => {
        e.preventDefault();

        if (!content) {
            setError("Le contenu ne peut pas être vide.");
            return;
        }

        const payload = {
            content,
            thread_id: threadId,
        };

        try {
            const response = await fetch(`http://localhost:8741/api/threads/${threadId}/posts`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(payload),
            });

            if (response.ok) {
                setSuccess("Post créé avec succès !");
                setContent('');
                setError(null);
                navigate(`/thread/${threadId}`);  // Rediriger vers le sujet après la création du post
            } else {
                setError("Une erreur est survenue lors de la création du post.");
                setSuccess(null);
            }
        } catch (err) {
            setError("Une erreur est survenue lors de la création du post.");
            setSuccess(null);
        }
    };

    // Ajout de classes CSS personnalisées à l'élément sélectionné
    const addCustomClass = () => {
        if (!customClass) return;

        // Ajouter la classe au contenu HTML
        const newContent = content.replace(
            /(<[^>]+)>/g,
            (match) => match.replace('>', ` class="${customClass}">`)
        );
        setContent(newContent);
        setCustomClass('');  // Réinitialiser l'entrée
    };

    return (
        <form onSubmit={handlePostSubmit} className="create-post-form">
            {/* Fil d'Ariane */}
            <nav className="breadcrumb">
                {breadcrumb.map((crumb, index) => (
                    <span key={index} className={index === breadcrumb.length - 1 ? 'breadcrumb__item--active' : ''}>
                        <Link to={crumb.url} className="breadcrumb__link">{crumb.name}</Link>
                        {index < breadcrumb.length - 1 && " > "}
                    </span>
                ))}
            </nav>

            <div className='create-post-form__header'>
                <h2>Créer un nouveau Post</h2>
            </div>

            {error && <div className="error-message">{error}</div>}
            {success && <div className="success-message">{success}</div>}

            <label>
                {/* Bouton pour activer/désactiver l'éditeur HTML */}
                <button
                    type="button"
                    onClick={() => setIsHTMLView(!isHTMLView)}
                    className="toggle-editor-view btn btn-tertiary"
                >
                    {isHTMLView ? '> Passer à l\'Editeur visuel <' : '> Passer à l\'Editeur HTML <'}
                </button>

                {!isHTMLView ? (
                    <Editor
                        apiKey={process.env.REACT_APP_TINYMCE_API_KEY}  // Utiliser la clé API TinyMCE
                        value={content}
                        onEditorChange={handleEditorChange}
                        init={{
                            height: 500,
                            menubar: true,
                            plugins: plugins,
                            toolbar: toolbars,
                            content_css: "/assets/css/tinymce.css",
                            skin: 'oxide-dark',  // Utilisation d'un skin sombre
                        }}
                    />
                ) : (
                    <textarea
                        value={content}
                        onChange={(e) => setContent(e.target.value)}  // Édition directe du HTML
                        style={{ width: '100%', height: '500px', fontFamily: 'monospace', backgroundColor: '#333', color: '#fff' }}
                    />
                )}
            </label>

            {/* Entrée pour les classes personnalisées */}
            {isHTMLView && (
                <div>
                    <input
                        type="text"
                        value={customClass}
                        onChange={(e) => setCustomClass(e.target.value)}
                        placeholder="Ajouter une classe CSS personnalisée"
                    />
                    <button type="button" onClick={addCustomClass} className='btn btn-tertiary'>
                        Ajouter la classe
                    </button>
                </div>
            )}

            <div className='create-post__button-list'>
                <button type="submit" className="btn btn-primary">Poster</button>

                {/* Bouton pour activer/désactiver la prévisualisation */}
                <button
                    type="button"
                    onClick={() => setIsPreviewVisible(!isPreviewVisible)}
                    className="toggle-preview btn btn-secondary"
                >
                    {isPreviewVisible ? 'Masquer la prévisualisation' : 'Afficher la prévisualisation'}
                </button>
            </div>

            {/* Prévisualisation */}
            {isPreviewVisible && (
                <div className="post-preview">
                    <h3>Aperçu du Post :</h3>

                    <div className="post">
                        <div className="post__user-info">
                            <img
                                src={user?.avatar || defaultUser.avatar}
                                alt={`${user?.name || defaultUser.name}'s avatar`}
                                className="post__avatar"
                            />
                            <p className="post__author">
                                <strong>{user?.name || defaultUser.name}</strong>
                            </p>
                            <p className="post__date">Maintenant</p>
                        </div>
                        <div dangerouslySetInnerHTML={{ __html: content }} className="post__content" />
                    </div>
                </div>
            )}
        </form>
    );
};

export default CreatePost;
