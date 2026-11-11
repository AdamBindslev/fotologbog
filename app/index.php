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

    <!-- EXIF Parser (JPEG, HEIC, WebP, PNG, TIFF) -->
    <script src="https://cdn.jsdelivr.net/npm/exifr/dist/full.umd.js"></script>

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

        /* Tag System */
        .selected-tags-container {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 8px;
            min-height: 28px;
        }

        .tag-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #242220;
            color: #ffffff;
            font-family: var(--font-mono);
            font-size: 12px;
            font-weight: 600;
            padding: 4px 8px;
            border-radius: 4px;
        }

        .tag-badge .btn-remove-tag {
            cursor: pointer;
            font-weight: bold;
            opacity: 0.7;
            font-size: 14px;
            line-height: 1;
        }

        .tag-badge .btn-remove-tag:hover {
            opacity: 1;
            color: var(--accent-red);
        }

        .recent-tags-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }

        .recent-tag-chip {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 14px;
            padding: 4px 10px;
            font-family: var(--font-mono);
            font-size: 12px;
            cursor: pointer;
            color: var(--text-main);
            transition: all 0.15s ease;
        }

        .recent-tag-chip:hover {
            border-color: #242220;
        }

        .recent-tag-chip.active {
            background: #242220;
            color: #ffffff;
            border-color: #242220;
        }

        /* EXIF Preview Box */
        .exif-preview-box {
            display: none;
            margin-top: 8px;
            font-family: var(--font-mono);
            font-size: 12px;
            color: var(--text-main);
            padding: 8px 12px;
            background: rgba(0, 0, 0, 0.03);
            border: 1px dashed var(--border-color);
            border-radius: 6px;
        }

        /* Edit Modal */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            padding: 16px;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-card {
            background: var(--bg-color);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            max-width: 480px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            padding: 24px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
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

            <!-- Kameradata (EXIF) -->
            <div class="form-group">
                <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 6px;">
                    <label class="form-label" style="margin-bottom: 0;">Kameradata (EXIF)</label>
                    <span id="exifStatusBadge" style="font-family: var(--font-mono); font-size: 11px; color: var(--text-muted);">
                        Udlæses automatisk fra foto
                    </span>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px;">
                    <input type="text" id="exifApertureInput" class="form-control" placeholder="f.eks. ƒ/2.8" title="Blænde">
                    <input type="text" id="exifShutterInput" class="form-control" placeholder="f.eks. 1/250s" title="Lukkertid">
                    <input type="text" id="exifIsoInput" class="form-control" placeholder="f.eks. ISO 400" title="ISO">
                </div>
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
                <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 6px;">
                    <label class="form-label" for="dateInput" style="margin-bottom: 0;">Dato &amp; Klokkeslæt</label>
                    <span id="dateOriginBadge" style="font-family: var(--font-mono); font-size: 11px; color: var(--text-muted);"></span>
                </div>
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

            <!-- Tags / Emner -->
            <div class="form-group">
                <label class="form-label" for="tagInput">
                    Tags / Emner <span style="font-weight: 400; font-size: 11px; color: var(--text-muted);">(Maks 3 per indlæg)</span>
                </label>
                <div id="selectedTagsContainer" class="selected-tags-container"></div>
                <div style="display: flex; gap: 8px;">
                    <input 
                        type="text" 
                        id="tagInput" 
                        class="form-control" 
                        placeholder="Skriv nyt tag og tryk Enter..."
                        onkeydown="handleTagInputKey(event)"
                    >
                    <button type="button" class="btn-small" onclick="addTagFromInput()" style="white-space: nowrap; padding: 0 14px; font-weight: 600;">
                        + Tilføj
                    </button>
                </div>
                <div id="recentTagsSection" style="margin-top: 10px;">
                    <div style="font-size: 11px; font-family: var(--font-mono); color: var(--text-muted); margin-bottom: 6px;">
                        VÆLG BLANDT TIDLIGERE BRUGTE TAGS:
                    </div>
                    <div id="recentTagsContainer" class="recent-tags-chips">
                        <span style="font-size: 11px; color: var(--text-muted);">Henter tags...</span>
                    </div>
                </div>
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
        <!-- Lockdown mode -->
        <div class="settings-card" style="border-left: 4px solid var(--accent);">
            <h3 class="settings-title">[ 🔒 LUK FOR NYE LOGINS (LOCKDOWN) ]</h3>
            <p style="font-size: 14px; color: var(--text-muted); margin-bottom: 12px;">
                Når dine enheder (telefon, iPad, computer) er logget ind, kan du aktivere denne funktion. Det <strong>blokerer alle nye login-forsøg 100%</strong> – selv hvis nogen gætter din PIN-kode.
            </p>
            <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-top: 14px;">
                <span id="lockdownStatusLabel" style="font-family: var(--font-mono); font-size: 13px; font-weight: 700;">
                    Status: Tjekker...
                </span>
                <button type="button" id="btnToggleLockdown" onclick="toggleLockdown()" class="btn-small" style="padding: 10px 16px; font-weight: 600;">
                    Skift status
                </button>
            </div>
        </div>

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

<!-- Modal til redigering af indlæg -->
<div id="editModal" class="modal-overlay" onclick="closeEditModal(event)">
    <div class="modal-card" onclick="event.stopPropagation()">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h3 style="font-family: var(--font-mono); font-size: 14px; text-transform: uppercase;">[ REDIGER INDLÆG ]</h3>
            <button type="button" onclick="closeEditModal()" class="btn-small" style="padding: 4px 8px; font-weight: bold;">✕</button>
        </div>
        <form id="editForm" onsubmit="handleSaveEdit(event)">
            <input type="hidden" id="editPostId">
            <div class="form-group">
                <label class="form-label" for="editTitleInput">Overskrift</label>
                <input type="text" id="editTitleInput" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="editDateInput">Dato &amp; Klokkeslæt</label>
                <input type="datetime-local" id="editDateInput" class="form-control" style="font-family: var(--font-mono); font-size: 14px;">
            </div>
            <div class="form-group">
                <label class="form-label" for="editLocInput">Lokation</label>
                <input type="text" id="editLocInput" class="form-control">
            </div>
            <div class="form-group">
                <label class="form-label" for="editContentInput">Noter / Refleksion</label>
                <textarea id="editContentInput" class="form-control" style="min-height: 90px;"></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Tags (Maks 3)</label>
                <div id="editSelectedTagsContainer" class="selected-tags-container"></div>
                <div style="display: flex; gap: 8px;">
                    <input type="text" id="editTagInput" class="form-control" placeholder="Tilføj tag..." onkeydown="handleEditTagInputKey(event)">
                    <button type="button" class="btn-small" onclick="addEditTagFromInput()" style="white-space: nowrap;">+ Tilføj</button>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Kameradata (EXIF)</label>
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px;">
                    <input type="text" id="editExifAperture" class="form-control" placeholder="f.eks. ƒ/2.8" title="Blænde">
                    <input type="text" id="editExifShutter" class="form-control" placeholder="f.eks. 1/250s" title="Lukkertid">
                    <input type="text" id="editExifIso" class="form-control" placeholder="f.eks. ISO 400" title="ISO">
                </div>
            </div>
            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button type="submit" class="btn-primary" id="saveEditBtn" style="padding: 12px;">Gem ændringer</button>
                <button type="button" class="btn-small" onclick="closeEditModal()" style="padding: 12px 18px;">Annuller</button>
            </div>
        </form>
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
        renderSelectedTags();
        loadRecentTags();
    } else if (viewName === 'history') {
        document.getElementById('viewHistory').classList.add('active');
        document.getElementById('bottomNav').style.display = 'flex';
        document.querySelectorAll('.tab-btn')[1].classList.add('active');
        loadPostsHistory();
    } else if (viewName === 'settings') {
        document.getElementById('viewSettings').classList.add('active');
        document.getElementById('bottomNav').style.display = 'flex';
        document.querySelectorAll('.tab-btn')[2].classList.add('active');
        loadSecuritySettings();
    }
    window.scrollTo(0, 0);
}

