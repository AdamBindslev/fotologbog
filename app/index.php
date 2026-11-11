<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
?>
<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Logbog App</title>
    
    <!-- PWA Indstillinger -->
    <link rel="manifest" href="manifest.json">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Logbog">
    <meta name="theme-color" content="#242220">
    <link rel="apple-touch-icon" href="../assets/icons/icon-192.png">

    <style>
        :root {
            --bg-color: #faf8f5;
            --card-bg: #ffffff;
            --text-main: #242220;
            --text-muted: #736e65;
            --border-color: #e6e2d8;
            --accent: #242220;
            --accent-active: #000000;
            --accent-red: #b93826;
            --font-mono: 'SFMono-Regular', Consolas, Menlo, monospace;
            --font-sans: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, sans-serif;
            --safe-bottom: env(safe-area-inset-bottom, 0px);
            --safe-top: env(safe-area-inset-top, 0px);
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --bg-color: #161514;
                --card-bg: #211f1d;
                --text-main: #eceae5;
                --text-muted: #99948a;
                --border-color: #33312c;
                --accent: #f0ece1;
                --accent-active: #ffffff;
            }
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-main);
            font-family: var(--font-sans);
            font-size: 16px;
            line-height: 1.5;
            padding-top: calc(var(--safe-top) + 12px);
            padding-bottom: calc(var(--safe-bottom) + 80px);
            min-height: 100vh;
        }

        .container {
            max-width: 520px;
            margin: 0 auto;
            padding: 0 16px;
        }

        /* Top Header */
        .app-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0 16px 0;
            border-bottom: 1px solid var(--border-color);
            margin-bottom: 20px;
        }

        .app-title {
            font-family: var(--font-mono);
            font-size: 15px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .app-header a {
            color: var(--text-muted);
            text-decoration: none;
            font-family: var(--font-mono);
            font-size: 13px;
        }

        /* Views */
        .view-section {
            display: none;
        }
        .view-section.active {
            display: block;
        }

        /* Login Screen */
        .auth-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 28px 20px;
            text-align: center;
            margin-top: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.04);
        }

        .auth-title {
            font-family: var(--font-mono);
            font-size: 16px;
            margin-bottom: 8px;
            letter-spacing: 0.05em;
        }

        .auth-desc {
            font-size: 14px;
            color: var(--text-muted);
            margin-bottom: 20px;
        }

        .pin-input {
            width: 180px;
            margin: 0 auto 16px auto;
            text-align: center;
            font-family: var(--font-mono);
            font-size: 28px;
            letter-spacing: 0.3em;
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background: var(--bg-color);
            color: var(--text-main);
        }

        /* Forms */
        .form-group {
            margin-bottom: 18px;
        }

        .form-label {
            display: block;
            font-family: var(--font-mono);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
            margin-bottom: 6px;
        }

        .form-control {
            width: 100%;
            padding: 12px 14px;
            font-size: 16px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background: var(--card-bg);
            color: var(--text-main);
            font-family: inherit;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--text-main);
        }

        textarea.form-control {
            min-height: 110px;
            resize: vertical;
            line-height: 1.5;
        }

        /* Photo Upload Picker */
        .photo-picker {
            border: 2px dashed var(--border-color);
            border-radius: 12px;
            padding: 24px 16px;
            text-align: center;
            background: var(--card-bg);
            cursor: pointer;
            position: relative;
            transition: border-color 0.15s ease;
        }

        .photo-picker:active {
            border-color: var(--text-main);
        }

        .photo-preview {
            width: 100%;
            max-height: 320px;
            object-fit: cover;
            border-radius: 8px;
            display: none;
            margin-top: 10px;
        }

        .photo-picker-icon {
            font-size: 36px;
            display: block;
            margin-bottom: 8px;
        }

        .photo-picker-text {
            font-family: var(--font-mono);
            font-size: 13px;
            color: var(--text-muted);
        }

        /* Location Button */
        .loc-row {
            display: flex;
            gap: 8px;
        }

        .btn-loc {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 0 14px;
            font-size: 14px;
            font-family: var(--font-mono);
            color: var(--text-main);
            cursor: pointer;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .btn-loc:active {
            background: var(--border-color);
        }

        /* Submit Button */
        .btn-primary {
            width: 100%;
            padding: 15px;
            background: #242220;
            color: #faf8f5;
            border: none;
            border-radius: 10px;
            font-family: var(--font-mono);
            font-size: 15px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .btn-primary:active {
            opacity: 0.85;
            transform: scale(0.99);
        }

        .btn-primary:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Status & Toasts */
        .alert-box {
            padding: 12px;
            border-radius: 8px;
            font-size: 14px;
            font-family: var(--font-mono);
            margin-bottom: 16px;
            display: none;
        }

        .alert-error {
            background: #fdf2f2;
            color: var(--accent-red);
            border: 1px solid #f8b4b4;
        }

        .alert-success {
            background: #f0fdf4;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        /* Bottom Tab Bar */
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: var(--card-bg);
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: space-around;
            padding-top: 10px;
            padding-bottom: calc(var(--safe-bottom) + 10px);
            z-index: 100;
        }

        .tab-btn {
            background: none;
            border: none;
            color: var(--text-muted);
            font-family: var(--font-mono);
            font-size: 11px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            cursor: pointer;
            flex: 1;
        }

        .tab-btn .tab-icon {
            font-size: 20px;
        }

        .tab-btn.active {
            color: var(--text-main);
            font-weight: 700;
        }

        /* Post History List */
        .history-item {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 14px;
            margin-bottom: 12px;
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .history-thumb {
            width: 54px;
            height: 54px;
            border-radius: 6px;
            object-fit: cover;
            background: #eee;
            flex-shrink: 0;
        }

        .history-info {
            flex-grow: 1;
            min-width: 0;
        }

        .history-title {
            font-weight: 600;
            font-size: 15px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .history-date {
            font-family: var(--font-mono);
            font-size: 12px;
            color: var(--text-muted);
        }

        .history-actions {
            display: flex;
            gap: 6px;
        }

        .btn-small {
            padding: 6px 10px;
            border-radius: 6px;
            font-size: 12px;
            border: 1px solid var(--border-color);
            background: var(--bg-color);
            color: var(--text-main);
            cursor: pointer;
        }

        .btn-danger {
            color: var(--accent-red);
            border-color: #f8b4b4;
        }

        /* Settings Card */
        .settings-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 16px;
        }

        .settings-title {
            font-family: var(--font-mono);
            font-size: 14px;
            margin-bottom: 12px;
            text-transform: uppercase;
        }
    </style>
</head>
<body>

<div class="container">

    <!-- Header -->
    <header class="app-header">
        <div class="app-title">SYS.LOG // BACKEND</div>
        <div>
            <a href="<?= BASE_URL ?>/" target="_blank">Vis logbog ↗</a>
        </div>
    </header>

    <!-- Beskeder / Status -->
    <div id="alertBox" class="alert-box"></div>

    <!-- 1. LOGIN SKÆRM -->
    <div id="viewLogin" class="view-section">
        <div class="auth-card">
            <div style="font-size: 40px; margin-bottom: 12px;">🔒</div>
            <h2 class="auth-title">[ ADGANGSKODE ]</h2>
            <p class="auth-desc">Indtast din personlige PIN-kode for at skrive i din logbog.</p>
            
            <form id="loginForm" onsubmit="handleLogin(event)">
                <input 
                    type="password" 
                    id="pinInput" 
                    class="pin-input" 
                    pattern="[0-9]*" 
                    inputmode="numeric" 
                    maxlength="8" 
                    placeholder="••••" 
                    required 
                    autofocus
                >
                <div style="margin-bottom: 18px; font-size: 13px; color: var(--text-muted);">
                    (Standardkode er: <code>1234</code>)
                </div>
                <button type="submit" class="btn-primary" id="loginBtn">Lås op</button>
            </form>
        </div>
    </div>

    <!-- 2. NYT INDLÆG (Hovedskærm) -->
    <div id="viewCompose" class="view-section">
        <form id="postForm" onsubmit="handleCreatePost(event)">
            
            <!-- Billedvælger -->
            <div class="form-group">
                <input 
                    type="file" 
                    id="fileInput" 
                    accept="image/*" 
                    style="display: none;" 
                    onchange="handleImageSelected(event)"
                >
                <div class="photo-picker" id="photoDropArea" onclick="document.getElementById('fileInput').click()">
                    <span class="photo-picker-icon">📷</span>
                    <div class="photo-picker-text" id="photoPickerPrompt">
                        <strong>Tryk her for at tage eller vælge billede</strong><br>
                        (Optimeres automatisk før upload)
                    </div>
                    <img id="imagePreview" class="photo-preview" alt="Preview">
                </div>
                <div id="imageResInfo" style="font-family: var(--font-mono); font-size: 11px; color: var(--text-muted); margin-top: 4px; display: none;"></div>
            </div>

            <!-- Lokation med GPS knap -->
            <div class="form-group">
                <label class="form-label" for="locInput">Lokation</label>
                <div class="loc-row">
                    <input 
                        type="text" 
                        id="locInput" 
                        class="form-control" 
                        placeholder="f.eks. København, Nørrebro"
                    >
                    <button type="button" class="btn-loc" id="gpsBtn" onclick="fetchGPSLocation()">
                        <span id="gpsIcon">📍</span> Hent GPS
                    </button>
                </div>
                <input type="hidden" id="latInput">
                <input type="hidden" id="lonInput">
            </div>

            <!-- Dato & Tid -->
            <div class="form-group">
                <label class="form-label" for="dateInput">Dato & Klokkeslæt</label>
                <input 
                    type="datetime-local" 
                    id="dateInput" 
                    class="form-control" 
                    style="font-family: var(--font-mono); font-size: 14px;"
                >
            </div>

            <!-- Overskrift -->
            <div class="form-group">
                <label class="form-label" for="titleInput">Overskrift</label>
                <input 
                    type="text" 
                    id="titleInput" 
                    class="form-control" 
                    placeholder="En kort overskrift..."
                >
            </div>

            <!-- Tekst / Refleksion -->
            <div class="form-group">
                <label class="form-label" for="contentInput">Noter / Refleksion</label>
                <textarea 
                    id="contentInput" 
                    class="form-control" 
                    placeholder="Dine tanker, betragtninger eller historien bag billedet..."
                ></textarea>
            </div>

            <!-- Gem knap -->
            <button type="submit" class="btn-primary" id="savePostBtn">
                Gem i logbog
            </button>
        </form>
    </div>

    <!-- 3. TIDLIGERE INDLÆG (Historik & Redigering) -->
    <div id="viewHistory" class="view-section">
        <h3 style="font-family: var(--font-mono); font-size: 14px; margin-bottom: 16px; text-transform: uppercase;">
            [ DINE INDLÆG ]
        </h3>
        <div id="postsListContainer">
            <p style="font-family: var(--font-mono); font-size: 13px; color: var(--text-muted);">Indlæser indlæg...</p>
        </div>
    </div>

    <!-- 4. INDSTILLINGER & BACKUP -->
    <div id="viewSettings" class="view-section">
        <div class="settings-card">
            <h3 class="settings-title">[ DATABASE BACKUP ]</h3>
            <p style="font-size: 14px; color: var(--text-muted); margin-bottom: 14px;">
                Download en komplet kopi af din SQLite database (<code>logbog.db</code>) til din telefon eller computer.
            </p>
            <a href="api.php?action=backup" class="btn-small" style="display: inline-block; padding: 10px 16px; font-weight: 600; text-decoration: none;">
                💾 Download database backup
            </a>
        </div>

        <div class="settings-card">
            <h3 class="settings-title">[ SKIFT PIN-KODE ]</h3>
            <form onsubmit="handleUpdatePin(event)">
                <div class="form-group">
                    <label class="form-label">Ny PIN-kode (min. 4 tegn)</label>
                    <input type="password" id="newPinInput" class="form-control" required placeholder="Ny PIN">
                </div>
                <button type="submit" class="btn-small" style="padding: 10px 16px;">Opdater PIN</button>
            </form>
        </div>

        <div class="settings-card">
            <h3 class="settings-title">[ LOG UD ]</h3>
            <button onclick="handleLogout()" class="btn-small btn-danger" style="padding: 10px 16px;">
                Log ud af appen
            </button>
        </div>
    </div>

</div>

<!-- Bottom Navigation Bar -->
<nav class="bottom-nav" id="bottomNav" style="display: none;">
    <button class="tab-btn active" onclick="switchView('compose')">
        <span class="tab-icon">📷</span>
        <span>Nyt</span>
    </button>
    <button class="tab-btn" onclick="switchView('history')">
        <span class="tab-icon">📋</span>
        <span>Indlæg</span>
    </button>
    <button class="tab-btn" onclick="switchView('settings')">
        <span class="tab-icon">⚙️</span>
        <span>Indstillinger</span>
    </button>
</nav>

<script>
// PWA Service Worker registrering
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('sw.js').catch(err => console.log('SW reg error:', err));
}

let compressedImageData = null;

// Initialiser standard dato
function setDefaultDate() {
    const now = new Date();
    // Format: YYYY-MM-DDTHH:MM
    const localIso = new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().slice(0, 16);
    document.getElementById('dateInput').value = localIso;
}

// Vis besked
function showAlert(message, type = 'error', duration = 4000) {
    const box = document.getElementById('alertBox');
    box.className = `alert-box alert-${type}`;
    box.textContent = message;
    box.style.display = 'block';
    if (duration > 0) {
        setTimeout(() => {
            box.style.display = 'none';
        }, duration);
    }
}

// Skift visning
function switchView(viewName) {
    document.querySelectorAll('.view-section').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));

    if (viewName === 'login') {
        document.getElementById('viewLogin').classList.add('active');
        document.getElementById('bottomNav').style.display = 'none';
    } else if (viewName === 'compose') {
        document.getElementById('viewCompose').classList.add('active');
        document.getElementById('bottomNav').style.display = 'flex';
        document.querySelectorAll('.tab-btn')[0].classList.add('active');
    } else if (viewName === 'history') {
        document.getElementById('viewHistory').classList.add('active');
        document.getElementById('bottomNav').style.display = 'flex';
        document.querySelectorAll('.tab-btn')[1].classList.add('active');
        loadPostsHistory();
    } else if (viewName === 'settings') {
        document.getElementById('viewSettings').classList.add('active');
        document.getElementById('bottomNav').style.display = 'flex';
        document.querySelectorAll('.tab-btn')[2].classList.add('active');
    }
    window.scrollTo(0, 0);
}

