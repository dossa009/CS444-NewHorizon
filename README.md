# New Horizon 🌅

A mental wellness resource hub providing accessible tools and community support for everyone seeking help.

**University Project** - CSUSM Web Programming Course

---

## 📋 Project Overview

New Horizon is a student-built platform designed to offer:
- Mental wellness exercises and resources
- Community forum for peer support
- Event calendar for wellness activities
- Educational content about mental health
- Private and accessible tools for everyone

---

## 🏗️ Project Structure

```
New Horizon/
├── frontend/                    # Frontend application
│   ├── index.html              # Home page (fully coded)
│   ├── css/
│   │   ├── base.css            # Global styles & design system
│   │   ├── home.css            # Home page styles
│   │   ├── opportunities.css   # Opportunities page styles
│   │   ├── contact.css         # Contact page styles
│   │   ├── exercises.css       # Exercises page (structure ready)
│   │   ├── calendar.css        # Calendar page (structure ready)
│   │   ├── forum.css           # Forum page (structure ready)
│   │   ├── about.css           # About/Mission page (structure ready)
│   │   └── account.css         # Account page (structure ready)
│   ├── js/
│   │   └── app.js              # Navigation & partial injection
│   ├── pages/
│   │   ├── opportunities.html  # Opportunities page (fully coded)
│   │   ├── contact.html        # Contact page (fully coded)
│   │   ├── exercises.html      # Coming soon
│   │   ├── calendar.html       # Coming soon
│   │   ├── forum.html          # Coming soon
│   │   ├── about.html          # Coming soon (Mission)
│   │   └── account.html        # Coming soon
│   ├── partials/
│   │   ├── header.html         # Shared navigation header
│   │   └── footer.html         # Shared footer
│   └── public/assets/
│       ├── img/                # Images from design templates
│       └── fonts/              # Custom fonts (if needed)
├── backend/                     # Backend (planned for future)
│   └── README.md               # Backend structure placeholder
└── template/                    # Design references (PDFs)
    ├── Home.pdf
    ├── Opportunities.pdf
    └── Contact.pdf
```

---

## 🚀 Running the Project

**⚠️ IMPORTANT:** You **must** run the server from the `frontend/` directory!

### Step 1: Navigate to frontend
```bash
cd frontend
```

### Step 2: Start the server

**Option A - Python (recommended)**
```bash
python3 -m http.server 8000
```

**Option B - Node.js**
```bash
npx serve
```

### Step 3: Open in browser
Open your browser and go to:
```
http://localhost:8000
```

**❌ DO NOT** open files directly (file://...) - JavaScript features require HTTP server!

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

## 🛠️ Tech Stack

- **HTML5** - Semantic markup
- **CSS3** - Custom styling (no frameworks)
- **Vanilla JavaScript** - Navigation & dynamic loading
- **Python HTTP Server** - Local development

**No dependencies**

---

## 📝 Notes

- **Educational Purpose**: This is a student project for learning web development
- **Not Medical Advice**: This site is for educational purposes only
- **Accessibility**: Priority focus on inclusive design
- **Privacy**: No data collection or tracking

---

**Last Updated**: October 26, 2025
