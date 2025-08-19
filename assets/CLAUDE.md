# Assets Folder - Static Resources

## Purpose
Contains all static assets for the horizn_ analytics platform including CSS, JavaScript, and images.

## Rules
- **Crypto/SaaS Aesthetic**: Dark mode first with sharp edges, subtle glows
- **Google Fonts**: Use Google Fonts for typography, monospace for data
- **Minification**: All files should be minified for production
- **Version Control**: Use cache-busting for updated assets
- **No Inline Styles**: Keep all CSS in dedicated files

## Design System

### Typography
```css
/* Google Fonts imports */
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
@import url('https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@300;400;500;600&display=swap');

/* Text: Inter font family */
/* Data/Numbers: JetBrains Mono (monospace) */
```

### Color Scheme
- **Dark Mode Primary**: Deep blacks (#0a0a0a, #1a1a1a)
- **Light Accents**: Subtle grays (#2a2a2a, #3a3a3a)
- **Accent Colors**: Blue/purple with subtle glow effects
- **Text**: White/light gray with high contrast

### Component Style
- **Sharp edges**: Minimal border-radius (2-4px max)
- **Subtle shadows**: Use glows instead of drop shadows
- **Clean lines**: Geometric, professional appearance
- **Consistent spacing**: Use 8px grid system

## Folder Structure
```
/css/
  main.css           # Core styles and variables
  dashboard.css      # Dashboard-specific styles
  auth.css          # Login/authentication styles
  components.css    # Reusable component styles

/js/
  app.js            # Main application JavaScript
  tracking.js       # Ad-blocker resistant tracking
  dashboard.js      # Dashboard functionality
  charts.js         # Chart visualization code

/img/
  logo.svg          # horizn_ logo
  icons/            # UI icons
  placeholder/      # Placeholder images
```

## Primary Agents
- ui-designer
- frontend-developer
- whimsy-injector
- performance-benchmarker

## Performance Requirements
- CSS files < 50KB each when minified
- JavaScript files < 100KB each when minified
- Images optimized and properly sized
- Use modern image formats (WebP, AVIF) with fallbacks

## Ad-blocker Considerations
- tracking.js must be disguised and lightweight
- No obvious analytics-related naming
- Multiple fallback methods implemented
- First-party domain requests only