// Tjek om brugeren er autentificeret
async function checkAuthStatus() {
    const savedToken = localStorage.getItem('logbog_token');
    const headers = {};
    if (savedToken) {
        headers['Authorization'] = 'Bearer ' + savedToken;
    }

    try {
        const res = await fetch('api.php?action=check_auth', { headers });
        const data = await res.json();
        if (data.authenticated) {
            switchView('compose');
            setDefaultDate();
        } else {
            switchView('login');
        }
    } catch (e) {
        switchView('login');
    }
}

// Login håndtering
async function handleLogin(e) {
    e.preventDefault();
    const pin = document.getElementById('pinInput').value;
    const btn = document.getElementById('loginBtn');
    btn.disabled = true;
    btn.textContent = 'Tjekker...';

    try {
        const res = await fetch('api.php?action=login', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ pin })
        });
        const data = await res.json();

        if (res.ok && data.success) {
            localStorage.setItem('logbog_token', data.token);
            showAlert('Logget ind!', 'success', 2000);
            document.getElementById('pinInput').value = '';
            switchView('compose');
            setDefaultDate();
        } else {
            showAlert(data.error || 'Forkert PIN-kode', 'error');
        }
    } catch (err) {
        showAlert('Forbindelsesfejl. Prøv igen.', 'error');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Lås op';
    }
}

