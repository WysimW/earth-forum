# Système de Design d'Earth Forum

## Identité visuelle

Le système de design d'Earth Forum est construit autour d'une esthétique moderne, sombre et immersive avec des accents bleus lumineux. L'interface utilise des effets de transparence, de flou et de lueur pour créer une ambiance futuriste et élégante.

## Palette de couleurs

### Couleurs principales
- **Bleu foncé** (`--primary-dark: #0C2D48`) - Couleur de fond principale
- **Bleu medium** (`--primary-medium: #145DA0`) - Utilisé pour les éléments interactifs
- **Bleu clair** (`--primary-light: #2980b9`) - Accents et survols
- **Noir profond** (`--deep-black: #0A0A0A`) - Arrière-plans secondaires
- **Gris clair** (`--light-gray: #DCE1E3`) - Texte secondaire
- **Blanc** (`--text-white: #FFFFFF`) - Texte principal

### Accents et états
- **Accent bleu** (`--accent-blue: #0476F2`) - Actions principales
- **Accent clair** (`--accent-light: #2588F5`) - Éléments interactifs secondaires
- **Avertissement** (`--warning: #f39c12`) - Notifications et alertes
- **Danger** (`--danger: #e74c3c`) - Erreurs et actions destructives
- **Info** (`--info: #3498db`) - Informations et conseils

### Sections spéciales

#### Roleplay (RP)
- Couleurs dans les tons bleus (`--roleplay-primary: #0476F2`)
- Effets de lueur bleutés

#### Hors Roleplay (HRP)
- Couleurs dans les tons verts (`--hrp-primary: #015f40`)
- Effets de lueur verdâtres

#### Messages importants
- Couleurs dans les tons rouges (`--important-primary: #BB070E`)
- Effets de lueur rougeâtres

## Typographie

### Polices principales
- **Corps de texte**: 'Inter', sans-serif
- **Titres**: 'Bebas Neue', sans-serif (en majuscules)
- **Texte spécial**: 'Bebas Neue', sans-serif
- **Monospace**: 'Roboto Mono', monospace

### Polices pour l'éditeur de texte
Support de polices différentes pour les dialogues des personnages:
- Arial
- Times New Roman
- Courier New
- Georgia
- Trebuchet MS
- Verdana

## Composants

### Boutons
- Style moderne avec effet de survol subtil
- Effet de lueur au survol
- Variantes primaire, succès, outline
- Police spéciale et texte en majuscules

### Cartes
- Fond semi-transparent
- Effets de survol multiples (lift, glow, shine)
- Bordures fines avec accents colorés

### Forums et discussions
- Distinction visuelle claire entre les sections RP et HRP
- Badges et indicateurs pour les statuts de discussion
- Système d'icônes cohérent

### Mise en page
- Grille à trois zones: header, sidebar, content
- En-tête fixe avec effet de flou (backdrop-filter)
- Barre latérale stylisée avec menu de navigation
- Contenu principal avec arrière-plan texturé

## Effets visuels

### Arrière-plans
- Arrière-plan principal dégradé avec motif subtil
- Filigrane en fond
- Effet de flou pour les éléments superposés

### Effets de profondeur
- Ombres douces
- Effets de survol élaborés
- Effets de lueur pour les éléments actifs

### Animations
- Transitions douces
- Animations de pulsation pour les notifications
- Effets de brillance sur les éléments interactifs

## Design responsive

- Points de rupture standards (576px, 768px, 992px, 1200px)
- Adaptations spécifiques pour les affichages mobiles
- Réorganisation des éléments pour une meilleure expérience sur petit écran

## Composants spéciaux

### Fiches de personnages
- Mise en page structurée avec sections distinctes
- En-tête de personnage avec informations clés
- Indicateurs de statut (validé, en attente, etc.)

### Éditeur de texte
- Personnalisation des polices pour le roleplay
- Support des styles spécifiques aux personnages
- Design cohérent avec l'interface du forum

## Organisation du CSS

### Structure des fichiers
- **variables.css** - Toutes les variables CSS globales
- **main.css** - Styles de base et importations
- **layout.css** - Structure de mise en page principale
- **typography.css** - Styles de texte et polices
- **buttons.css** - Boutons et contrôles interactifs
- **forums.css** - Composants pour les forums et discussions
- **cards.css** - Styles pour les différents types de cartes

### Conventions de nommage
- Classes thématiques cohérentes : `.rp-theme`, `.hrp-theme`, `.important-theme`
- Classes utilitaires (`hover-lift`, `hover-shine`) pour les effets réutilisables 
- Variables avec préfixes sémantiques (`--primary-`, `--roleplay-`, `--hrp-`)

## Améliorations techniques

### Optimisation du CSS
- Variables pour les valeurs RGB facilitant les opérations de transparence
- Élimination des styles redondants pour les polices d'éditeur
- Utilisation de variables CSS composées pour la réutilisation
- Simplification des sélecteurs pour améliorer les performances
- Consolidation des effets de survol en classes utilitaires 