// Robust API-kald hjælper
async function apiFetch(action, options = {}) {
    const token = localStorage.getItem('logbog_token') || '';
    const method = options.method || 'GET';
    const headers = options.headers || {};

    if (token) {
        headers['Authorization'] = 'Bearer ' + token;
    }

    let url = 'api.php?';
    if (action.includes('&') || action.includes('=')) {
        url += 'action=' + action;
    } else {
        url += 'action=' + encodeURIComponent(action);
    }

    if (options.params) {
        for (const [k, v] of Object.entries(options.params)) {
            url += '&' + encodeURIComponent(k) + '=' + encodeURIComponent(v);
        }
    }

    let body = options.body;

    if (method === 'POST') {
        if (typeof body === 'object' && !(body instanceof FormData)) {
            headers['Content-Type'] = 'application/json';
            body = JSON.stringify({ ...body, token: token });
        }
    } else {
        if (token) {
            url += '&token=' + encodeURIComponent(token);
        }
    }

    const res = await fetch(url, { method, headers, body });
    const rawText = await res.text();
    let data = null;
    try {
        data = JSON.parse(rawText);
    } catch (parseErr) {
        console.error('Uventet serversvar:', rawText);
        const cleanErr = rawText.replace(/<[^>]*>?/gm, '').trim();
        throw new Error('Serverfejl (' + res.status + '): ' + (cleanErr.substring(0, 140) || 'Ugyldigt svar'));
    }

    if (!res.ok) {
        if (res.status === 401) {
            localStorage.removeItem('logbog_token');
            switchView('login');
            throw new Error(data.error || 'Session udløbet. Log ind igen.');
        }
        throw new Error(data.error || 'Fejl fra server (' + res.status + ')');
    }

    return data;
}