// Billedbehandling & Klient-optimering (Canvas)
function handleImageSelected(e) {
    const file = e.target.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = function(event) {
        const img = new Image();
        img.onload = function() {
            // Skaler billedet ned til max 2048px for lynhurtig upload
            const maxDimension = 2048;
            let width = img.width;
            let height = img.height;

            if (width > maxDimension || height > maxDimension) {
                if (width > height) {
                    height = Math.round((height * maxDimension) / width);
                    width = maxDimension;
                } else {
                    width = Math.round((width * maxDimension) / height);
                    height = maxDimension;
                }
            }

            const canvas = document.createElement('canvas');
            canvas.width = width;
            canvas.height = height;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(img, 0, 0, width, height);

            // Komprimer til JPEG med 85% kvalitet
            compressedImageData = canvas.toDataURL('image/jpeg', 0.85);

            // Vis preview
            const preview = document.getElementById('imagePreview');
            preview.src = compressedImageData;
            preview.style.display = 'block';
            document.getElementById('photoPickerPrompt').style.display = 'none';

            // Beregn størrelse
            const approxSizeKb = Math.round((compressedImageData.length * 3/4) / 1024);
            const info = document.getElementById('imageResInfo');
            info.textContent = `Optimeret til: ${width}x${height}px (~${approxSizeKb} KB)`;
            info.style.display = 'block';
        };
        img.src = event.target.result;
    };
    reader.readAsDataURL(file);
}

