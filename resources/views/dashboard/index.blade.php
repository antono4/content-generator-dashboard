<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Content Generator Pro - Dashboard</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Styles -->
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
    
    <!-- Three.js for 3D Background -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
</head>
<body>
    <!-- Animated Background -->
    <div class="animated-bg"></div>
    <div class="particles">
        <div class="particle" style="left: 10%;"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
    </div>
    <canvas id="three-canvas"></canvas>
    
    <!-- Sidebar Navigation -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <div class="logo-icon">⚡</div>
                <span class="logo-text">ContentGen Pro</span>
            </div>
        </div>
        
        <nav>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="#dashboard" class="nav-link active" data-section="dashboard">
                        <span class="nav-icon">🏠</span>
                        <span class="nav-text">Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#title-generator" class="nav-link" data-section="title-generator">
                        <span class="nav-icon">💡</span>
                        <span class="nav-text">Title Generator</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#prompt-engineering" class="nav-link" data-section="prompt-engineering">
                        <span class="nav-icon">🔧</span>
                        <span class="nav-text">Prompt Engineering</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#thumbnail-studio" class="nav-link" data-section="thumbnail-studio">
                        <span class="nav-icon">🎨</span>
                        <span class="nav-text">Thumbnail Studio</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#script-writer" class="nav-link" data-section="script-writer">
                        <span class="nav-icon">✍️</span>
                        <span class="nav-text">Script Writer</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#seo-optimizer" class="nav-link" data-section="seo-optimizer">
                        <span class="nav-icon">📊</span>
                        <span class="nav-text">SEO Optimizer</span>
                    </a>
                </li>
            </ul>
        </nav>
        
        <div class="sidebar-footer">
            <div class="user-info glass-card" style="padding: 12px;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="width: 36px; height: 36px; background: linear-gradient(135deg, var(--primary-300), var(--accent-purple)); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                        {{ substr(Auth::user()->name ?? 'User', 0, 1) }}
                    </div>
                    <div>
                        <div style="font-size: 14px; font-weight: 600;">{{ Auth::user()->name ?? 'Creator' }}</div>
                        <div style="font-size: 12px; color: var(--text-muted);">Pro Plan</div>
                    </div>
                </div>
            </div>
        </div>
    </aside>
    
    <!-- Main Content -->
    <main class="main-content">
        <!-- Page Header -->
        <header class="page-header">
            <h1 class="page-title">Content Generator Dashboard</h1>
            <p class="page-subtitle">Create compelling video content with AI-powered tools</p>
        </header>
        
        <!-- Workflow Progress -->
        <section class="workflow-container">
            <div class="workflow-step completed" data-step="1">
                <div class="step-number">1</div>
                <div class="step-icon">💡</div>
                <div class="step-title">Title Ideas</div>
                <div class="step-description">Generate catchy titles</div>
            </div>
            <div class="workflow-step completed" data-step="2">
                <div class="step-number">2</div>
                <div class="step-icon">🔧</div>
                <div class="step-title">Auto-Prompt</div>
                <div class="step-description">AI prompt engineering</div>
            </div>
            <div class="workflow-step active" data-step="3">
                <div class="step-number">3</div>
                <div class="step-icon">🎨</div>
                <div class="step-title">Thumbnail</div>
                <div class="step-description">Generate thumbnails</div>
            </div>
            <div class="workflow-step" data-step="4">
                <div class="step-number">4</div>
                <div class="step-icon">✍️</div>
                <div class="step-title">Script</div>
                <div class="step-description">Write video scripts</div>
            </div>
            <div class="workflow-step" data-step="5">
                <div class="step-number">5</div>
                <div class="step-icon">📊</div>
                <div class="step-title">SEO</div>
                <div class="step-description">Optimize for algorithms</div>
            </div>
        </section>
        
        <!-- Generator Sections -->
        <section class="generator-section">
            
            <!-- WORKFLOW 1: Title Idea Generator -->
            <div class="generator-card" id="title-generator-section">
                <div class="generator-card-title">
                    <div class="generator-card-icon">💡</div>
                    Title Idea Generator
                </div>
                <p class="generator-card-desc">
                    Masukkan kata kunci utama dan dapatkan berbagai opsi judul menarik yang dioptimalkan 
                    untuk engagement dan SEO.
                </p>
                
                <div class="input-group">
                    <label class="input-label">Kata Kunci Utama</label>
                    <div class="input-with-icon">
                        <span class="input-icon">🔑</span>
                        <input type="text" id="title-keywords" class="glass-input" 
                               placeholder="Contoh: cara memulai bisnis online">
                    </div>
                </div>
                
                <div class="input-group">
                    <label class="input-label">Platform Target</label>
                    <div class="style-selector">
                        <button class="style-option active" data-platform="youtube">YouTube</button>
                        <button class="style-option" data-platform="tiktok">TikTok</button>
                        <button class="style-option" data-platform="instagram">Instagram</button>
                    </div>
                </div>
                
                <button class="glass-btn glass-btn-glow" id="generate-titles-btn" onclick="generateTitles()">
                    <span>✨</span> Generate Titles
                </button>
                
                <div class="results-area" id="title-results">
                    <div style="text-align: center; color: var(--text-muted); padding: 40px;">
                        <div style="font-size: 48px; margin-bottom: 16px;">📝</div>
                        <p>Masukkan kata kunci dan klik Generate untuk melihat hasil</p>
                    </div>
                </div>
            </div>
            
            <!-- WORKFLOW 2: Auto-Prompt Engineering -->
            <div class="generator-card" id="prompt-engineering-section">
                <div class="generator-card-title">
                    <div class="generator-card-icon">🔧</div>
                    Auto-Prompt Engineering
                </div>
                <p class="generator-card-desc">
                    Sistem AI akan secara otomatis membuat prompt spesifik untuk teks dan visual 
                    berdasarkan judul yang Anda pilih.
                </p>
                
                <div class="input-group">
                    <label class="input-label">Judul yang Dipilih</label>
                    <input type="text" id="selected-title" class="glass-input" 
                           placeholder="Pilih judul dari generator..." readonly>
                </div>
                
                <div class="input-group">
                    <label class="input-label">Tipe Konten</label>
                    <div class="style-selector">
                        <button class="style-option active" data-type="tutorial">Tutorial</button>
                        <button class="style-option" data-type="review">Review</button>
                        <button class="style-option" data-type="vlog">Vlog</button>
                        <button class="style-option" data-type="educational">Educational</button>
                    </div>
                </div>
                
                <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                    <button class="glass-btn" onclick="generateTextPrompt()">
                        <span>📝</span> Generate Text Prompt
                    </button>
                    <button class="glass-btn glass-btn-secondary" onclick="generateVisualPrompt()">
                        <span>🎨</span> Generate Visual Prompt
                    </button>
                </div>
                
                <div class="results-area" id="prompt-results">
                    <div style="text-align: center; color: var(--text-muted); padding: 40px;">
                        <div style="font-size: 48px; margin-bottom: 16px;">⚙️</div>
                        <p>Prompt akan muncul di sini setelah generation</p>
                    </div>
                </div>
            </div>
            
            <!-- WORKFLOW 3: Thumbnail Generator -->
            <div class="generator-card thumbnail-section" id="thumbnail-studio-section">
                <div class="generator-card-title">
                    <div class="generator-card-icon">🎨</div>
                    Thumbnail Studio
                    <span class="badge" style="margin-left: auto; background: linear-gradient(135deg, var(--accent-cyan), var(--accent-green)); padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;">PRO</span>
                </div>
                <p class="generator-card-desc">
                    Hasilkan thumbnail profesional dengan berbagai style menggunakan AI. 
                    Pilih style, generate multiple вариантов, dan pilih yang terbaik.
                </p>
                
                <div class="input-group">
                    <label class="input-label">Visual Prompt</label>
                    <textarea id="thumbnail-prompt" class="glass-input" rows="3" 
                              placeholder="Deskripsikan thumbnail yang Anda inginkan..."></textarea>
                </div>
                
                <div class="input-group">
                    <label class="input-label">Thumbnail Style</label>
                    <div class="style-selector">
                        <button class="style-option active" data-style="bold_text">
                            <span style="margin-right: 6px;">🔤</span> Bold Text
                        </button>
                        <button class="style-option" data-style="minimalist">
                            <span style="margin-right: 6px;">✨</span> Minimalist
                        </button>
                        <button class="style-option" data-style="character_focused">
                            <span style="margin-right: 6px;">👤</span> Character
                        </button>
                        <button class="style-option" data-style="concept_art">
                            <span style="margin-right: 6px;">🎭</span> Concept Art
                        </button>
                        <button class="style-option" data-style="photographic">
                            <span style="margin-right: 6px;">📷</span> Photographic
                        </button>
                    </div>
                </div>
                
                <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                    <button class="glass-btn glass-btn-glow" onclick="generateThumbnails()">
                        <span>🎨</span> Generate Thumbnails
                    </button>
                    <button class="glass-btn glass-btn-secondary" onclick="regenerateThumbnails()">
                        <span>🔄</span> Regenerate All
                    </button>
                </div>
                
                <div class="thumbnail-preview-grid" id="thumbnail-grid">
                    <div class="thumbnail-preview">
                        <div class="thumbnail-placeholder">
                            <div class="thumbnail-placeholder-icon">🖼️</div>
                            <span>Thumbnail 1</span>
                        </div>
                    </div>
                    <div class="thumbnail-preview">
                        <div class="thumbnail-placeholder">
                            <div class="thumbnail-placeholder-icon">🖼️</div>
                            <span>Thumbnail 2</span>
                        </div>
                    </div>
                    <div class="thumbnail-preview">
                        <div class="thumbnail-placeholder">
                            <div class="thumbnail-placeholder-icon">🖼️</div>
                            <span>Thumbnail 3</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- WORKFLOW 4: Script Writer -->
            <div class="generator-card" id="script-writer-section">
                <div class="generator-card-title">
                    <div class="generator-card-icon">✍️</div>
                    Script Writer
                </div>
                <p class="generator-card-desc">
                    Generate naskah video lengkap dengan hook, struktur adegan, B-Roll suggestions, 
                    dan visual direction untuk setiap scene.
                </p>
                
                <div class="input-group">
                    <label class="input-label">Judul Video</label>
                    <input type="text" id="script-title" class="glass-input" 
                           placeholder="Judul untuk script...">
                </div>
                
                <div class="input-group">
                    <label class="input-label">Konteks / Topik</label>
                    <textarea id="script-context" class="glass-input" rows="3" 
                              placeholder="Jelaskan topik atau konteks video..."></textarea>
                </div>
                
                <div class="input-group">
                    <label class="input-label">Tone & Style</label>
                    <div class="style-selector">
                        <button class="style-option active" data-tone="casual">Casual</button>
                        <button class="style-option" data-tone="professional">Professional</button>
                        <button class="style-option" data-tone="humorous">Humorous</button>
                        <button class="style-option" data-tone="serious">Serious</button>
                    </div>
                </div>
                
                <button class="glass-btn glass-btn-glow" onclick="generateScript()" style="width: 100%;">
                    <span>✨</span> Generate Full Script
                </button>
                
                <div class="results-area" id="script-results" style="max-height: 500px;">
                    <div style="text-align: center; color: var(--text-muted); padding: 40px;">
                        <div style="font-size: 48px; margin-bottom: 16px;">📜</div>
                        <p>Script video akan muncul di sini</p>
                    </div>
                </div>
            </div>
            
            <!-- WORKFLOW 5: SEO Optimizer -->
            <div class="generator-card" id="seo-optimizer-section">
                <div class="generator-card-title">
                    <div class="generator-card-icon">📊</div>
                    SEO Optimizer
                </div>
                <p class="generator-card-desc">
                    Optimalkan judul dan tags untuk algoritma platform video. 
                    Dapatkan rekomendasi based on trending topics dan competitor analysis.
                </p>
                
                <div class="input-group">
                    <label class="input-label">Judul Original</label>
                    <input type="text" id="seo-title" class="glass-input" 
                           placeholder="Judul video untuk dioptimasi...">
                </div>
                
                <div class="input-group">
                    <label class="input-label">Deskripsi / Script</label>
                    <textarea id="seo-content" class="glass-input" rows="3" 
                              placeholder="Tempel deskripsi atau script untuk analisis SEO..."></textarea>
                </div>
                
                <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                    <button class="glass-btn" onclick="optimizeSeo()">
                        <span>🚀</span> Optimize SEO
                    </button>
                    <button class="glass-btn glass-btn-secondary" onclick="generateTags()">
                        <span>#️⃣</span> Generate Tags
                    </button>
                </div>
                
                <div class="results-area" id="seo-results">
                    <div style="text-align: center; color: var(--text-muted); padding: 40px;">
                        <div style="font-size: 48px; margin-bottom: 16px;">📈</div>
                        <p>Hasil SEO akan muncul di sini</p>
                    </div>
                </div>
            </div>
            
        </section>
    </main>
    
    <!-- Toast Container -->
    <div class="toast-container" id="toast-container"></div>
    
    <!-- Scripts -->
    <script>
        // CSRF Token
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        
        // API Base URL
        const API_BASE = '/api/v1';
        
        // ============================================
        // THREE.JS 3D BACKGROUND
        // ============================================
        function initThreeBackground() {
            const canvas = document.getElementById('three-canvas');
            const scene = new THREE.Scene();
            const camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
            const renderer = new THREE.WebGLRenderer({ canvas, alpha: true, antialias: true });
            
            renderer.setSize(window.innerWidth, window.innerHeight);
            renderer.setPixelRatio(window.devicePixelRatio);
            
            // Create floating geometric shapes
            const geometry = new THREE.IcosahedronGeometry(1, 0);
            const material = new THREE.MeshBasicMaterial({
                color: 0x6366f1,
                wireframe: true,
                transparent: true,
                opacity: 0.3
            });
            
            const shapes = [];
            for (let i = 0; i < 20; i++) {
                const mesh = new THREE.Mesh(geometry, material.clone());
                mesh.position.x = (Math.random() - 0.5) * 50;
                mesh.position.y = (Math.random() - 0.5) * 50;
                mesh.position.z = (Math.random() - 0.5) * 50;
                mesh.rotation.x = Math.random() * Math.PI;
                mesh.rotation.y = Math.random() * Math.PI;
                mesh.scale.setScalar(Math.random() * 0.5 + 0.1);
                shapes.push(mesh);
                scene.add(mesh);
            }
            
            camera.position.z = 30;
            
            // Mouse interaction
            let mouseX = 0, mouseY = 0;
            document.addEventListener('mousemove', (e) => {
                mouseX = (e.clientX / window.innerWidth) * 2 - 1;
                mouseY = (e.clientY / window.innerHeight) * 2 - 1;
            });
            
            // Animation
            function animate() {
                requestAnimationFrame(animate);
                
                shapes.forEach((shape, i) => {
                    shape.rotation.x += 0.002;
                    shape.rotation.y += 0.003;
                    shape.position.y += Math.sin(Date.now() * 0.001 + i) * 0.01;
                });
                
                camera.position.x += (mouseX * 5 - camera.position.x) * 0.05;
                camera.position.y += (-mouseY * 5 - camera.position.y) * 0.05;
                camera.lookAt(scene.position);
                
                renderer.render(scene, camera);
            }
            
            animate();
            
            // Resize handler
            window.addEventListener('resize', () => {
                camera.aspect = window.innerWidth / window.innerHeight;
                camera.updateProjectionMatrix();
                renderer.setSize(window.innerWidth, window.innerHeight);
            });
        }
        
        // Initialize 3D background
        initThreeBackground();
        
        // ============================================
        // NAVIGATION
        // ============================================
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const section = link.dataset.section;
                
                // Update active nav
                document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
                link.classList.add('active');
                
                // Scroll to section
                const targetSection = document.getElementById(`${section}-section`);
                if (targetSection) {
                    targetSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });
        
        // Style selector click handlers
        document.querySelectorAll('.style-selector').forEach(selector => {
            selector.addEventListener('click', (e) => {
                if (e.target.classList.contains('style-option')) {
                    selector.querySelectorAll('.style-option').forEach(opt => opt.classList.remove('active'));
                    e.target.classList.add('active');
                }
            });
        });
        
        // ============================================
        // API FUNCTIONS
        // ============================================
        
        // Toast notification
        function showToast(message, type = 'info') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            toast.innerHTML = `
                <span class="toast-icon">${type === 'success' ? '✅' : type === 'error' ? '❌' : 'ℹ️'}</span>
                <span class="toast-message">${message}</span>
                <button class="toast-close" onclick="this.parentElement.remove()">✕</button>
            `;
            container.appendChild(toast);
            
            setTimeout(() => toast.remove(), 5000);
        }
        
        // Generate Titles
        async function generateTitles() {
            const keywords = document.getElementById('title-keywords').value.trim();
            if (!keywords) {
                showToast('Masukkan kata kunci terlebih dahulu', 'warning');
                return;
            }
            
            const btn = document.getElementById('generate-titles-btn');
            btn.disabled = true;
            btn.innerHTML = '<span class="loading-dots"><span></span><span></span><span></span></span> Generating...';
            
            try {
                const response = await fetch(`${API_BASE}/title-generator/generate`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ keywords })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    displayTitles(data.data);
                    document.getElementById('selected-title').value = data.data.titles[data.data.recommended_index]?.title || '';
                    showToast('Titles berhasil di-generate!', 'success');
                } else {
                    showToast(data.message || 'Gagal generate titles', 'error');
                }
            } catch (error) {
                showToast('Error: ' + error.message, 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<span>✨</span> Generate Titles';
            }
        }
        
        // Display titles in results area
        function displayTitles(data) {
            const container = document.getElementById('title-results');
            let html = '<div class="results-header" style="margin-bottom: 16px;"><h3 style="color: var(--text-primary);">Generated Titles</h3></div>';
            
            data.titles.forEach((item, index) => {
                const isRecommended = index === data.recommended_index;
                html += `
                    <div class="result-item ${isRecommended ? 'recommended' : ''}" onclick="selectTitle(this, '${item.title.replace(/'/g, "\\'")}')">
                        <div style="display: flex; justify-content: space-between; align-items: start;">
                            <div>
                                <div style="font-weight: 600; color: var(--text-primary);">${item.title}</div>
                                <div style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">
                                    Style: ${item.style} | Viral Score: ${item.viral_score_estimate}%
                                </div>
                            </div>
                            ${isRecommended ? '<span style="background: linear-gradient(135deg, var(--accent-green), var(--accent-cyan)); padding: 2px 8px; border-radius: 10px; font-size: 10px; font-weight: 600;">RECOMMENDED</span>' : ''}
                        </div>
                    </div>
                `;
            });
            
            if (data.reasoning) {
                html += `<div style="margin-top: 16px; padding: 12px; background: rgba(6, 182, 212, 0.1); border-radius: 8px; border-left: 3px solid var(--accent-cyan);">
                    <div style="font-size: 12px; color: var(--accent-cyan); font-weight: 600; margin-bottom: 4px;">AI Reasoning</div>
                    <div style="font-size: 13px; color: var(--text-secondary);">${data.reasoning}</div>
                </div>`;
            }
            
            container.innerHTML = html;
        }
        
        // Select title
        function selectTitle(element, title) {
            document.querySelectorAll('#title-results .result-item').forEach(item => {
                item.classList.remove('selected');
            });
            element.classList.add('selected');
            document.getElementById('selected-title').value = title;
            
            // Also update other fields
            document.getElementById('script-title').value = title;
            document.getElementById('seo-title').value = title;
        }
        
        // Generate Text Prompt
        async function generateTextPrompt() {
            const title = document.getElementById('selected-title').value;
            if (!title) {
                showToast('Pilih judul terlebih dahulu', 'warning');
                return;
            }
            
            showLoading('prompt-results');
            
            try {
                const response = await fetch(`${API_BASE}/prompt-engineering/generate-text-prompt`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ title })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    displayPrompt(data.data, 'text');
                } else {
                    showToast('Gagal generate prompt', 'error');
                }
            } catch (error) {
                showToast('Error: ' + error.message, 'error');
            }
        }
        
        // Generate Visual Prompt
        async function generateVisualPrompt() {
            const title = document.getElementById('selected-title').value;
            if (!title) {
                showToast('Pilih judul terlebih dahulu', 'warning');
                return;
            }
            
            const style = document.querySelector('#prompt-engineering-section .style-option.active')?.dataset.type || 'bold_text';
            showLoading('prompt-results');
            
            try {
                const response = await fetch(`${API_BASE}/prompt-engineering/generate-visual-prompt`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ title, style })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    displayPrompt(data.data, 'visual');
                } else {
                    showToast('Gagal generate prompt', 'error');
                }
            } catch (error) {
                showToast('Error: ' + error.message, 'error');
            }
        }
        
        // Display prompt results
        function displayPrompt(data, type) {
            const container = document.getElementById('prompt-results');
            
            if (type === 'text') {
                container.innerHTML = `
                    <div style="margin-bottom: 20px;">
                        <h4 style="color: var(--accent-cyan); margin-bottom: 8px;">📝 Main Prompt</h4>
                        <div style="background: rgba(0,0,0,0.3); padding: 12px; border-radius: 8px; font-family: monospace; white-space: pre-wrap;">${data.main_prompt}</div>
                    </div>
                    ${data.structure ? `
                        <div style="margin-bottom: 20px;">
                            <h4 style="color: var(--accent-purple); margin-bottom: 8px;">📋 Content Structure</h4>
                            <div style="background: rgba(0,0,0,0.3); padding: 12px; border-radius: 8px;">
                                <div><strong>Introduction:</strong> ${data.structure.introduction}</div>
                                <div style="margin-top: 8px;"><strong>Main Body:</strong> ${data.structure.main_body.join(', ')}</div>
                                <div style="margin-top: 8px;"><strong>Conclusion:</strong> ${data.structure.conclusion}</div>
                            </div>
                        </div>
                    ` : ''}
                    ${data.seo_keywords?.length ? `
                        <div>
                            <h4 style="color: var(--accent-green); margin-bottom: 8px;">🔑 SEO Keywords</h4>
                            <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                                ${data.seo_keywords.map(k => `<span style="background: rgba(16, 185, 129, 0.2); padding: 4px 10px; border-radius: 20px; font-size: 12px;">${k}</span>`).join('')}
                            </div>
                        </div>
                    ` : ''}
                `;
            } else {
                container.innerHTML = `
                    <div style="margin-bottom: 20px;">
                        <h4 style="color: var(--accent-pink); margin-bottom: 8px;">🎨 Image Prompt</h4>
                        <div style="background: rgba(0,0,0,0.3); padding: 12px; border-radius: 8px; font-family: monospace; white-space: pre-wrap;">${data.image_prompt}</div>
                        <button class="glass-btn" style="margin-top: 12px; width: 100%;" onclick="copyToClipboard('${data.image_prompt.replace(/'/g, "\\'")}')">📋 Copy Prompt</button>
                    </div>
                    ${data.color_palette ? `
                        <div style="margin-bottom: 20px;">
                            <h4 style="color: var(--accent-orange); margin-bottom: 8px;">🎨 Color Palette</h4>
                            <div style="display: flex; gap: 12px;">
                                ${Object.entries(data.color_palette).map(([name, color]) => `
                                    <div style="text-align: center;">
                                        <div style="width: 40px; height: 40px; background: ${color}; border-radius: 8px; border: 2px solid rgba(255,255,255,0.2);"></div>
                                        <div style="font-size: 10px; color: var(--text-muted); margin-top: 4px;">${name}</div>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    ` : ''}
                `;
            }
        }
        
        // Generate Thumbnails
        async function generateThumbnails() {
            const prompt = document.getElementById('thumbnail-prompt').value.trim() || document.getElementById('selected-title').value;
            if (!prompt) {
                showToast('Masukkan prompt atau pilih judul terlebih dahulu', 'warning');
                return;
            }
            
            const style = document.querySelector('#thumbnail-studio-section .style-option.active')?.dataset.style || 'bold_text';
            showLoading('thumbnail-grid', true);
            
            try {
                const response = await fetch(`${API_BASE}/thumbnails/generate-batch`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ prompt, style, count: 3 })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    displayThumbnails(data.data.thumbnails);
                    showToast('Thumbnails berhasil di-generate!', 'success');
                } else {
                    showToast('Gagal generate thumbnails', 'error');
                }
            } catch (error) {
                showToast('Error: ' + error.message, 'error');
            }
        }
        
        // Display thumbnails
        function displayThumbnails(thumbnails) {
            const container = document.getElementById('thumbnail-grid');
            
            if (!thumbnails || thumbnails.length === 0) {
                container.innerHTML = `
                    <div class="thumbnail-preview">
                        <div class="thumbnail-placeholder">
                            <div style="font-size: 14px; color: var(--accent-cyan);">Demo Mode</div>
                            <div style="font-size: 12px; margin-top: 8px;">Thumbnail generation requires API configuration</div>
                        </div>
                    </div>
                `;
                return;
            }
            
            container.innerHTML = thumbnails.map((thumb, i) => `
                <div class="thumbnail-preview" onclick="selectThumbnail(this, ${thumb.id})">
                    <img src="${thumb.image_url}" alt="Thumbnail ${i + 1}">
                    <div style="position: absolute; bottom: 8px; left: 8px; right: 8px; display: flex; gap: 4px; opacity: 0;">
                        <button class="glass-btn" style="flex: 1; padding: 6px;" onclick="event.stopPropagation(); downloadThumbnail(${thumb.id})">⬇️</button>
                        <button class="glass-btn" style="flex: 1; padding: 6px;" onclick="event.stopPropagation(); regenerateThumbnail(${thumb.id})">🔄</button>
                    </div>
                </div>
            `).join('');
        }
        
        // Select thumbnail
        function selectThumbnail(element, id) {
            document.querySelectorAll('.thumbnail-preview').forEach(p => p.classList.remove('selected'));
            element.classList.add('selected');
            showToast('Thumbnail dipilih sebagai utama', 'success');
        }
        
        // Generate Script
        async function generateScript() {
            const title = document.getElementById('script-title').value || document.getElementById('selected-title').value;
            const context = document.getElementById('script-context').value;
            
            if (!title) {
                showToast('Masukkan judul video', 'warning');
                return;
            }
            
            showLoading('script-results');
            
            try {
                const response = await fetch(`${API_BASE}/scripts/generate`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ title, context })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    displayScript(data.data);
                    showToast('Script berhasil di-generate!', 'success');
                } else {
                    showToast('Gagal generate script', 'error');
                }
            } catch (error) {
                showToast('Error: ' + error.message, 'error');
            }
        }
        
        // Display script
        function displayScript(data) {
            const container = document.getElementById('script-results');
            
            container.innerHTML = `
                <div style="margin-bottom: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                        <h4 style="color: var(--accent-cyan);">🎬 ${data.title}</h4>
                        <span style="background: rgba(99, 102, 241, 0.2); padding: 4px 12px; border-radius: 20px; font-size: 12px;">
                            ${data.technical?.estimated_duration || '~10 min'}
                        </span>
                    </div>
                    
                    ${data.hook ? `
                        <div style="background: linear-gradient(135deg, rgba(236, 72, 153, 0.2), rgba(168, 85, 247, 0.2)); padding: 12px; border-radius: 8px; border-left: 3px solid var(--accent-pink); margin-bottom: 16px;">
                            <div style="font-size: 11px; color: var(--accent-pink); font-weight: 600; margin-bottom: 4px;">🎯 HOOK</div>
                            <div style="font-size: 14px;">${data.hook}</div>
                        </div>
                    ` : ''}
                    
                    <div style="max-height: 300px; overflow-y: auto;">
                        ${data.segments?.map((seg, i) => `
                            <div style="background: rgba(0,0,0,0.2); padding: 12px; border-radius: 8px; margin-bottom: 8px;">
                                <div style="font-weight: 600; color: var(--primary-100); margin-bottom: 6px;">
                                    📍 ${seg.title || 'Segment ' + (i + 1)}
                                </div>
                                <div style="font-size: 13px; line-height: 1.6;">${seg.script}</div>
                                ${seg.b_roll?.length ? `
                                    <div style="margin-top: 8px; font-size: 11px; color: var(--text-muted);">
                                        🎥 B-Roll: ${seg.b_roll.join(', ')}
                                    </div>
                                ` : ''}
                            </div>
                        `).join('') || '<pre style="white-space: pre-wrap; font-size: 13px;">' + data.script_content + '</pre>'}
                    </div>
                    
                    ${data.conclusion?.cta ? `
                        <div style="background: rgba(16, 185, 129, 0.1); padding: 12px; border-radius: 8px; border-left: 3px solid var(--accent-green); margin-top: 16px;">
                            <div style="font-size: 11px; color: var(--accent-green); font-weight: 600; margin-bottom: 4px;">📢 CALL TO ACTION</div>
                            <div style="font-size: 14px;">${data.conclusion.cta}</div>
                        </div>
                    ` : ''}
                </div>
                
                <button class="glass-btn" style="width: 100%;" onclick="copyToClipboard(\`${(data.script_content || '').replace(/`/g, '\\`').replace(/\$/g, '\\$')}\`)">
                    📋 Copy Full Script
                </button>
            `;
        }
        
        // Optimize SEO
        async function optimizeSeo() {
            const title = document.getElementById('seo-title').value || document.getElementById('selected-title').value;
            const content = document.getElementById('seo-content').value;
            
            if (!title) {
                showToast('Masukkan judul untuk dioptimasi', 'warning');
                return;
            }
            
            showLoading('seo-results');
            
            try {
                const response = await fetch(`${API_BASE}/seo/optimize`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ title, content })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    displaySeoResults(data.data);
                    showToast('SEO berhasil dioptimasi!', 'success');
                } else {
                    showToast('Gagal optimize SEO', 'error');
                }
            } catch (error) {
                showToast('Error: ' + error.message, 'error');
            }
        }
        
        // Display SEO results
        function displaySeoResults(data) {
            const container = document.getElementById('seo-results');
            
            container.innerHTML = `
                <div style="margin-bottom: 20px;">
                    <h4 style="color: var(--accent-green); margin-bottom: 8px;">✅ Optimized Title</h4>
                    <div style="background: rgba(16, 185, 129, 0.1); padding: 12px; border-radius: 8px; font-weight: 600;">
                        ${data.final_title}
                    </div>
                </div>
                
                <div style="margin-bottom: 20px;">
                    <h4 style="color: var(--accent-cyan); margin-bottom: 8px;">#️⃣ Recommended Tags</h4>
                    <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                        ${(data.tags?.all_tags || data.tags || []).slice(0, 15).map(tag => `
                            <span style="background: rgba(6, 182, 212, 0.2); padding: 4px 10px; border-radius: 20px; font-size: 12px; cursor: pointer;" onclick="copyToClipboard('${tag}')">${tag}</span>
                        `).join('')}
                    </div>
                </div>
                
                ${data.optimization?.score ? `
                    <div style="margin-bottom: 20px;">
                        <h4 style="color: var(--accent-purple); margin-bottom: 8px;">📊 SEO Score</h4>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: ${data.optimization.score}%;"></div>
                        </div>
                        <div style="text-align: center; margin-top: 4px; font-size: 12px; color: var(--text-muted);">
                            ${data.optimization.score}% optimized
                        </div>
                    </div>
                ` : ''}
                
                ${data.posting ? `
                    <div>
                        <h4 style="color: var(--accent-orange); margin-bottom: 8px;">⏰ Best Posting Time</h4>
                        <div style="background: rgba(249, 115, 22, 0.1); padding: 12px; border-radius: 8px;">
                            <div style="font-size: 14px;">🕐 ${data.posting.best_time}</div>
                            <div style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">Best days: ${data.posting.best_days?.join(', ')}</div>
                        </div>
                    </div>
                ` : ''}
            `;
        }
        
        // Generate Tags only
        async function generateTags() {
            const title = document.getElementById('seo-title').value;
            if (!title) {
                showToast('Masukkan judul terlebih dahulu', 'warning');
                return;
            }
            
            showLoading('seo-results');
            
            try {
                const response = await fetch(`${API_BASE}/seo/generate-tags`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ title })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    displayTags(data.data);
                } else {
                    showToast('Gagal generate tags', 'error');
                }
            } catch (error) {
                showToast('Error: ' + error.message, 'error');
            }
        }
        
        // Display tags
        function displayTags(data) {
            const container = document.getElementById('seo-results');
            
            container.innerHTML = `
                <h4 style="color: var(--accent-cyan); margin-bottom: 16px;">#️⃣ Generated Tags</h4>
                
                <div style="margin-bottom: 16px;">
                    <div style="font-size: 12px; color: var(--accent-green); margin-bottom: 8px;">Primary Tag</div>
                    <span style="background: linear-gradient(135deg, var(--primary-300), var(--accent-purple)); padding: 8px 16px; border-radius: 20px; font-weight: 600;">${data.primary || data[0]}</span>
                </div>
                
                <div>
                    <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 8px;">All Tags</div>
                    <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                        ${data.map(tag => `
                            <span style="background: rgba(6, 182, 212, 0.15); padding: 4px 12px; border-radius: 20px; font-size: 12px; cursor: pointer; transition: all 0.2s;" 
                                  onmouseover="this.style.background='rgba(6, 182, 212, 0.3)'"
                                  onmouseout="this.style.background='rgba(6, 182, 212, 0.15)'"
                                  onclick="copyToClipboard('${tag}')">${tag}</span>
                        `).join('')}
                    </div>
                </div>
                
                <button class="glass-btn" style="width: 100%; margin-top: 16px;" onclick="copyToClipboard('${data.join(', ')}')">
                    📋 Copy All Tags
                </button>
            `;
        }
        
        // ============================================
        // UTILITY FUNCTIONS
        // ============================================
        
        // Show loading state
        function showLoading(containerId, isGrid = false) {
            const container = document.getElementById(containerId);
            
            if (isGrid) {
                container.innerHTML = [1, 2, 3].map(() => `
                    <div class="thumbnail-preview">
                        <div style="display: flex; align-items: center; justify-content: center; height: 100%;">
                            <div class="loading-spinner"></div>
                        </div>
                    </div>
                `).join('');
            } else {
                container.innerHTML = `
                    <div style="text-align: center; padding: 60px 20px;">
                        <div class="loading-spinner" style="margin: 0 auto 20px;"></div>
                        <p style="color: var(--text-muted);">Generating content...</p>
                        <div class="loading-dots" style="justify-content: center; margin-top: 12px;">
                            <span></span><span></span><span></span>
                        </div>
                    </div>
                `;
            }
        }
        
        // Copy to clipboard
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                showToast('Copied to clipboard!', 'success');
            }).catch(() => {
                showToast('Failed to copy', 'error');
            });
        }
        
        // Regenerate thumbnails
        function regenerateThumbnails() {
            generateThumbnails();
        }
        
        // Download thumbnail
        function downloadThumbnail(id) {
            showToast('Download started...', 'info');
            // Implementation would call API to download
        }
    </script>
</body>
</html>
