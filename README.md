# New Horizon

A simple mental wellness resource hub built with HTML, CSS, and JavaScript.

## Project Structure

```
NewHorizon/
├── index.html          # Home page
├── css/
│   └── base.css        # Main stylesheet
├── js/
│   └── app.js          # Navigation and interactivity
├── pages/
│   ├── listings.html
│   ├── opportunities.html
│   └── contact.html
├── partials/
│   ├── header.html     # Shared header
│   └── footer.html     # Shared footer
├── public/assets/
│   ├── img/            # Images
│   └── fonts/          # Custom fonts
└── template/           # Design references (PDFs)
```

## Running the Project

**IMPORTANT:** You must run the server from the `frontend/` directory:

```bash
# Navigate to frontend directory
cd frontend

# Start server with Python
python3 -m http.server 8000

# OR with Node.js
npx serve
```

Then open `http://localhost:8000` in your browser.

## Pages

- **Home** (index.html) - Fully coded
- **Listings** (pages/listings.html) - Coming soon
- **Opportunities** (pages/opportunities.html) - Coming soon
- **Contact** (pages/contact.html) - Coming soon

## Tech Stack

- HTML5
- CSS3 (no frameworks)
- Vanilla JavaScript