// Hent GPS Lokation & Reverse Geocoding
function fetchGPSLocation() {
    const btn = document.getElementById('gpsBtn');
    const icon = document.getElementById('gpsIcon');
    const locInput = document.getElementById('locInput');

    if (!navigator.geolocation) {
        showAlert('GPS understøttes ikke af din browser', 'error');
        return;
    }

    icon.textContent = '⏳';
    btn.disabled = true;

    navigator.geolocation.getCurrentPosition(
        async (position) => {
            const lat = position.coords.latitude;
            const lon = position.coords.longitude;
            document.getElementById('latInput').value = lat;
            document.getElementById('lonInput').value = lon;

            try {
                // Hent by/område via OpenStreetMap Nominatim
                const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lon}&zoom=14&addressdetails=1`, {
                    headers: { 'Accept-Language': 'da, en' }
                });
                const data = await res.json();
                
                if (data && data.address) {
                    const addr = data.address;
                    const city = addr.city || addr.town || addr.village || addr.municipality || '';
                    const district = addr.suburb || addr.neighbourhood || addr.quarter || '';
                    
                    let locName = '';
                    if (city && district && city !== district) {
                        locName = `${city}, ${district}`;
                    } else {
                        locName = city || district || 'Danmark';
                    }
                    locInput.value = locName;
                    showAlert(`📍 Lokation fundet: ${locName}`, 'success', 2500);
                } else {
                    locInput.value = `${lat.toFixed(4)}, ${lon.toFixed(4)}`;
                }
            } catch (err) {
                locInput.value = `${lat.toFixed(4)}, ${lon.toFixed(4)}`;
            } finally {
                icon.textContent = '📍';
                btn.disabled = false;
            }
        },
        (error) => {
            icon.textContent = '📍';
            btn.disabled = false;
            showAlert('Kunne ikke hente GPS: ' + error.message, 'error');
        },
        { enableHighAccuracy: true, timeout: 10000 }
    );
}

// Opret nyt indlæg
async function handleCreatePost(e) {
    e.preventDefault();

    if (!compressedImageData) {
        showAlert('Vælg eller tag venligst et billede først', 'error');
        return;
    }

    const saveBtn = document.getElementById('savePostBtn');
    saveBtn.disabled = true;
    saveBtn.textContent = 'Gemmer i logbog...';

    const payload = {
        title: document.getElementById('titleInput').value,
        content: document.getElementById('contentInput').value,
        location: document.getElementById('locInput').value,
        latitude: document.getElementById('latInput').value,
        longitude: document.getElementById('lonInput').value,
        published_at: document.getElementById('dateInput').value,
        image_data: compressedImageData
    };

    const token = localStorage.getItem('logbog_token');

    try {
        const res = await fetch('api.php?action=create_post', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + token
            },
            body: JSON.stringify(payload)
        });
        const data = await res.json();

        if (res.ok && data.success) {
            showAlert('✓ Dit indlæg er gemt i logbogen!', 'success', 4000);
            
            // Nulstil formularen
            document.getElementById('postForm').reset();
            compressedImageData = null;
            document.getElementById('imagePreview').style.display = 'none';
            document.getElementById('photoPickerPrompt').style.display = 'block';
            document.getElementById('imageResInfo').style.display = 'none';
            setDefaultDate();
        } else {
            showAlert(data.error || 'Fejl under gemning', 'error');
        }
    } catch (err) {
        showAlert('Netværksfejl under upload', 'error');
    } finally {
        saveBtn.disabled = false;
        saveBtn.textContent = 'Gem i logbog';
    }
}

// Hent liste af indlæg til historik/redigering
async function loadPostsHistory() {
    const container = document.getElementById('postsListContainer');
    container.innerHTML = '<p style="font-family: var(--font-mono); font-size: 13px; color: var(--text-muted);">Henter indlæg...</p>';

    const token = localStorage.getItem('logbog_token');
    try {
        const res = await fetch('api.php?action=get_posts', {
            headers: { 'Authorization': 'Bearer ' + token }
        });
        const data = await res.json();

        if (res.ok && data.posts) {
            if (data.posts.length === 0) {
                container.innerHTML = '<p style="font-family: var(--font-mono); font-size: 13px; color: var(--text-muted);">Ingen indlæg oprettet endnu.</p>';
                return;
            }

            let html = '';
            data.posts.forEach(p => {
                const imgUrl = '../uploads/' + p.image_path;
                const dateStr = p.published_at.substring(0, 10);
                html += `
                    <div class="history-item" id="hist-${p.id}">
                        <img src="${imgUrl}" class="history-thumb" alt="">
                        <div class="history-info">
                            <div class="history-title">${p.title}</div>
                            <div class="history-date">[${dateStr}] ${p.location ? '• ' + p.location : ''}</div>
                        </div>
                        <div class="history-actions">
                            <a href="../post.php?id=${p.id}" target="_blank" class="btn-small" style="text-decoration:none;">Vis</a>
                            <button onclick="deletePost(${p.id})" class="btn-small btn-danger">Slet</button>
                        </div>
                    </div>
                `;
            });
            container.innerHTML = html;
        }
    } catch (e) {
        container.innerHTML = '<p style="color: var(--accent-red);">Kunne ikke hente indlæg.</p>';
    }
}

// Slet indlæg
async function deletePost(id) {
    if (!confirm('Er du sikker på, at du vil slette dette indlæg? Billedet slettes også.')) {
        return;
    }

    const token = localStorage.getItem('logbog_token');
    try {
        const res = await fetch('api.php?action=delete_post', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + token
            },
            body: JSON.stringify({ id })
        });
        const data = await res.json();
        if (res.ok && data.success) {
            showAlert('Indlæg slettet', 'success', 2000);
            const el = document.getElementById('hist-' + id);
            if (el) el.remove();
        } else {
            showAlert(data.error || 'Fejl under sletning', 'error');
        }
    } catch (e) {
        showAlert('Netværksfejl', 'error');
    }
}

// Opdater PIN
async function handleUpdatePin(e) {
    e.preventDefault();
    const newPin = document.getElementById('newPinInput').value;
    const token = localStorage.getItem('logbog_token');

    try {
        const res = await fetch('api.php?action=update_pin', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + token
            },
            body: JSON.stringify({ new_pin: newPin })
        });
        const data = await res.json();
        if (res.ok && data.success) {
            showAlert('✓ PIN-koden er ændret!', 'success', 3000);
            document.getElementById('newPinInput').value = '';
        } else {
            showAlert(data.error || 'Fejl under ændring', 'error');
        }
    } catch (e) {
        showAlert('Netværksfejl', 'error');
    }
}

// Log ud
async function handleLogout() {
    try {
        await fetch('api.php?action=logout');
    } catch (e) {}
    localStorage.removeItem('logbog_token');
    switchView('login');
}

// Kør ved start
window.addEventListener('DOMContentLoaded', () => {
    checkAuthStatus();
});
</script>

</body>
</html>
