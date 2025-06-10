/**
 * Éditeur d'avatar avec upload et recadrage
 * Nécessite Cropper.js
 */

class AvatarEditor {
    constructor() {
        this.cropper = null;
        this.currentFile = null;
        this.currentFilename = null;
        this.initializeEventListeners();
        console.log('🎨 AvatarEditor initialisé');
    }

    initializeEventListeners() {
        // Gestion des onglets
        document.querySelectorAll('.avatar-tab').forEach(tab => {
            tab.addEventListener('click', (e) => this.switchTab(e));
        });

        // Upload par clic
        const dropzone = document.getElementById('avatarDropzone');
        const fileInput = document.getElementById('avatarFileInput');

        if (dropzone && fileInput) {
            dropzone.addEventListener('click', () => fileInput.click());
            fileInput.addEventListener('change', (e) => this.handleFileSelect(e));

            // Drag & Drop
            dropzone.addEventListener('dragover', (e) => this.handleDragOver(e));
            dropzone.addEventListener('dragleave', (e) => this.handleDragLeave(e));
            dropzone.addEventListener('drop', (e) => this.handleFileDrop(e));
        }

        // Prévisualisation URL
        const avatarInput = document.querySelector('input[name*="avatar"]');
        if (avatarInput) {
            avatarInput.addEventListener('input', (e) => this.handleUrlChange(e));
        }

        // Contrôles de recadrage
        const confirmCrop = document.getElementById('confirmCrop');
        const cancelCrop = document.getElementById('cancelCrop');

        if (confirmCrop) {
            confirmCrop.addEventListener('click', () => this.confirmCrop());
        }

        if (cancelCrop) {
            cancelCrop.addEventListener('click', () => this.cancelCrop());
        }
    }

    switchTab(event) {
        const clickedTab = event.currentTarget;
        const targetTab = clickedTab.dataset.tab;

        // Mise à jour des onglets
        document.querySelectorAll('.avatar-tab').forEach(tab => {
            tab.classList.remove('active');
        });
        clickedTab.classList.add('active');

        // Mise à jour du contenu
        document.querySelectorAll('.avatar-tab-pane').forEach(pane => {
            pane.classList.remove('active');
        });
        document.getElementById(`tab-${targetTab}`).classList.add('active');

        // Nettoyer l'autre méthode
        if (targetTab === 'url') {
            this.clearUpload();
        } else {
            this.clearUrl();
        }
    }

    handleDragOver(event) {
        event.preventDefault();
        event.stopPropagation();
        event.currentTarget.classList.add('dragover');
    }

    handleDragLeave(event) {
        event.preventDefault();
        event.stopPropagation();
        event.currentTarget.classList.remove('dragover');
    }

    handleFileDrop(event) {
        event.preventDefault();
        event.stopPropagation();
        event.currentTarget.classList.remove('dragover');

        const files = event.dataTransfer.files;
        if (files.length > 0) {
            this.processFile(files[0]);
        }
    }

    handleFileSelect(event) {
        const file = event.target.files[0];
        if (file) {
            this.processFile(file);
        }
    }

    handleUrlChange(event) {
        const url = event.target.value.trim();
        if (url) {
            this.loadImageFromUrl(url);
        } else {
            this.clearPreview();
        }
    }

    processFile(file) {
        // Validation
        if (!this.validateFile(file)) {
            return;
        }

        this.currentFile = file;
        this.showProgress(true);

        // Upload du fichier
        this.uploadFile(file)
            .then(response => {
                this.showProgress(false);
                if (response.success) {
                    this.currentFilename = response.filename;
                    document.getElementById('avatarFilename').value = response.filename;
                    this.loadImageForCropping(response.path);
                    this.showMessage('Avatar uploadé avec succès !', 'success');
                } else {
                    this.showMessage(response.error || 'Erreur lors de l\'upload', 'error');
                }
            })
            .catch(error => {
                this.showProgress(false);
                this.showMessage('Erreur lors de l\'upload : ' + error.message, 'error');
            });
    }

