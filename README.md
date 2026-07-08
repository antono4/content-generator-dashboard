# ⚡ Content Generator All-in-One Dashboard

![Dark Glassmorphism UI](https://img.shields.io/badge/UI-Dark%20Glassmorphism-1a1a2e?style=for-the-badge)
![Laravel](https://img.shields.io/badge/Laravel-PHP-FF2D20?style=for-the-badge&logo=laravel)
![Gemini AI](https://img.shields.io/badge/AI-Google%20Gemini-4285F4?style=for-the-badge&logo=google)
![Three.js](https://img.shields.io/badge/3D-Three.js-000000?style=for-the-badge&logo=three.js)

> AI-powered content generation dashboard for creating compelling video content from start to finish. Features a futuristic Dark Glassmorphism UI with Three.js 3D backgrounds.

## ✨ Features

### 📌 5-Step Content Creation Workflow

| Step | Feature | Description |
|------|---------|-------------|
| 1 | **💡 Title Generator** | Generate catchy, SEO-optimized titles from keywords |
| 2 | **🔧 Auto-Prompt Engineering** | Automatically create text and visual prompts |
| 3 | **🎨 Thumbnail Studio** | Generate professional thumbnails with AI |
| 4 | **✍️ Script Writer** | Create complete video scripts with storyboard |
| 5 | **📊 SEO Optimizer** | Final optimization for algorithm success |

### 🎨 UI/UX Features

- **Dark Glassmorphism Theme** - Frosted glass effects with backdrop blur
- **Three.js 3D Background** - Animated floating geometric shapes
- **Particle Effects** - Floating color-coded particles
- **Glow Effects** - Neon accent lighting
- **Responsive Design** - Works on desktop, tablet, and mobile

## 🚀 Quick Start

### Prerequisites

- PHP 8.1+
- Composer
- Laravel 10+
- Google Gemini API Key

### Installation

```bash
# Clone the repository
git clone https://github.com/antono4/content-generator-dashboard.git
cd content-generator-dashboard

# Install dependencies
composer install

# Copy environment file
cp .env.example .env

# Configure your environment
# Add your Gemini API key in .env:
GEMINI_API_KEY=your_api_key_here

# Generate application key
php artisan key:generate

# Run migrations
php artisan migrate

# Start the server
php artisan serve
```

### Configuration

Add your Google Gemini API key to `.env`:

```env
GEMINI_API_KEY=your_google_ai_api_key
GEMINI_MODEL=gemini-1.5-pro
```

Get your API key from: [Google AI Studio](https://makersuite.google.com/app/apikey)

## 📁 Project Structure

```
content-generator-dashboard/
├── app/
│   ├── Http/Controllers/
│   │   ├── Api/V1/
│   │   │   ├── ContentGeneratorController.php  # Main AI controller
│   │   │   ├── ThumbnailController.php
│   │   │   ├── ScriptController.php
│   │   │   ├── SeoController.php
│   │   │   └── ProjectController.php
│   │   └── Web/DashboardController.php
│   ├── Models/
│   │   ├── Project.php
│   │   ├── GeneratedPrompt.php
│   │   ├── Thumbnail.php
│   │   ├── VideoScript.php
│   │   ├── ScriptScene.php
│   │   └── SeoOptimization.php
│   └── Services/
│       └── GeminiAiService.php
├── database/migrations/
├── routes/
│   ├── api.php          # API endpoints
│   └── web.php          # Web routes
├── resources/views/
│   └── dashboard/
│       └── index.blade.php
└── public/
    ├── css/dashboard.css
    └── index.html       # Standalone demo version
```

## 🗄️ Database Schema

| Table | Description |
|-------|-------------|
| `projects` | Main project with workflow status |
| `generated_prompts` | All AI-generated prompts |
| `thumbnails` | Generated thumbnail images |
| `video_scripts` | Complete video scripts |
| `script_scenes` | Individual storyboard scenes |
| `seo_optimizations` | Final SEO recommendations |

## 🔌 API Endpoints

### Title Generator
```
POST /api/v1/title-generator/generate
POST /api/v1/title-generator/generate-variations
```

### Prompt Engineering
```
POST /api/v1/prompt-engineering/generate-text-prompt
POST /api/v1/prompt-engineering/generate-visual-prompt
POST /api/v1/prompt-engineering/generate-thumbnail-prompt
```

### Thumbnail Generator
```
POST /api/v1/thumbnails/generate
POST /api/v1/thumbnails/generate-batch
GET  /api/v1/thumbnails/{id}
```

### Script Writer
```
POST /api/v1/scripts/generate
GET  /api/v1/scripts/{id}
GET  /api/v1/scripts/{id}/scenes
```

### SEO Optimizer
```
POST /api/v1/seo/optimize
POST /api/v1/seo/generate-tags
GET  /api/v1/seo/{projectId}
```

## 🎨 Standalone Demo

For quick preview without Laravel, open `index.html` directly in your browser:

```bash
open index.html
```

The standalone version includes demo mode with simulated AI responses.

## 🤝 Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📄 License

This project is open source and available under the [MIT License](LICENSE).

## 🙏 Acknowledgments

- [Google Gemini AI](https://deepmind.google/technologies/gemini/) - AI content generation
- [Three.js](https://threejs.org/) - 3D background effects
- [Laravel](https://laravel.com/) - PHP framework

---

⭐ If this project helped you, please give it a star!
