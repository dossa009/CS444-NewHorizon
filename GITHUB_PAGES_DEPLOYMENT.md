# GitHub Pages Deployment Guide

## Comment ça fonctionne

Le site utilise un système de chemins dynamiques qui s'adapte automatiquement selon l'environnement:

### 1. **base-path.js** (Chargé en premier)
Ce script s'exécute AVANT tout autre code et:
- Détecte si le site tourne sur GitHub Pages (`github.io`)
- Si oui, injecte une balise `<base href="/CS444-NewHorizon/">` dans le `<head>`
- Cela fait que tous les chemins absolus (`/frontend/...`) deviennent automatiquement `/CS444-NewHorizon/frontend/...`

### 2. **config.js** (Configuration centralisée)
- Détecte l'environnement (dev vs production)
- Configure les chemins API et assets
- Fournit des helpers pour construire des URLs

## Déploiement sur GitHub Pages

### Étape 1: Commit et Push
```bash
git add .
git commit -m "Fix paths for GitHub Pages deployment"
git push origin main
```

### Étape 2: Activer GitHub Pages
1. Va sur https://github.com/dossa009/CS444-NewHorizon
2. Clique sur **Settings**
3. Dans la sidebar, clique sur **Pages**
4. Sous **Source**, sélectionne **main branch**
5. Le dossier doit être **/ (root)**
6. Clique sur **Save**

### Étape 3: Attendre le déploiement
- GitHub prend ~2-5 minutes pour déployer
- Tu recevras un email quand c'est prêt
- Le site sera accessible à: https://dossa009.github.io/CS444-NewHorizon/

## URLs sur GitHub Pages

Une fois déployé, les URLs seront:

- **Home**: https://dossa009.github.io/CS444-NewHorizon/
- **Login**: https://dossa009.github.io/CS444-NewHorizon/frontend/pages/login.html
- **Admin**: https://dossa009.github.io/CS444-NewHorizon/frontend/pages/admin.html
- **Resources**: https://dossa009.github.io/CS444-NewHorizon/frontend/pages/resources.html
- etc.

## Ce qui fonctionne automatiquement

✅ Tous les liens de navigation
✅ Toutes les images
✅ Tous les CSS
✅ Tous les JS
✅ Header et Footer (partials)
✅ Chemins relatifs et absolus

## Limitations sur GitHub Pages

⚠️ **Le backend PHP ne fonctionnera PAS sur GitHub Pages**

GitHub Pages sert uniquement des fichiers statiques (HTML, CSS, JS). Les fonctionnalités qui nécessitent le backend PHP (login, admin, API) ne fonctionneront que en local.

### Solutions pour le backend:

1. **Hébergement PHP séparé**:
   - Déployer le backend sur un serveur PHP (Heroku, AWS, DigitalOcean, etc.)
   - Mettre à jour `config.js` avec l'URL du backend

2. **Développement local uniquement**:
   - Garder GitHub Pages pour la démo frontend
   - Utiliser localhost pour tester les fonctionnalités complètes

## Tester localement avant de pousser

```bash
# Démarrer le serveur PHP local
php -S localhost:8000 router.php

# Ouvrir dans le navigateur
open http://localhost:8000/frontend/index.html
```

## Troubleshooting

### Les images ne se chargent pas
- Vérifie que `base-path.js` est bien chargé en premier
- Ouvre la console (F12) et vérifie les erreurs
- Les chemins d'images doivent utiliser `/frontend/public/assets/...`

### Les CSS ne se chargent pas
- Même solution que pour les images
- Vérifie que les liens CSS utilisent `/frontend/css/...`

### Le header/footer ne s'affichent pas
- Vérifie que `app.js` est chargé
- Les partials doivent être dans `/frontend/partials/`
- Vérifie la console pour les erreurs de chargement

### Les liens de navigation ne fonctionnent pas
- Assure-toi que les liens dans `header.html` utilisent des chemins absolus
- Format: `/frontend/pages/about.html` (pas `pages/about.html`)

## Fichiers importants

- `frontend/js/base-path.js` - Configure le base href pour GitHub Pages
- `frontend/js/config.js` - Configuration centralisée
- `frontend/js/app.js` - Charge les partials
- `add_base_path_script.sh` - Script pour ajouter base-path.js aux HTML
- `fix_github_pages.sh` - Script de migration (obsolète)

## Mettre à jour après modifications

Si tu ajoutes de nouvelles pages HTML:

```bash
# Ajoute base-path.js à la nouvelle page
bash add_base_path_script.sh

# Commit et push
git add .
git commit -m "Add new page"
git push origin main
```

## Structure des chemins

### En local (localhost):
```
http://localhost:8000/frontend/index.html
http://localhost:8000/frontend/css/base.css
http://localhost:8000/frontend/public/assets/img/logo.png
```

### Sur GitHub Pages:
```
https://dossa009.github.io/CS444-NewHorizon/frontend/index.html
https://dossa009.github.io/CS444-NewHorizon/frontend/css/base.css
https://dossa009.github.io/CS444-NewHorizon/frontend/public/assets/img/logo.png
```

Grâce à `base-path.js`, tu n'as PAS besoin de changer les chemins dans le code!