    validateFile(file) {
        const maxSize = 5 * 1024 * 1024; // 5MB
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

        if (file.size > maxSize) {
            this.showMessage('Le fichier est trop volumineux. Taille maximum : 5MB', 'error');
            return false;
        }

        if (!allowedTypes.includes(file.type)) {
            this.showMessage('Type de fichier non autorisé. Formats acceptés : JPEG, PNG, GIF, WebP', 'error');
            return false;
        }

        return true;
    }

    async uploadFile(file) {
        const formData = new FormData();
        formData.append('avatar', file);
        formData.append('character_name', this.getCharacterName());

        try {
            // Utiliser l'URL configurée ou fallback vers l'ancienne
            const uploadUrl = this.uploadUrl || '/characters/avatar/upload';
            const response = await fetch(uploadUrl, {
                method: 'POST',
                body: formData
            });

            return await response.json();
        } catch (error) {
            throw new Error('Erreur réseau lors de l\'upload');
        }
    }

    loadImageFromUrl(url) {
        const img = new Image();
        img.onload = () => {
            this.updatePreview(url);
            this.clearUpload();
        };
        img.onerror = () => {
            this.showMessage('Impossible de charger l\'image depuis cette URL', 'error');
            this.clearPreview();
        };
        img.src = url;
    }

    loadImageForCropping(imagePath) {
        this.updatePreview(imagePath);
        this.showCropEditor(imagePath);
    }

    showCropEditor(imagePath) {
        const cropEditor = document.getElementById('avatarCropEditor');
        const cropImage = document.getElementById('cropImage');

        if (!cropEditor || !cropImage) return;

        cropImage.src = imagePath;
        cropEditor.style.display = 'block';

        // Initialiser Cropper.js quand l'image est chargée
        cropImage.onload = () => {
            if (this.cropper) {
                this.cropper.destroy();
            }

            this.cropper = new Cropper(cropImage, {
                aspectRatio: NaN, // Ratio libre
                viewMode: 1,
                dragMode: 'move',
                autoCropArea: 0.8,
                restore: false,
                guides: true,
                center: true,
                highlight: false,
                cropBoxMovable: true,
                cropBoxResizable: true,
                toggleDragModeOnDblclick: false,
                crop: (event) => this.updateCropPreviews(event.detail)
            });
        };
    }

    updateCropPreviews(cropData) {
        const portraitPreview = document.getElementById('cropPreviewPortrait');
        const circlePreview = document.getElementById('cropPreviewCircle');

        if (!portraitPreview || !circlePreview) return;

        const canvas = this.cropper.getCroppedCanvas();
        if (!canvas) return;

        // Prévisualisation portrait (1.2:1)
        const portraitCanvas = document.createElement('canvas');
        const portraitCtx = portraitCanvas.getContext('2d');
        const portraitWidth = 80;
        const portraitHeight = 96;

        portraitCanvas.width = portraitWidth;
        portraitCanvas.height = portraitHeight;
        portraitCtx.drawImage(canvas, 0, 0, portraitWidth, portraitHeight);

        portraitPreview.innerHTML = '';
        portraitPreview.appendChild(portraitCanvas);

        // Prévisualisation circulaire
        const circleCanvas = document.createElement('canvas');
        const circleCtx = circleCanvas.getContext('2d');
        const circleSize = 80;

        circleCanvas.width = circleSize;
        circleCanvas.height = circleSize;

        // Créer un masque circulaire
        circleCtx.beginPath();
        circleCtx.arc(circleSize / 2, circleSize / 2, circleSize / 2, 0, Math.PI * 2);
        circleCtx.closePath();
        circleCtx.clip();

        // Dessiner l'image dans le cercle
        circleCtx.drawImage(canvas, 0, 0, circleSize, circleSize);

        circlePreview.innerHTML = '';
        circlePreview.appendChild(circleCanvas);
    }

