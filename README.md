# New Horizon 🌅

A mental wellness resource hub providing accessible tools and community support for everyone seeking help.

**University Project** - CSUSM Web Programming Course

**Live Site**: https://dossa009.github.io/CS444-NewHorizon/

---

## 📋 Project Overview

New Horizon is a student-built platform designed to offer:
- Mental wellness exercises and resources
- Community forum for peer support
- Event calendar for wellness activities
- Educational content about mental health
- Private and accessible tools for everyone

---

## 🚀 Quick Start

### Local Development

To run the site locally:

```bash
python3 serve.py
```

The site will be available at: **http://localhost:8000**

### Automatic Deployment

The site is automatically deployed to GitHub Pages on every push to the `main` branch.

Production URL: **https://dossa009.github.io/CS444-NewHorizon/**

---

## 🏗️ Project Structure

```
New Horizon/
├── .github/
│   └── workflows/
│       └── deploy.yml          # CI/CD GitHub Actions configuration
├── frontend/                    # Frontend application
│   ├── index.html              # Home page (fully coded)
│   ├── css/
│   │   ├── base.css            # Global styles & design system
│   │   ├── home.css            # Home page styles
│   │   ├── opportunities.css   # Opportunities page styles
│   │   ├── contact.css         # Contact page styles
│   │   ├── exercises.css       # Exercises page styles
│   │   ├── calendar.css        # Calendar page styles
│   │   ├── forum.css           # Forum page styles
│   │   ├── about.css           # About/Mission page styles
│   │   └── account.css         # Account page styles
│   ├── js/
│   │   ├── app.js              # Navigation & partial injection
│   │   └── config.js           # Base path configuration (auto-generated)
│   ├── pages/
│   │   ├── opportunities.html  # Opportunities page
│   │   ├── contact.html        # Contact page
│   │   ├── exercises.html      # Exercises page
│   │   ├── calendar.html       # Calendar page
│   │   ├── forum.html          # Forum page
│   │   ├── about.html          # About/Mission page
│   │   └── account.html        # Account page
│   ├── partials/
│   │   ├── header.html         # Shared navigation header
│   │   └── footer.html         # Shared footer
│   └── public/assets/
│       └── img/                # Images from design templates
├── backend/                     # Backend (planned for future)
│   └── README.md               # Backend structure placeholder
├── serve.py                     # Local development server
├── fix-paths.py                 # Path configuration injection script
└── README.md                    # This file
```

---

## 🔧 How It Works

### Path Management (Local vs Production)

The site uses an intelligent path management system:

- **Local**: Paths are relative to root `/`
- **Production**: Paths are relative to `/CS444-NewHorizon/`

An automatic script detects the environment and injects the correct base path:

```javascript
// This code is automatically injected into each HTML page
const isLocal = window.location.hostname === 'localhost';
const basePath = isLocal ? '/' : '/CS444-NewHorizon/';
```

### CI/CD Pipeline

GitHub Actions workflow (`.github/workflows/deploy.yml`):

1. **Trigger**: Push or merge to `main`
2. **Checkout**: Fetch the code
3. **Setup Pages**: Configure GitHub Pages
4. **Upload**: Send the `frontend/` folder
5. **Deploy**: Deploy to GitHub Pages

The deployment takes ~1-2 minutes and can be monitored in the Actions tab.

---

## 🛠️ Development Commands

### Start local server
```bash
python3 serve.py
```

### Re-inject configuration script (if needed)
```bash
python3 fix-paths.py
```

### Deploy to GitHub Pages
```bash
git add .
git commit -m "Your commit message"
git push origin main
```

The deployment will start automatically!

---

## 📝 Adding New Pages

When creating a new HTML file:

1. Create your file in `frontend/` or `frontend/pages/`
2. Use absolute paths (starting with `/`) for resources:
   ```html
   <link rel="stylesheet" href="/css/base.css">
   <img src="/public/assets/img/photo.png">
   <a href="/pages/about.html">About</a>
   ```
3. Run the configuration script:
   ```bash
   python3 fix-paths.py
   ```
4. The script will automatically add the path management code

---

## 🎨 Design System

### Color Palette
```css
--cream: #F5E6D3      /* Light background */
--beige: #E8D5C4      /* Secondary background */
--terracotta: #B85C38 /* Primary accent */
--dark-brown: #5C3D2E /* Headings */
--text-dark: #3A3A3A  /* Body text */
--text-light: #6B6B6B /* Muted text */
--white: #FFFFFF      /* Pure white */
```

### Typography
- **Headings**: Georgia, serif
- **Body**: Helvetica Neue, Arial, sans-serif

### Naming Convention
All CSS classes use **page-prefix** naming:
- Home: `home-hero`, `home-pillars`, etc.
- Exercises: `exercises-card`, `exercises-grid`, etc.
- Calendar: `calendar-header`, `calendar-day`, etc.

---

## 👥 Team Guidelines

### For Developers Working on Pages

1. **Find your assigned page** in `frontend/pages/`
2. **Open the corresponding CSS** in `frontend/css/`
3. **Use existing classes** as a starting point (they're already structured!)
4. **Follow the naming convention**: `[page]-[element]`
5. **Test locally** using the HTTP server
6. **Keep the design consistent** with the color palette

### Git Workflow (if using version control)
```bash
# Create a branch for your page
git checkout -b feature/calendar-page

# Make your changes
git add .
git commit -m "feat: implement calendar page"

# Push and create PR
git push origin feature/calendar-page
```

---

## 🐛 Troubleshooting

### CSS/images not loading locally
- Make sure you're using `python3 serve.py` and not opening files directly
- Check that you're accessing `http://localhost:8000`

### Site not working on GitHub Pages
- Check that the GitHub Actions workflow succeeded (Actions tab)
- Verify GitHub Pages is configured with source "GitHub Actions"
- Wait 1-2 minutes after push (deployment time)

### New HTML files not working
- Run `python3 fix-paths.py` to inject the configuration code

---

## 📊 Deployment Monitoring

Track deployment status here: **https://github.com/dossa009/CS444-NewHorizon/actions**

Each push to `main` creates a new deployment workflow.

---

## 🛠️ Tech Stack

- **HTML5** - Semantic markup
- **CSS3** - Custom styling (no frameworks)
- **Vanilla JavaScript** - Navigation & dynamic loading
- **Python HTTP Server** - Local development
- **GitHub Actions** - CI/CD automation
- **GitHub Pages** - Hosting

**No build dependencies required**

---

## 📝 Notes

- **Educational Purpose**: This is a student project for learning web development
- **Not Medical Advice**: This site is for educational purposes only
- **Accessibility**: Priority focus on inclusive design
- **Privacy**: No data collection or tracking

---

**Last Updated**: October 28, 2025