// Tjek om brugeren er autentificeret
async function checkAuthStatus() {
    try {
        const data = await apiFetch('check_auth');
        if (data && data.authenticated) {
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
        const data = await apiFetch('login', {
            method: 'POST',
            body: { pin }
        });
        if (data.success) {
            localStorage.setItem('logbog_token', data.token);
            showAlert('Logget ind!', 'success', 2000);
            document.getElementById('pinInput').value = '';
            switchView('compose');
            setDefaultDate();
        }
    } catch (err) {
        showAlert(err.message || 'Forkert PIN-kode', 'error');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Lås op';
    }
}

// Tilstand for EXIF og Tags
let currentExifData = null;
let selectedTags = [];
let recentTagsList = [];
let editSelectedTags = [];

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

// Robust EXIF-udtrækker der benytter exifr UMD (hvis loaded) samt native fallback parser
async function extractExifFromFile(file) {
    let result = { aperture: null, shutter: null, iso: null, date: null, latitude: null, longitude: null };

    // 1. Prøv exifr biblioteket (fuld understøttelse af JPEG, HEIC, PNG, WebP, TIFF)
    if (window.exifr && typeof window.exifr.parse === 'function') {
        try {
            const data = await window.exifr.parse(file, {
                tiff: true,
                xmp: true,
                exif: true,
                gps: true,
                pick: [
                    'FNumber', 'ApertureValue', 
                    'ExposureTime', 'ShutterSpeedValue', 
                    'ISO', 'ISOSpeedRatings', 'PhotographicSensitivity', 
                    'DateTimeOriginal', 'CreateDate', 'ModifyDate',
                    'latitude', 'longitude'
                ]
            });

            if (data) {
                // Blænde
                if (data.FNumber) {
                    result.aperture = 'ƒ/' + (Math.round(data.FNumber * 10) / 10);
                } else if (data.ApertureValue) {
                    const f = Math.pow(Math.SQRT2, data.ApertureValue);
                    result.aperture = 'ƒ/' + (Math.round(f * 10) / 10);
                }

                // Lukkertid
                if (data.ExposureTime) {
                    const exp = data.ExposureTime;
                    if (exp < 1 && exp > 0) {
                        result.shutter = '1/' + Math.round(1 / exp) + 's';
                    } else {
                        result.shutter = (Math.round(exp * 10) / 10) + 's';
                    }
                } else if (data.ShutterSpeedValue) {
                    const exp = Math.pow(2, -data.ShutterSpeedValue);
                    if (exp < 1 && exp > 0) {
                        result.shutter = '1/' + Math.round(1 / exp) + 's';
                    } else {
                        result.shutter = (Math.round(exp * 10) / 10) + 's';
                    }
                }

                // ISO
                const isoVal = data.ISO || data.ISOSpeedRatings || data.PhotographicSensitivity;
                if (isoVal) {
                    const isoNum = Array.isArray(isoVal) ? isoVal[0] : isoVal;
                    if (isoNum) result.iso = 'ISO ' + isoNum;
                }

                // Optagedato
                const rawDate = data.DateTimeOriginal || data.CreateDate || data.ModifyDate;
                if (rawDate) {
                    if (rawDate instanceof Date && !isNaN(rawDate.getTime())) {
                        result.date = rawDate;
                    } else if (typeof rawDate === 'string') {
                        const m = rawDate.match(/^(\d{4})[:\-\/](\d{2})[:\-\/](\d{2})\s+(\d{2}):(\d{2})(?::(\d{2}))?/);
                        if (m) {
                            result.date = new Date(m[1], m[2] - 1, m[3], m[4], m[5], m[6] || 0);
                        }
                    }
                }

                // GPS
                if (typeof data.latitude === 'number' && typeof data.longitude === 'number') {
                    result.latitude = data.latitude;
                    result.longitude = data.longitude;
                }

                if (result.aperture || result.shutter || result.iso || result.date) {
                    return result;
                }
            }
        } catch (err) {
            console.warn('exifr parsing bemærkning:', err);
        }
    }

    // 2. Fallback: Parse buffer direkte hvis JPEG
    try {
        const buffer = await file.arrayBuffer();
        const fallback = parseExifFallback(buffer);
        if (fallback) {
            result = Object.assign(result, fallback);
        }
    } catch (e) {
        console.warn('Fallback exif note:', e);
    }

    return result;
}

// Robust rå JPEG EXIF parser der scanner alle segmenter og håndterer både Motorola og Intel TIFF
function parseExifFallback(buffer) {
    const view = new DataView(buffer);
    if (view.byteLength < 4 || view.getUint16(0, false) !== 0xFFD8) return null;

    let offset = 2;
    const length = view.byteLength;

    while (offset < length - 4) {
        if (view.getUint8(offset) !== 0xFF) {
            offset++;
            continue;
        }
        const marker = view.getUint8(offset + 1);
        if (marker === 0xDA || marker === 0xD9) break;

        const segLength = view.getUint16(offset + 2, false);
        if (segLength < 2 || offset + 2 + segLength > length) break;

        // APP1 (0xE1)
        if (marker === 0xE1 && segLength >= 14) {
            const exifOffset = offset + 4;
            const isExif = view.getUint32(exifOffset, false) === 0x45786966 && view.getUint16(exifOffset + 4, false) === 0x0000;
            if (isExif) {
                const tiffStart = exifOffset + 6;
                const endianCode = view.getUint16(tiffStart, false);
                const bigEndian = endianCode === 0x4D4D;
                if (!bigEndian && endianCode !== 0x4949) {
                    offset += 2 + segLength;
                    continue;
                }

                const ifd0Offset = tiffStart + view.getUint32(tiffStart + 4, bigEndian);
                if (ifd0Offset < length) {
                    const result = { aperture: null, shutter: null, iso: null, date: null };
                    let exifIfdOffset = null;
                    const numEntries0 = view.getUint16(ifd0Offset, bigEndian);

                    for (let i = 0; i < numEntries0; i++) {
                        const entryOffset = ifd0Offset + 2 + i * 12;
                        if (entryOffset + 12 > length) break;
                        const tag = view.getUint16(entryOffset, bigEndian);
                        if (tag === 0x8769) {
                            exifIfdOffset = tiffStart + view.getUint32(entryOffset + 8, bigEndian);
                        } else if (tag === 0x0132 && !result.date) {
                            const dOffset = tiffStart + view.getUint32(entryOffset + 8, bigEndian);
                            result.date = readExifDateString(view, dOffset, length);
                        }
                    }

                    if (exifIfdOffset && exifIfdOffset + 2 < length) {
                        const numExifEntries = view.getUint16(exifIfdOffset, bigEndian);
                        for (let i = 0; i < numExifEntries; i++) {
                            const entryOffset = exifIfdOffset + 2 + i * 12;
                            if (entryOffset + 12 > length) break;
                            const tag = view.getUint16(entryOffset, bigEndian);
                            const valOffset = tiffStart + view.getUint32(entryOffset + 8, bigEndian);

                            if (tag === 0x829D && !result.aperture) {
                                if (valOffset + 8 <= length) {
                                    const num = view.getUint32(valOffset, bigEndian);
                                    const den = view.getUint32(valOffset + 4, bigEndian);
                                    if (den > 0) result.aperture = 'ƒ/' + (Math.round((num / den) * 10) / 10);
                                }
                            } else if (tag === 0x9202 && !result.aperture) {
                                if (valOffset + 8 <= length) {
                                    const num = view.getUint32(valOffset, bigEndian);
                                    const den = view.getUint32(valOffset + 4, bigEndian);
                                    if (den > 0) {
                                        const f = Math.pow(Math.SQRT2, num / den);
                                        result.aperture = 'ƒ/' + (Math.round(f * 10) / 10);
                                    }
                                }
                            } else if (tag === 0x829A && !result.shutter) {
                                if (valOffset + 8 <= length) {
                                    const num = view.getUint32(valOffset, bigEndian);
                                    const den = view.getUint32(valOffset + 4, bigEndian);
                                    if (num > 0 && den > 0) {
                                        if (num < den) {
                                            result.shutter = '1/' + Math.round(den / num) + 's';
                                        } else {
                                            result.shutter = (Math.round((num / den) * 10) / 10) + 's';
                                        }
                                    }
                                }
                            } else if (tag === 0x8827 || tag === 0x8832) {
                                const entryType = view.getUint16(entryOffset + 2, bigEndian);
                                let iso = 0;
                                if (entryType === 3) {
                                    iso = view.getUint16(entryOffset + 8, bigEndian);
                                } else if (entryType === 4) {
                                    iso = view.getUint32(entryOffset + 8, bigEndian);
                                }
                                if (iso > 0 && !result.iso) {
                                    result.iso = 'ISO ' + iso;
                                }
                            } else if (tag === 0x9003 && !result.date) {
                                result.date = readExifDateString(view, valOffset, length);
                            }
                        }
                    }

                    if (result.aperture || result.shutter || result.iso || result.date) {
                        return result;
                    }
                }
            }
        }

        offset += 2 + segLength;
    }
    return null;
}

function readExifDateString(view, offset, maxLen) {
    if (offset + 19 > maxLen) return null;
    let str = '';
    for (let i = 0; i < 19; i++) {
        str += String.fromCharCode(view.getUint8(offset + i));
    }
    const m = str.match(/^(\d{4})[:\-\/](\d{2})[:\-\/](\d{2})\s+(\d{2}):(\d{2})(?::(\d{2}))?/);
    if (m) {
        return new Date(m[1], m[2] - 1, m[3], m[4], m[5], m[6] || 0);
    }
    return null;
}

// Billedbehandling & Klient-optimering (Canvas)
async function handleImageSelected(e) {
    const file = e.target.files[0];
    if (!file) return;

    // Prøv at udtrække EXIF direkte fra råfilen før canvas-komprimering
    try {
        const exifData = await extractExifFromFile(file);
        if (exifData) {
            currentExifData = exifData;

            // Udfyld EXIF felterne i skrivefladen
            if (exifData.aperture) document.getElementById('exifApertureInput').value = exifData.aperture;
            if (exifData.shutter) document.getElementById('exifShutterInput').value = exifData.shutter;
            if (exifData.iso) document.getElementById('exifIsoInput').value = exifData.iso;

            const parts = [exifData.aperture, exifData.shutter, exifData.iso].filter(Boolean);
            const badge = document.getElementById('exifStatusBadge');
            if (badge) {
                if (parts.length > 0) {
                    badge.textContent = '✓ Udlæst fra foto (' + parts.join(' · ') + ')';
                    badge.style.color = 'var(--accent-red)';
                } else {
                    badge.textContent = 'Ingen EXIF fundet i fil – indtast evt. manuelt';
                    badge.style.color = 'var(--text-muted)';
                }
            }

            // Hvis fotoet indeholder optagedato, opdater automatisk Dato & Klokkeslæt
            if (exifData.date && exifData.date instanceof Date && !isNaN(exifData.date.getTime())) {
                const d = exifData.date;
                const localIso = new Date(d.getTime() - d.getTimezoneOffset() * 60000).toISOString().slice(0, 16);
                document.getElementById('dateInput').value = localIso;

                const dateBadge = document.getElementById('dateOriginBadge');
                if (dateBadge) {
                    const pad = n => String(n).padStart(2, '0');
                    dateBadge.textContent = `✓ Foto optaget: ${pad(d.getDate())}.${pad(d.getMonth() + 1)}.${d.getFullYear()} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
                    dateBadge.style.color = 'var(--accent-red)';
                }
            }

            // Hvis fotoet indeholder GPS-koordinater
            if (exifData.latitude && exifData.longitude) {
                document.getElementById('latInput').value = exifData.latitude;
                document.getElementById('lonInput').value = exifData.longitude;
            }
        }
    } catch (err) {
        console.warn('EXIF udtræk bemærkning:', err);
    }

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

// Tags håndtering i Compose
async function loadRecentTags() {
    try {
        const res = await apiFetch('get_tags');
        if (res && res.tags) {
            recentTagsList = res.tags;
            renderRecentTags();
        }
    } catch (e) {
        console.warn('Kunne ikke hente tags:', e);
    }
}

function renderRecentTags() {
    const container = document.getElementById('recentTagsContainer');
    if (!container) return;
    if (recentTagsList.length === 0) {
        container.innerHTML = '<span style="font-size: 11px; color: var(--text-muted);">Ingen tidligere tags</span>';
        return;
    }

    container.innerHTML = recentTagsList.map(t => {
        const isSelected = selectedTags.some(st => st.toLowerCase() === t.name.toLowerCase());
        return `
            <button type="button" 
                    class="recent-tag-chip ${isSelected ? 'active' : ''}" 
                    onclick="toggleTag('${escapeHtml(t.name)}')">
                #${escapeHtml(t.name)} ${isSelected ? '✓' : ''}
            </button>
        `;
    }).join('');
}

function renderSelectedTags() {
    const container = document.getElementById('selectedTagsContainer');
    if (!container) return;
    if (selectedTags.length === 0) {
        container.innerHTML = '<span style="font-size: 12px; color: var(--text-muted); font-style: italic;">Ingen tags valgt</span>';
        renderRecentTags();
        return;
    }

    container.innerHTML = selectedTags.map(t => `
        <span class="tag-badge">
            #${escapeHtml(t)}
            <span class="btn-remove-tag" onclick="removeTag('${escapeHtml(t)}')" title="Fjern">&times;</span>
        </span>
    `).join('');

    renderRecentTags();
}

function addTag(rawTag) {
    let tag = rawTag.trim().replace(/^#+/, '').trim();
    if (!tag) return;
    if (selectedTags.length >= 3) {
        showAlert('Du kan højst vælge 3 tags per indlæg', 'error', 3000);
        return;
    }
    const lower = tag.toLowerCase();
    if (selectedTags.some(t => t.toLowerCase() === lower)) {
        return;
    }
    selectedTags.push(tag);
    renderSelectedTags();
}

function removeTag(tag) {
    selectedTags = selectedTags.filter(t => t.toLowerCase() !== tag.toLowerCase());
    renderSelectedTags();
}

function toggleTag(tag) {
    if (selectedTags.some(t => t.toLowerCase() === tag.toLowerCase())) {
        removeTag(tag);
    } else {
        addTag(tag);
    }
}

function addTagFromInput() {
    const input = document.getElementById('tagInput');
    const val = input.value.trim();
    if (val) {
        addTag(val);
        input.value = '';
    }
}

function handleTagInputKey(e) {
    if (e.key === 'Enter' || e.key === ',') {
        e.preventDefault();
        addTagFromInput();
    }
}

// Tags håndtering i Edit modal
function renderEditSelectedTags() {
    const container = document.getElementById('editSelectedTagsContainer');
    if (!container) return;
    if (editSelectedTags.length === 0) {
        container.innerHTML = '<span style="font-size: 12px; color: var(--text-muted); font-style: italic;">Ingen tags</span>';
        return;
    }
    container.innerHTML = editSelectedTags.map(t => `
        <span class="tag-badge">
            #${escapeHtml(t)}
            <span class="btn-remove-tag" onclick="removeEditTag('${escapeHtml(t)}')">&times;</span>
        </span>
    `).join('');
}

function addEditTag(rawTag) {
    let tag = rawTag.trim().replace(/^#+/, '').trim();
    if (!tag) return;
    if (editSelectedTags.length >= 3) {
        showAlert('Højst 3 tags per indlæg', 'error', 2500);
        return;
    }
    if (editSelectedTags.some(t => t.toLowerCase() === tag.toLowerCase())) return;
    editSelectedTags.push(tag);
    renderEditSelectedTags();
}

function removeEditTag(tag) {
    editSelectedTags = editSelectedTags.filter(t => t.toLowerCase() !== tag.toLowerCase());
    renderEditSelectedTags();
}

function addEditTagFromInput() {
    const input = document.getElementById('editTagInput');
    const val = input.value.trim();
    if (val) {
        addEditTag(val);
        input.value = '';
    }
}

function handleEditTagInputKey(e) {
    if (e.key === 'Enter' || e.key === ',') {
        e.preventDefault();
        addEditTagFromInput();
    }
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
        image_data: compressedImageData,
        tags: selectedTags,
        exif_aperture: (document.getElementById('exifApertureInput')?.value || '').trim(),
        exif_shutter: (document.getElementById('exifShutterInput')?.value || '').trim(),
        exif_iso: (document.getElementById('exifIsoInput')?.value || '').trim()
    };

    try {
        const data = await apiFetch('create_post', {
            method: 'POST',
            body: payload
        });

        if (data.success) {
            showAlert('✓ Dit indlæg er gemt i logbogen!', 'success', 4000);
            document.getElementById('postForm').reset();
            compressedImageData = null;
            currentExifData = null;
            selectedTags = [];
            renderSelectedTags();
            document.getElementById('exifApertureInput').value = '';
            document.getElementById('exifShutterInput').value = '';
            document.getElementById('exifIsoInput').value = '';
            const exifBadge = document.getElementById('exifStatusBadge');
            if (exifBadge) {
                exifBadge.textContent = 'Udlæses automatisk fra foto';
                exifBadge.style.color = 'var(--text-muted)';
            }
            const dateBadge = document.getElementById('dateOriginBadge');
            if (dateBadge) {
                dateBadge.textContent = '';
            }
            document.getElementById('imagePreview').style.display = 'none';
            document.getElementById('photoPickerPrompt').style.display = 'block';
            document.getElementById('imageResInfo').style.display = 'none';
            setDefaultDate();
            loadRecentTags();
        }
    } catch (err) {
        showAlert(err.message || 'Fejl under gemning', 'error');
    } finally {
        saveBtn.disabled = false;
        saveBtn.textContent = 'Gem i logbog';
    }
}

// Hent liste af indlæg til historik/redigering
async function loadPostsHistory() {
    const container = document.getElementById('postsListContainer');
    container.innerHTML = '<p style="font-family: var(--font-mono); font-size: 13px; color: var(--text-muted);">Henter indlæg...</p>';

    try {
        const data = await apiFetch('get_posts');
        if (data && data.posts) {
            if (data.posts.length === 0) {
                container.innerHTML = '<p style="font-family: var(--font-mono); font-size: 13px; color: var(--text-muted);">Ingen indlæg oprettet endnu.</p>';
                return;
            }

            let html = '';
            data.posts.forEach(p => {
                const imgUrl = '../uploads/' + p.image_path;
                const dateStr = p.published_at.substring(0, 10);
                const tagNames = (p.tags || []).map(t => '#' + t.name).join(' ');
                html += `
                    <div class="history-item" id="hist-${p.id}">
                        <img src="${imgUrl}" class="history-thumb" alt="">
                        <div class="history-info">
                            <div class="history-title">${escapeHtml(p.title)}</div>
                            <div class="history-date">[${dateStr}] ${p.location ? '• ' + escapeHtml(p.location) : ''}</div>
                            ${tagNames ? `<div style="font-family: var(--font-mono); font-size: 11px; color: var(--text-muted); margin-top: 2px;">${escapeHtml(tagNames)}</div>` : ''}
                        </div>
                        <div class="history-actions">
                            <a href="../post.php?p=${p.uid || p.id}" target="_blank" class="btn-small" style="text-decoration:none;">Vis</a>
                            <button onclick="openEditModal(${p.id})" class="btn-small">Rediger</button>
                            <button onclick="deletePost(${p.id})" class="btn-small btn-danger">Slet</button>
                        </div>
                    </div>
                `;
            });
            container.innerHTML = html;
        }
    } catch (e) {
        container.innerHTML = `<p style="color: var(--accent-red);">${e.message || 'Kunne ikke hente indlæg'}</p>`;
    }
}

// Rediger indlæg modal logik
async function openEditModal(id) {
    try {
        const data = await apiFetch('get_post', { params: { id: id } });
        if (!data || !data.post) return;
        const p = data.post;
        document.getElementById('editPostId').value = p.id;
        document.getElementById('editTitleInput').value = p.title || '';
        document.getElementById('editLocInput').value = p.location || '';
        document.getElementById('editContentInput').value = p.content || '';
        
        if (p.published_at) {
            const dt = p.published_at.replace(' ', 'T').substring(0, 16);
            document.getElementById('editDateInput').value = dt;
        }

        editSelectedTags = (p.tags || []).map(t => t.name);
        renderEditSelectedTags();

        document.getElementById('editExifAperture').value = p.exif_aperture || '';
        document.getElementById('editExifShutter').value = p.exif_shutter || '';
        document.getElementById('editExifIso').value = p.exif_iso || '';

        document.getElementById('editModal').classList.add('active');
    } catch (e) {
        showAlert('Kunne ikke hente indlæg: ' + e.message, 'error');
    }
}

function closeEditModal(e) {
    if (e && e.target !== document.getElementById('editModal') && e.type === 'click') return;
    document.getElementById('editModal').classList.remove('active');
}

async function handleSaveEdit(e) {
    e.preventDefault();
    const id = document.getElementById('editPostId').value;
    const btn = document.getElementById('saveEditBtn');
    btn.disabled = true;
    btn.textContent = 'Gemmer...';

    const payload = {
        id: id,
        title: document.getElementById('editTitleInput').value,
        location: document.getElementById('editLocInput').value,
        content: document.getElementById('editContentInput').value,
        published_at: document.getElementById('editDateInput').value,
        tags: editSelectedTags,
        exif_aperture: document.getElementById('editExifAperture').value,
        exif_shutter: document.getElementById('editExifShutter').value,
        exif_iso: document.getElementById('editExifIso').value
    };

    try {
        const data = await apiFetch('update_post', {
            method: 'POST',
            body: payload
        });
        if (data.success) {
            showAlert('✓ Indlæg opdateret!', 'success', 3000);
            closeEditModal();
            loadPostsHistory();
            loadRecentTags();
        }
    } catch (err) {
        showAlert(err.message || 'Fejl under opdatering', 'error');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Gem ændringer';
    }
}

// Slet indlæg
async function deletePost(id) {
    if (!confirm('Er du sikker på, at du vil slette dette indlæg? Billedet slettes også.')) {
        return;
    }

    try {
        const data = await apiFetch('delete_post', {
            method: 'POST',
            body: { id }
        });
        if (data.success) {
            showAlert('Indlæg slettet', 'success', 2000);
            const el = document.getElementById('hist-' + id);
            if (el) el.remove();
        }
    } catch (e) {
        showAlert(e.message || 'Fejl under sletning', 'error');
    }
}

// Hent sikkerhedsindstillinger (Lockdown)
async function loadSecuritySettings() {
    const label = document.getElementById('lockdownStatusLabel');
    try {
        const data = await apiFetch('get_settings');
        updateLockdownUI(data.lockdown);
    } catch (e) {
        label.textContent = 'Status: ' + (e.message || 'Kunne ikke hentes');
    }
}

function updateLockdownUI(isLocked) {
    const label = document.getElementById('lockdownStatusLabel');
    const btn = document.getElementById('btnToggleLockdown');
    if (isLocked) {
        label.innerHTML = 'Status: <span style="color: #b93826;">🔒 AKTIV (Nye logins er blokeret)</span>';
        btn.textContent = '🔓 Åbn for nye logins';
        btn.className = 'btn-small';
    } else {
        label.innerHTML = 'Status: <span style="color: #166534;">🔓 ÅBEN (Nye logins tilladt)</span>';
        btn.textContent = '🔒 Lås for nye logins';
        btn.className = 'btn-small btn-danger';
    }
}

async function toggleLockdown() {
    const btn = document.getElementById('btnToggleLockdown');
    btn.disabled = true;

    try {
        const data = await apiFetch('toggle_lockdown', { method: 'POST' });
        showAlert(data.message, 'success', 3500);
        updateLockdownUI(data.lockdown);
    } catch (e) {
        showAlert(e.message || 'Fejl', 'error');
    } finally {
        btn.disabled = false;
    }
}

// Opdater PIN
async function handleUpdatePin(e) {
    e.preventDefault();
    const newPin = document.getElementById('newPinInput').value;

    try {
        const data = await apiFetch('update_pin', {
            method: 'POST',
            body: { new_pin: newPin }
        });
        if (data.success) {
            showAlert('✓ PIN-koden er ændret!', 'success', 3000);
            document.getElementById('newPinInput').value = '';
        }
    } catch (e) {
        showAlert(e.message || 'Fejl under ændring', 'error');
    }
}

// Log ud
async function handleLogout() {
    try {
        await apiFetch('logout');
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