    confirmCrop() {
        if (!this.cropper || !this.currentFilename) {
            this.showMessage('Aucune image à recadrer', 'error');
            return;
        }

        const cropData = this.cropper.getData();
        
        // Sauvegarder les données de recadrage
        this.saveCropData(cropData)
            .then(response => {
                if (response.success) {
                    this.showMessage('Avatar recadré avec succès !', 'success');
                    this.hideCropEditor();
                    
                    // Mettre à jour la prévisualisation avec la version portrait
                    if (response.versions && response.versions.portrait) {
                        this.updatePreview('/uploads/avatars/' + response.versions.portrait);
                    }
                } else {
                    this.showMessage(response.error || 'Erreur lors du recadrage', 'error');
                }
            })
            .catch(error => {
                this.showMessage('Erreur lors du recadrage : ' + error.message, 'error');
            });
    }

    async saveCropData(cropData) {
        try {
            // Utiliser l'URL configurée ou fallback vers l'ancienne
            const cropUrl = this.cropUrl || '/characters/avatar/crop';
            const response = await fetch(cropUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    filename: this.currentFilename,
                    cropData: cropData
                })
            });

            return await response.json();
        } catch (error) {
            throw new Error('Erreur réseau lors du recadrage');
        }
    }

    cancelCrop() {
        this.hideCropEditor();
        this.clearUpload();
    }

    hideCropEditor() {
        const cropEditor = document.getElementById('avatarCropEditor');
        if (cropEditor) {
            cropEditor.style.display = 'none';
        }

        if (this.cropper) {
            this.cropper.destroy();
            this.cropper = null;
        }
    }

    showProgress(show) {
        const progress = document.getElementById('uploadProgress');
        if (progress) {
            progress.style.display = show ? 'block' : 'none';
            if (show) {
                // Animation de la barre de progression
                const progressBar = progress.querySelector('.progress-bar');
                if (progressBar) {
                    progressBar.style.width = '0%';
                    setTimeout(() => {
                        progressBar.style.width = '100%';
                    }, 100);
                }
            }
        }
    }

    updatePreview(imagePath) {
        const preview = document.getElementById('avatarPreview');
        if (preview) {
            preview.innerHTML = `<img src="${imagePath}" alt="Avatar preview">`;
            preview.classList.add('upload-success');
            setTimeout(() => {
                preview.classList.remove('upload-success');
            }, 600);
        }
    }

    clearPreview() {
        const preview = document.getElementById('avatarPreview');
        if (preview) {
            preview.innerHTML = '<i class="fas fa-user-circle"></i>';
        }
    }

    clearUpload() {
        this.currentFile = null;
        this.currentFilename = null;
        document.getElementById('avatarFilename').value = '';
        document.getElementById('avatarFileInput').value = '';
        this.hideCropEditor();
    }

    clearUrl() {
        const avatarInput = document.querySelector('input[name*="avatar"]');
        if (avatarInput) {
            avatarInput.value = '';
        }
    }

    showMessage(message, type) {
        // Supprimer les messages existants
        document.querySelectorAll('.avatar-error, .avatar-success').forEach(el => {
            el.remove();
        });

        // Créer le nouveau message
        const messageEl = document.createElement('div');
        messageEl.className = `avatar-${type}`;
        messageEl.textContent = message;

        // Ajouter après la section avatar
        const avatarEditor = document.querySelector('.character-avatar-editor');
        if (avatarEditor) {
            avatarEditor.appendChild(messageEl);

            // Supprimer automatiquement après 5 secondes
            setTimeout(() => {
                messageEl.remove();
            }, 5000);
        }
    }

    getCharacterName() {
        const nameInput = document.querySelector('input[name*="name"]');
        return nameInput ? nameInput.value : 'character';
    }
}

// Initialisation automatique
document.addEventListener('DOMContentLoaded', function() {
    // Vérifier si on est sur une page avec l'éditeur d'avatar
    if (document.querySelector('.character-avatar-editor')) {
        // Charger Cropper.js si pas déjà chargé
        if (typeof Cropper === 'undefined') {
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = 'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.css';
            document.head.appendChild(link);

            const script = document.createElement('script');
            script.src = 'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.js';
            script.onload = () => {
                window.avatarEditor = new AvatarEditor();
            };
            document.head.appendChild(script);
        } else {
            window.avatarEditor = new AvatarEditor();
        }
    }
}); 