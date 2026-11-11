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
    <link rel="icon" type="image/svg+xml" href="../assets/icons/favicon.svg">
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
            font-size: 32px;
            display: block;
            margin-bottom: 8px;
            font-family: var(--font-mono);
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
            font-size: 13px;
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
            font-size: 18px;
            font-family: var(--font-mono);
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
            font-family: var(--font-mono);
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
            background: none;
            border: none;
            color: inherit;
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

        /* Missing Dates Bar in History */
        .missing-dates-box {
            background: var(--card-bg);
            border: 1px dashed var(--border-color);
            border-radius: 10px;
            padding: 12px 14px;
            margin-bottom: 16px;
        }

        .missing-date-chip {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-family: var(--font-mono);
            font-size: 11px;
            padding: 4px 9px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            background: var(--bg-color);
            color: var(--text-main);
            cursor: pointer;
        }

        .missing-date-chip:hover {
            border-color: var(--accent-red);
            color: var(--accent-red);
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

        /* Delingskort / Open Graph Vælger */
        .og-style-selector {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 6px;
            margin-top: 6px;
        }
        .og-style-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 10px 4px;
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.15s ease;
            text-align: center;
            color: var(--text-main);
            user-select: none;
            -webkit-user-select: none;
        }
        .og-style-btn:hover {
            border-color: var(--text-muted);
        }
        .og-style-btn.active {
            background: #242220;
            color: #faf8f5;
            border-color: #242220;
            box-shadow: 0 2px 6px rgba(0,0,0,0.12);
        }
        @media (prefers-color-scheme: dark) {
            .og-style-btn.active {
                background: #f5f2eb;
                color: #1a1816;
                border-color: #f5f2eb;
            }
        }
        .og-style-icon {
            font-size: 18px;
            margin-bottom: 4px;
            line-height: 1;
        }
        .og-style-label {
            font-family: var(--font-mono);
            font-size: 11px;
            font-weight: 600;
        }
        .og-preview-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 10px;
        }
        .og-canvas-wrap {
            width: 100%;
            aspect-ratio: 1200 / 630;
            background: #181614;
            border-radius: 6px;
            overflow: hidden;
            border: 1px solid var(--border-color);
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .og-canvas-wrap canvas {
            width: 100%;
            height: 100%;
            display: block;
            object-fit: contain;
        }
    </style>
</head>
<body>

<div class="container">

    <!-- Header -->
    <header class="app-header">
        <div class="app-title">SYS.LOG // BACKEND</div>
        <div style="display: flex; align-items: center; gap: 12px;">
            <span id="draftBadge" style="display: none; font-family: var(--font-mono); font-size: 11px; color: var(--text-muted); cursor: pointer;" onclick="clearDraft(true)" title="Tryk for at rydde kladde">[KLADDE GEMT ✕]</span>
            <a href="<?= htmlspecialchars(BASE_URL) ?>/" target="_blank">Vis logbog ↗</a>
        </div>
    </header>

    <!-- Beskeder / Status -->
    <div id="alertBox" class="alert-box"></div>

    <!-- 1. LOGIN SKÆRM -->
    <div id="viewLogin" class="view-section">
        <div class="auth-card">
            <div style="font-size: 34px; margin-bottom: 12px; font-family: var(--font-mono);">[ 🔒 ]</div>
            <h2 class="auth-title">[ ADGANGSKODE ]</h2>
            <p class="auth-desc">Indtast din personlige kode for at skrive i logbogen.</p>
            
            <form id="loginForm" onsubmit="handleLogin(event)">
                <input 
                    type="password" 
                    id="pinInput" 
                    class="pin-input" 
                    inputmode="numeric" 
                    maxlength="16" 
                    placeholder="••••" 
                    required 
                    autofocus
                >
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
                    <span class="photo-picker-icon">[ ⌀ FOTO ]</span>
                    <div class="photo-picker-text" id="photoPickerPrompt">
                        <strong>Tryk her for at vælge dagens fotografi</strong><br>
                        (Udlæser automatisk optagedato, GPS &amp; EXIF)
                    </div>
                    <img id="imagePreview" class="photo-preview" alt="Preview">
                </div>
                <div id="imageResInfo" style="font-family: var(--font-mono); font-size: 11px; color: var(--text-muted); margin-top: 4px; display: none;"></div>
            </div>

            <!-- Kameradata (EXIF) inkl. Kamera/Optik og Brændvidde -->
            <div class="form-group">
                <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 6px;">
                    <label class="form-label" style="margin-bottom: 0;">Kameradata &amp; Optik (EXIF)</label>
                    <span id="exifStatusBadge" style="font-family: var(--font-mono); font-size: 11px; color: var(--text-muted);">
                        Udlæses automatisk fra foto
                    </span>
                </div>
                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 8px; margin-bottom: 8px;">
                    <input type="text" id="exifCameraInput" class="form-control" placeholder="Kamera / Optik / Film (f.eks. Leica Q2)" title="Kamera, objektiv eller filmtype">
                    <input type="text" id="exifFocalInput" class="form-control" placeholder="f.eks. 35mm" title="Brændvidde">
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px;">
                    <input type="text" id="exifApertureInput" class="form-control" placeholder="f.eks. ƒ/2.8" title="Blænde">
                    <input type="text" id="exifShutterInput" class="form-control" placeholder="f.eks. 1/250s" title="Lukkertid">
                    <input type="text" id="exifIsoInput" class="form-control" placeholder="f.eks. ISO 400" title="ISO">
                </div>
            </div>

            <!-- Lokation med GPS knap -->
            <div class="form-group">
                <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 6px;">
                    <label class="form-label" for="locInput" style="margin-bottom: 0;">Lokation</label>
                    <span id="locOriginBadge" style="font-family: var(--font-mono); font-size: 11px; color: var(--text-muted);"></span>
                </div>
                <div class="loc-row">
                    <input 
                        type="text" 
                        id="locInput" 
                        class="form-control" 
                        placeholder="f.eks. København, Nørrebro"
                    >
                    <button type="button" class="btn-loc" id="gpsBtn" onclick="fetchGPSLocation()" title="Hent enhedens nuværende placering">
                        <span id="gpsIcon">⌖</span> Her
                    </button>
                </div>
                <input type="hidden" id="latInput">
                <input type="hidden" id="lonInput">
            </div>

            <!-- Dato & Tid -->
            <div class="form-group">
                <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 6px;">
                    <label class="form-label" for="dateInput" style="margin-bottom: 0;">Optagedato &amp; Klokkeslæt</label>
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

            <!-- Tekst / Refleksion med Skrivemaskine-farvebånd & formatering -->
            <div class="form-group">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 6px; margin-bottom: 6px;">
                    <label class="form-label" for="contentInput" style="margin-bottom: 0;">Noter / Refleksion</label>
                    <div class="tw-toolbar" style="display: flex; gap: 4px;">
                        <button type="button" class="btn-small" style="padding: 3px 8px; font-weight: 700;" onclick="applyTypewriterFormat('contentInput', '**')" title="Fed (**tekst**)">B</button>
                        <button type="button" class="btn-small" style="padding: 3px 8px; font-style: italic;" onclick="applyTypewriterFormat('contentInput', '*')" title="Kursiv (*tekst*)">I</button>
                        <button type="button" class="btn-small" style="padding: 3px 8px; text-decoration: underline;" onclick="applyTypewriterFormat('contentInput', '__')" title="Understreget (__tekst__)">U</button>
                        <button type="button" class="btn-small" style="padding: 3px 8px; color: var(--accent-red); border-color: var(--accent-red); font-weight: 600;" onclick="applyTypewriterFormat('contentInput', '==')" title="Rødt farvebånd (==tekst==)">● RØD</button>
                    </div>
                </div>
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

            <!-- Delingskort / Social Share Style -->
            <div class="form-group" style="margin-top: 18px; margin-bottom: 24px;">
                <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 4px;">
                    <label class="form-label" style="margin-bottom: 0;">Delingskort (Sociale medier)</label>
                    <span style="font-family: var(--font-mono); font-size: 11px; color: var(--text-muted);">
                        Vælg visuelt udtryk
                    </span>
                </div>
                
                <div class="og-style-selector" id="ogStyleSelector">
                    <button type="button" class="og-style-btn active" data-style="polaroid" onclick="setOgStyle('polaroid')">
                        <span class="og-style-icon">📷</span>
                        <span class="og-style-label">Polaroid</span>
                    </button>
                    <button type="button" class="og-style-btn" data-style="darkroom" onclick="setOgStyle('darkroom')">
                        <span class="og-style-icon">🎞️</span>
                        <span class="og-style-label">35mm Film</span>
                    </button>
                    <button type="button" class="og-style-btn" data-style="notebook" onclick="setOgStyle('notebook')">
                        <span class="og-style-icon">📓</span>
                        <span class="og-style-label">Notesbog</span>
                    </button>
                    <button type="button" class="og-style-btn" data-style="raw" onclick="setOgStyle('raw')">
                        <span class="og-style-icon">🖼️</span>
                        <span class="og-style-label">Rent foto</span>
                    </button>
                </div>

                <!-- Live Preview af det sociale delingskort -->
                <div class="og-preview-card" id="ogPreviewContainer" style="margin-top: 12px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                        <span style="font-family: var(--font-mono); font-size: 10px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted);">
                            [ FORHÅNDSVISNING AF DELT KORT · 1200 × 630 ]
                        </span>
                        <span id="ogPreviewStatus" style="font-family: var(--font-mono); font-size: 10px; color: var(--text-muted);">
                            Opdateres automatisk
                        </span>
                    </div>
                    <div class="og-canvas-wrap">
                        <canvas id="ogPreviewCanvas" width="1200" height="630"></canvas>
                    </div>
                </div>
            </div>

            <!-- Gem knap -->
            <button type="submit" class="btn-primary" id="savePostBtn">
                Gem i logbog
            </button>
        </form>
    </div>

    <!-- 3. TIDLIGERE INDLÆG (Historik, Manglende dage & Redigering) -->
    <div id="viewHistory" class="view-section">
        <!-- Overblik over manglende dage (seneste 14 dage) – kun synligt her i fotografens private app -->
        <div id="missingDatesBox" class="missing-dates-box" style="display: none;">
            <div style="font-family: var(--font-mono); font-size: 11px; color: var(--text-muted); margin-bottom: 8px; text-transform: uppercase;">
                [ LEDIGE DATOER DE SENESTE 14 DAGE – TRYK FOR AT UDFYLDE ]
            </div>
            <div id="missingDatesChips" style="display: flex; flex-wrap: wrap; gap: 6px;"></div>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 10px;">
            <h3 style="font-family: var(--font-mono); font-size: 14px; text-transform: uppercase;">
                [ DINE INDLÆG ]
            </h3>
            <span id="historyTotalLabel" style="font-family: var(--font-mono); font-size: 12px; color: var(--text-muted);"></span>
        </div>

        <!-- Søgefelt til hurtigt at finde et af årets 365+ indlæg -->
        <div class="form-group" style="margin-bottom: 14px;">
            <input 
                type="search" 
                id="historySearchInput" 
                class="form-control" 
                placeholder="Søg i titel, dato (YYYY-MM), lokation, kamera eller #tag..."
                oninput="handleHistorySearchInput()"
                style="font-size: 14px; padding: 10px 12px;"
            >
        </div>

        <div id="postsListContainer">
            <p style="font-family: var(--font-mono); font-size: 13px; color: var(--text-muted);">Indlæser indlæg...</p>
        </div>

        <button type="button" id="loadMoreHistoryBtn" class="btn-small" onclick="loadMoreHistory()" style="display: none; width: 100%; padding: 12px; margin-top: 8px; font-weight: 600;">
            Indlæs flere indlæg...
        </button>
    </div>

    <!-- 4. INDSTILLINGER & BACKUP -->
    <div id="viewSettings" class="view-section">
        <!-- Lockdown mode -->
        <div class="settings-card" style="border-left: 4px solid var(--accent);">
            <h3 class="settings-title">[ 🔒 LUK FOR NYE LOGINS (LOCKDOWN) ]</h3>
            <p style="font-size: 14px; color: var(--text-muted); margin-bottom: 12px;">
                Når dine enheder (telefon, iPad, computer) er logget ind, kan du aktivere denne funktion. Det <strong>blokerer alle nye login-forsøg 100%</strong> – selv hvis nogen kender din PIN-kode.
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
            <button type="button" id="btnBackupDb" onclick="downloadDatabaseBackup()" class="btn-small" style="padding: 10px 16px; font-weight: 600;">
                💾 Download database backup
            </button>
        </div>

        <div class="settings-card">
            <h3 class="settings-title">[ SKIFT PIN-KODE ]</h3>
            <form onsubmit="handleUpdatePin(event)">
                <div class="form-group">
                    <label class="form-label">Ny PIN-kode (min. 4 tegn)</label>
                    <input type="password" id="newPinInput" class="form-control" required minlength="4" placeholder="Ny PIN">
                </div>
                <button type="submit" class="btn-small" style="padding: 10px 16px;">Opdater PIN</button>
            </form>
        </div>

        <div class="settings-card">
            <h3 class="settings-title">[ SESSIONER &amp; ENHEDER ]</h3>
            <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 12px;">
                Du kan enten logge ud på denne enhed eller ugyldiggøre alle tidligere udstedte login-tokens på andre enheder.
            </p>
            <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                <button type="button" onclick="handleRevokeSessions()" class="btn-small" style="padding: 10px 14px;">
                    Log andre enheder ud
                </button>
                <button type="button" onclick="handleLogout()" class="btn-small btn-danger" style="padding: 10px 14px;">
                    Log ud her
                </button>
            </div>
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

            <!-- Mulighed for at skifte billede ved redigering -->
            <div class="form-group">
                <label class="form-label">Fotografi</label>
                <input type="file" id="editFileInput" accept="image/*" style="display: none;" onchange="handleEditImageSelected(event)">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <img id="editImagePreview" src="" alt="Aktuelt foto" style="width: 72px; height: 72px; object-fit: cover; border-radius: 6px; border: 1px solid var(--border-color);">
                    <div>
                        <button type="button" class="btn-small" onclick="document.getElementById('editFileInput').click()">
                            Skift fotografi...
                        </button>
                        <div id="editImageStatus" style="font-family: var(--font-mono); font-size: 11px; color: var(--text-muted); margin-top: 4px;">
                            Behold nuværende foto, eller vælg et nyt
                        </div>
                    </div>
                </div>
            </div>

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
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 6px; margin-bottom: 6px;">
                    <label class="form-label" for="editContentInput" style="margin-bottom: 0;">Noter / Refleksion</label>
                    <div class="tw-toolbar" style="display: flex; gap: 4px;">
                        <button type="button" class="btn-small" style="padding: 3px 8px; font-weight: 700;" onclick="applyTypewriterFormat('editContentInput', '**')" title="Fed (**tekst**)">B</button>
                        <button type="button" class="btn-small" style="padding: 3px 8px; font-style: italic;" onclick="applyTypewriterFormat('editContentInput', '*')" title="Kursiv (*tekst*)">I</button>
                        <button type="button" class="btn-small" style="padding: 3px 8px; text-decoration: underline;" onclick="applyTypewriterFormat('editContentInput', '__')" title="Understreget (__tekst__)">U</button>
                        <button type="button" class="btn-small" style="padding: 3px 8px; color: var(--accent-red); border-color: var(--accent-red); font-weight: 600;" onclick="applyTypewriterFormat('editContentInput', '==')" title="Rødt farvebånd (==tekst==)">● RØD</button>
                    </div>
                </div>
                <textarea id="editContentInput" class="form-control" style="min-height: 90px;"></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Tags (Maks 3)</label>
                <div id="editSelectedTagsContainer" class="selected-tags-container"></div>
                <div style="display: flex; gap: 8px; margin-bottom: 8px;">
                    <input type="text" id="editTagInput" class="form-control" placeholder="Tilføj tag..." onkeydown="handleEditTagInputKey(event)">
                    <button type="button" class="btn-small" onclick="addEditTagFromInput()" style="white-space: nowrap;">+ Tilføj</button>
                </div>
                <div id="editRecentTagsContainer" class="recent-tags-chips"></div>
            </div>
            <div class="form-group">
                <label class="form-label">Kameradata &amp; Optik (EXIF)</label>
                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 8px; margin-bottom: 8px;">
                    <input type="text" id="editExifCamera" class="form-control" placeholder="Kamera / Optik / Film" title="Kamera, objektiv eller filmtype">
                    <input type="text" id="editExifFocal" class="form-control" placeholder="f.eks. 35mm" title="Brændvidde">
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px;">
                    <input type="text" id="editExifAperture" class="form-control" placeholder="f.eks. ƒ/2.8" title="Blænde">
                    <input type="text" id="editExifShutter" class="form-control" placeholder="f.eks. 1/250s" title="Lukkertid">
                    <input type="text" id="editExifIso" class="form-control" placeholder="f.eks. ISO 400" title="ISO">
                </div>
            </div>
            <div class="form-group" style="margin-top: 14px;">
                <label class="form-label">Delingskort (Sociale medier)</label>
                <div class="og-style-selector" id="editOgStyleSelector">
                    <button type="button" class="og-style-btn" data-style="polaroid" onclick="setEditOgStyle('polaroid')">
                        <span class="og-style-icon">📷</span>
                        <span class="og-style-label">Polaroid</span>
                    </button>
                    <button type="button" class="og-style-btn" data-style="darkroom" onclick="setEditOgStyle('darkroom')">
                        <span class="og-style-icon">🎞️</span>
                        <span class="og-style-label">35mm Film</span>
                    </button>
                    <button type="button" class="og-style-btn" data-style="notebook" onclick="setEditOgStyle('notebook')">
                        <span class="og-style-icon">📓</span>
                        <span class="og-style-label">Notesbog</span>
                    </button>
                    <button type="button" class="og-style-btn" data-style="raw" onclick="setEditOgStyle('raw')">
                        <span class="og-style-icon">🖼️</span>
                        <span class="og-style-label">Rent foto</span>
                    </button>
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
        <span class="tab-icon">[ ⌀ ]</span>
        <span>Nyt</span>
    </button>
    <button class="tab-btn" onclick="switchView('history')">
        <span class="tab-icon">[ ≡ ]</span>
        <span>Indlæg</span>
    </button>
    <button class="tab-btn" onclick="switchView('settings')">
        <span class="tab-icon">[ ⚙ ]</span>
        <span>Indstillinger</span>
    </button>
</nav>

<script>
// PWA Service Worker registrering
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('sw.js').catch(err => console.log('SW reg error:', err));
}

let compressedImageData = null;
let editCompressedImageData = null;
const DRAFT_STORAGE_KEY = 'logbog_draft_v1';

// Initialiser standard dato
function setDefaultDate() {
    const now = new Date();
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
        setTimeout(renderOgLivePreview, 60);
    } else if (viewName === 'history') {
        document.getElementById('viewHistory').classList.add('active');
        document.getElementById('bottomNav').style.display = 'flex';
        document.querySelectorAll('.tab-btn')[1].classList.add('active');
        loadPostsHistory(true);
    } else if (viewName === 'settings') {
        document.getElementById('viewSettings').classList.add('active');
        document.getElementById('bottomNav').style.display = 'flex';
        document.querySelectorAll('.tab-btn')[2].classList.add('active');
        loadSecuritySettings();
    }
    window.scrollTo(0, 0);
}

// Robust API-kald hjælper (sender altid token i Authorization header eller JSON-body, aldrig i URL)
async function apiFetch(action, options = {}) {
    const token = localStorage.getItem('logbog_token') || '';
    const method = options.method || 'GET';
    const headers = options.headers || {};

    if (token) {
        headers['Authorization'] = 'Bearer ' + token;
    }

    let url = 'api.php?action=' + encodeURIComponent(action);

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
        } else if (!body) {
            headers['Content-Type'] = 'application/json';
            body = JSON.stringify({ token: token });
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
            setDefaultDate();
            restoreDraft();
            switchView('compose');
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
            setDefaultDate();
            restoreDraft();
            switchView('compose');
        }
    } catch (err) {
        showAlert(err.message || 'Forkert PIN-kode', 'error');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Lås op';
    }
}

// Tilstand for EXIF, Tags og Kladde
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

// Tilstand for delingskort (Social Share / Open Graph)
let currentOgStyle = 'polaroid';
let editOgStyle = 'polaroid';
let cachedPreviewImg = null;

function setOgStyle(style) {
    currentOgStyle = style;
    document.querySelectorAll('#ogStyleSelector .og-style-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.style === style);
    });
    saveDraft();
    renderOgLivePreview();
}

function setEditOgStyle(style) {
    editOgStyle = style;
    document.querySelectorAll('#editOgStyleSelector .og-style-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.style === style);
    });
}

// Auto-gem kladde i localStorage så refleksioner ikke går tabt
function saveDraft() {
    const draft = {
        title: document.getElementById('titleInput')?.value || '',
        content: document.getElementById('contentInput')?.value || '',
        location: document.getElementById('locInput')?.value || '',
        camera: document.getElementById('exifCameraInput')?.value || '',
        focal: document.getElementById('exifFocalInput')?.value || '',
        og_style: currentOgStyle,
        tags: selectedTags
    };
    const hasContent = draft.title.trim() || draft.content.trim() || draft.location.trim() || draft.tags.length > 0;
    const badge = document.getElementById('draftBadge');
    if (hasContent) {
        localStorage.setItem(DRAFT_STORAGE_KEY, JSON.stringify(draft));
        if (badge) badge.style.display = 'inline';
    } else {
        localStorage.removeItem(DRAFT_STORAGE_KEY);
        if (badge) badge.style.display = 'none';
    }
}

function restoreDraft() {
    try {
        const raw = localStorage.getItem(DRAFT_STORAGE_KEY);
        if (!raw) return;
        const draft = JSON.parse(raw);
        if (!draft) return;
        if (draft.title) document.getElementById('titleInput').value = draft.title;
        if (draft.content) document.getElementById('contentInput').value = draft.content;
        if (draft.location) document.getElementById('locInput').value = draft.location;
        if (draft.camera && !document.getElementById('exifCameraInput').value) {
            document.getElementById('exifCameraInput').value = draft.camera;
        }
        if (draft.focal && !document.getElementById('exifFocalInput').value) {
            document.getElementById('exifFocalInput').value = draft.focal;
        }
        if (draft.og_style) {
            setOgStyle(draft.og_style);
        }
        if (Array.isArray(draft.tags) && draft.tags.length > 0) {
            selectedTags = draft.tags.slice(0, 3);
        }
        const badge = document.getElementById('draftBadge');
        if (badge) badge.style.display = 'inline';
    } catch (e) {}
}

function clearDraft(resetFields = false) {
    localStorage.removeItem(DRAFT_STORAGE_KEY);
    const badge = document.getElementById('draftBadge');
    if (badge) badge.style.display = 'none';
    if (resetFields) {
        document.getElementById('titleInput').value = '';
        document.getElementById('contentInput').value = '';
        document.getElementById('locInput').value = '';
        selectedTags = [];
        setOgStyle('polaroid');
        renderSelectedTags();
        showAlert('Kladde ryddet', 'success', 2000);
    }
}

// Live forhåndsvisning af Open Graph delingskort (1200 x 630 Canvas)
function renderOgLivePreview() {
    const canvas = document.getElementById('ogPreviewCanvas');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    const w = 1200;
    const h = 630;

    const title = (document.getElementById('titleInput')?.value || '').trim() || 'Log ' + (document.getElementById('dateInput')?.value ? new Date(document.getElementById('dateInput').value).toLocaleDateString('da-DK') : 'i dag');
    const location = (document.getElementById('locInput')?.value || '').trim();
    const dateVal = document.getElementById('dateInput')?.value ? new Date(document.getElementById('dateInput').value) : new Date();
    const dateStr = dateVal.toLocaleDateString('da-DK', { day: '2-digit', month: 'short', year: 'numeric' }).toUpperCase().replace('.', '');
    const content = (document.getElementById('contentInput')?.value || '').trim();
    const exifCam = (document.getElementById('exifCameraInput')?.value || '').trim();

    function drawPreviewWithImage(img) {
        ctx.clearRect(0, 0, w, h);

        if (currentOgStyle === 'polaroid') {
            // Mørk skrivebordsflade (#231c17)
            ctx.fillStyle = '#231c17';
            ctx.fillRect(0, 0, w, h);
            ctx.strokeStyle = '#16110d';
            ctx.lineWidth = 16;
            ctx.strokeRect(8, 8, w - 16, h - 16);

            // Polaroid kort (590 x 554)
            const cardW = 590, cardH = 554;
            const cardX = (w - cardW) / 2;
            const cardY = (h - cardH) / 2;

            // Skygge
            ctx.fillStyle = 'rgba(10, 8, 6, 0.45)';
            ctx.fillRect(cardX + 4, cardY + 6, cardW, cardH);

            // Papir
            ctx.fillStyle = '#f8f5ee';
            ctx.fillRect(cardX, cardY, cardW, cardH);
            ctx.strokeStyle = '#dcd5c4';
            ctx.lineWidth = 1;
            ctx.strokeRect(cardX, cardY, cardW, cardH);

            // Billedareal
            const pm = 28;
            const pw = cardW - (pm * 2);
            const ph = 416;
            const px = cardX + pm;
            const py = cardY + pm;

            if (img) {
                drawImageCover(ctx, img, px, py, pw, ph);
            } else {
                ctx.fillStyle = '#1c1916';
                ctx.fillRect(px, py, pw, ph);
                ctx.fillStyle = '#8f867a';
                ctx.font = '16px monospace';
                ctx.textAlign = 'center';
                ctx.fillText('[ VÆLG ET FOTOGRAFI OVENFOR ]', px + pw / 2, py + ph / 2);
            }
            ctx.strokeStyle = '#cfc6b2';
            ctx.strokeRect(px, py, pw, ph);

            // Titel på hagen
            ctx.textAlign = 'left';
            ctx.fillStyle = '#1a1715';
            ctx.font = 'bold 24px Courier, monospace';
            const displayTitle = title.length > 34 ? title.substring(0, 33) + '…' : title;
            ctx.fillText(displayTitle, px + 4, py + ph + 54);

            // Stempel i terracotta-rød (#b53424)
            ctx.textAlign = 'right';
            ctx.fillStyle = '#b53424';
            ctx.font = 'bold 16px Courier, monospace';
            const stamp = dateStr + (location ? ' · ' + location.toUpperCase() : '');
            ctx.fillText(stamp.length > 32 ? stamp.substring(0, 31) + '…' : stamp, px + pw, py + ph + 88);

        } else if (currentOgStyle === 'darkroom') {
            // Sort mørkekammer
            ctx.fillStyle = '#0e0e0e';
            ctx.fillRect(0, 0, w, h);

            // 35mm perforeringer
            const spW = 16, spH = 26, spacing = 46;
            for (let x = 18; x < w - 20; x += spacing) {
                ctx.fillStyle = '#070707';
                ctx.fillRect(x, 14, spW, spH);
                ctx.fillRect(x, h - 14 - spH, spW, spH);
                ctx.strokeStyle = '#222222';
                ctx.strokeRect(x, 14, spW, spH);
                ctx.strokeRect(x, h - 14 - spH, spW, spH);
            }

            // Filmkant markeringer
            ctx.fillStyle = '#d68331';
            ctx.font = '14px Courier, monospace';
            ctx.textAlign = 'left';
            const camLabel = exifCam ? exifCam.toUpperCase() : '35MM ANALOG';
            ctx.fillText(`▶ 24A     ${camLabel}     SAFETY FILM`, 36, 52);
            ctx.fillText(`24     SYS.LOG // FOTO     ${dateStr}`, 36, h - 48);

            // Central ramme
            const fx = 80, fy = 66, fw = 1040, fh = 496;
            if (img) {
                drawImageCover(ctx, img, fx, fy, fw, fh);
            } else {
                ctx.fillStyle = '#151515';
                ctx.fillRect(fx, fy, fw, fh);
                ctx.fillStyle = '#8f867a';
                ctx.font = '16px monospace';
                ctx.textAlign = 'center';
                ctx.fillText('[ VÆLG ET FOTOGRAFI OVENFOR ]', fx + fw / 2, fy + fh / 2);
            }
            ctx.strokeStyle = '#282828';
            ctx.strokeRect(fx, fy, fw, fh);

            // Dæmpet bjælke
            const grad = ctx.createLinearGradient(0, fy + fh - 80, 0, fy + fh);
            grad.addColorStop(0, 'rgba(10, 10, 10, 0)');
            grad.addColorStop(1, 'rgba(10, 10, 10, 0.85)');
            ctx.fillStyle = grad;
            ctx.fillRect(fx, fy + fh - 80, fw, 80);

            ctx.textAlign = 'left';
            ctx.fillStyle = '#f5f2eb';
            ctx.font = 'bold 22px Courier, monospace';
            ctx.fillText(title, fx + 24, fy + fh - 38);

            if (location || exifCam) {
                ctx.fillStyle = '#b0a79a';
                ctx.font = '14px Courier, monospace';
                ctx.fillText([location, exifCam].filter(Boolean).join(' · '), fx + 24, fy + fh - 16);
            }

        } else if (currentOgStyle === 'notebook') {
            // Split: Venstre foto (620), Højre manila (580)
            const splitX = 620;
            if (img) {
                drawImageCover(ctx, img, 0, 0, splitX, h);
            } else {
                ctx.fillStyle = '#211d1a';
                ctx.fillRect(0, 0, splitX, h);
                ctx.fillStyle = '#8f867a';
                ctx.font = '16px monospace';
                ctx.textAlign = 'center';
                ctx.fillText('[ VÆLG ET FOTOGRAFI ]', splitX / 2, h / 2);
            }

            // Manila papir
            ctx.fillStyle = '#fbf7ee';
            ctx.fillRect(splitX, 0, w - splitX, h);
            ctx.strokeStyle = '#dad1c0';
            ctx.strokeRect(splitX, 0, w - splitX, h);

            // Spiralhuller
            for (let y = 28; y < h - 10; y += 42) {
                ctx.beginPath();
                ctx.arc(splitX + 16, y, 7, 0, Math.PI * 2);
                ctx.fillStyle = '#261e18';
                ctx.fill();
                ctx.strokeStyle = '#b9afa0';
                ctx.stroke();
            }

            // Tekst på arket
            const cx = splitX + 54;
            ctx.textAlign = 'left';
            ctx.fillStyle = '#b53424';
            ctx.font = 'bold 15px Courier, monospace';
            const hdr = 'LOG // ' + dateStr + (location ? ' · ' + location.toUpperCase() : '');
            ctx.fillText(hdr, cx, 85);

            ctx.strokeStyle = '#ded5c2';
            ctx.beginPath();
            ctx.moveTo(cx, 105);
            ctx.lineTo(w - 48, 105);
            ctx.stroke();

            ctx.fillStyle = '#181513';
            ctx.font = 'bold 36px Georgia, serif';
            wrapTextCanvas(ctx, title, cx, 160, w - cx - 48, 44, 3);

            if (content) {
                const cleanContent = content.replace(/[*_=#]/g, '').replace(/\s+/g, ' ').trim();
                ctx.fillStyle = '#544b43';
                ctx.font = '15px Courier, monospace';
                wrapTextCanvas(ctx, cleanContent, cx, 310, w - cx - 48, 26, 4);
            }

            if (exifCam) {
                ctx.fillStyle = '#918579';
                ctx.font = '13px Courier, monospace';
                ctx.fillText('⌀ ' + exifCam, cx, h - 55);
            }

        } else if (currentOgStyle === 'raw') {
            // Rent fotografi i 1200 x 630
            ctx.fillStyle = '#12100e';
            ctx.fillRect(0, 0, w, h);
            if (img) {
                const aspect = img.width / img.height;
                if (aspect >= 1.45) {
                    drawImageCover(ctx, img, 0, 0, w, h);
                } else {
                    const fitW = h * aspect;
                    const ox = (w - fitW) / 2;
                    ctx.drawImage(img, ox, 0, fitW, h);
                }
            } else {
                ctx.fillStyle = '#8f867a';
                ctx.font = '16px monospace';
                ctx.textAlign = 'center';
                ctx.fillText('[ VÆLG ET FOTOGRAFI OVENFOR ]', w / 2, h / 2);
            }
        }
    }

    if (compressedImageData) {
        if (!cachedPreviewImg || cachedPreviewImg.src !== compressedImageData) {
            cachedPreviewImg = new Image();
            cachedPreviewImg.onload = () => drawPreviewWithImage(cachedPreviewImg);
            cachedPreviewImg.src = compressedImageData;
        } else {
            drawPreviewWithImage(cachedPreviewImg);
        }
    } else {
        drawPreviewWithImage(null);
    }
}

function drawImageCover(ctx, img, x, y, w, h) {
    const imgAspect = img.width / img.height;
    const boxAspect = w / h;
    let sx, sy, sw, sh;
    if (imgAspect > boxAspect) {
        sw = img.height * boxAspect;
        sh = img.height;
        sx = (img.width - sw) / 2;
        sy = 0;
    } else {
        sw = img.width;
        sh = img.width / boxAspect;
        sx = 0;
        sy = (img.height - sh) / 2;
    }
    ctx.drawImage(img, sx, sy, sw, sh, x, y, w, h);
}

function wrapTextCanvas(ctx, text, x, y, maxWidth, lineHeight, maxLines) {
    const words = text.split(/\s+/);
    let line = '';
    let lineCount = 0;
    let currY = y;

    for (let n = 0; n < words.length; n++) {
        const testLine = line + (line ? ' ' : '') + words[n];
        const metrics = ctx.measureText(testLine);
        if (metrics.width > maxWidth && line !== '') {
            lineCount++;
            if (lineCount >= maxLines) {
                ctx.fillText(line + '…', x, currY);
                return;
            }
            ctx.fillText(line, x, currY);
            line = words[n];
            currY += lineHeight;
        } else {
            line = testLine;
        }
    }
    if (line && lineCount < maxLines) {
        ctx.fillText(line, x, currY);
    }
}

['titleInput', 'contentInput', 'locInput', 'dateInput', 'exifCameraInput', 'exifFocalInput'].forEach(id => {
    const el = document.getElementById(id);
    if (el) {
        el.addEventListener('input', () => {
            saveDraft();
            renderOgLivePreview();
        });
    }
});

// Skrivemaskine-formatering (Fed, Kursiv, Understreget & Rødt zebra-bånd)
function applyTypewriterFormat(textareaId, marker) {
    const ta = document.getElementById(textareaId);
    if (!ta) return;
    const start = ta.selectionStart ?? ta.value.length;
    const end = ta.selectionEnd ?? ta.value.length;
    const selected = ta.value.substring(start, end);
    const mLen = marker.length;

    // Hvis teksten allerede er pakket ind i samme markør, fjern markøren (toggle)
    if (
        start >= mLen &&
        ta.value.substring(start - mLen, start) === marker &&
        ta.value.substring(end, end + mLen) === marker
    ) {
        ta.value = ta.value.substring(0, start - mLen) + selected + ta.value.substring(end + mLen);
        ta.focus();
        ta.setSelectionRange(start - mLen, end - mLen);
    } else {
        const replacement = marker + selected + marker;
        ta.value = ta.value.substring(0, start) + replacement + ta.value.substring(end);
        ta.focus();
        // Behold markeringen på selve teksten indeni, så man let kan kombinere (f.eks. Fed + Rød)
        ta.setSelectionRange(start + mLen, start + mLen + selected.length);
    }
    if (textareaId === 'contentInput') {
        saveDraft();
    }
}

// Robust EXIF-udtrækker der benytter exifr UMD samt native fallback parser
async function extractExifFromFile(file) {
    let result = {
        camera: null,
        focal: null,
        aperture: null,
        shutter: null,
        iso: null,
        date: null,
        latitude: null,
        longitude: null
    };

    // 1. Prøv exifr biblioteket (fuld understøttelse af JPEG, HEIC, PNG, WebP, TIFF)
    if (window.exifr && typeof window.exifr.parse === 'function') {
        try {
            const data = await window.exifr.parse(file, {
                tiff: true,
                xmp: true,
                exif: true,
                gps: true,
                pick: [
                    'Make', 'Model', 'LensModel',
                    'FocalLength', 'FocalLengthIn35mmFormat',
                    'FNumber', 'ApertureValue', 
                    'ExposureTime', 'ShutterSpeedValue', 
                    'ISO', 'ISOSpeedRatings', 'PhotographicSensitivity', 
                    'DateTimeOriginal', 'CreateDate', 'ModifyDate',
                    'latitude', 'longitude'
                ]
            });

            if (data) {
                // Kamera & Objektiv
                const make = (data.Make || '').trim();
                const model = (data.Model || '').trim();
                const lens = (data.LensModel || '').trim();
                if (model) {
                    result.camera = (make && !model.toLowerCase().includes(make.toLowerCase()))
                        ? `${make} ${model}`
                        : model;
                } else if (lens) {
                    result.camera = lens;
                }

                // Brændvidde
                const fl = data.FocalLengthIn35mmFormat || data.FocalLength;
                if (fl && !isNaN(fl) && Number(fl) > 0) {
                    result.focal = Math.round(Number(fl)) + 'mm';
                }

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

                if (result.camera || result.focal || result.aperture || result.shutter || result.iso || result.date) {
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
                    const result = { camera: null, focal: null, aperture: null, shutter: null, iso: null, date: null };
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
                        } else if (tag === 0x0110 && !result.camera) {
                            const count = view.getUint32(entryOffset + 4, bigEndian);
                            const mOffset = count > 4 ? (tiffStart + view.getUint32(entryOffset + 8, bigEndian)) : (entryOffset + 8);
                            result.camera = readAsciiString(view, mOffset, Math.min(count, 48), length);
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
                            } else if (tag === 0x920A && !result.focal) {
                                if (valOffset + 8 <= length) {
                                    const num = view.getUint32(valOffset, bigEndian);
                                    const den = view.getUint32(valOffset + 4, bigEndian);
                                    if (num > 0 && den > 0) {
                                        result.focal = Math.round(num / den) + 'mm';
                                    }
                                }
                            } else if (tag === 0xA405 && !result.focal) {
                                const fl35 = view.getUint16(entryOffset + 8, bigEndian);
                                if (fl35 > 0) result.focal = fl35 + 'mm';
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

                    if (result.camera || result.focal || result.aperture || result.shutter || result.iso || result.date) {
                        return result;
                    }
                }
            }
        }

        offset += 2 + segLength;
    }
    return null;
}

function readAsciiString(view, offset, count, maxLen) {
    if (offset < 0 || offset + count > maxLen) return null;
    let str = '';
    for (let i = 0; i < count; i++) {
        const ch = view.getUint8(offset + i);
        if (ch === 0) break;
        if (ch >= 32 && ch <= 126) str += String.fromCharCode(ch);
    }
    return str.trim() || null;
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

// Skaler og optimer billede skånsomt (2400px @ 92% JPEG-kvalitet for at bevare filmkorn og toneovergange)
function compressImageFileToDataUrl(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onerror = () => reject(new Error('Kunne ikke læse billedfil'));
        reader.onload = function(event) {
            const img = new Image();
            img.onerror = () => reject(new Error('Ugyldigt billede'));
            img.onload = function() {
                const maxDimension = 2400;
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

                const dataUrl = canvas.toDataURL('image/jpeg', 0.92);
                const approxSizeKb = Math.round((dataUrl.length * 3 / 4) / 1024);
                resolve({ dataUrl, width, height, approxSizeKb });
            };
            img.src = event.target.result;
        };
        reader.readAsDataURL(file);
    });
}

// Reverse geocoding (bruges både ved EXIF-GPS fra foto og ved manuel GPS-knap)
async function reverseGeocodeCoords(lat, lon, fromPhotoExif = false) {
    const locInput = document.getElementById('locInput');
    const locBadge = document.getElementById('locOriginBadge');
    try {
        const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${encodeURIComponent(lat)}&lon=${encodeURIComponent(lon)}&zoom=14&addressdetails=1`, {
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
            if (locBadge) {
                locBadge.textContent = fromPhotoExif ? '✓ Fra foto-GPS' : '✓ Fra enheds-GPS';
                locBadge.style.color = 'var(--accent-red)';
            }
            saveDraft();
            return locName;
        }
    } catch (e) {}
    return null;
}

// Billedbehandling & Klient-optimering (Canvas)
async function handleImageSelected(e) {
    const file = e.target.files[0];
    if (!file) return;

    // 1. Udtræk EXIF direkte fra råfilen før canvas-komprimering
    try {
        const exifData = await extractExifFromFile(file);
        if (exifData) {
            currentExifData = exifData;

            if (exifData.camera) document.getElementById('exifCameraInput').value = exifData.camera;
            if (exifData.focal) document.getElementById('exifFocalInput').value = exifData.focal;
            if (exifData.aperture) document.getElementById('exifApertureInput').value = exifData.aperture;
            if (exifData.shutter) document.getElementById('exifShutterInput').value = exifData.shutter;
            if (exifData.iso) document.getElementById('exifIsoInput').value = exifData.iso;

            const parts = [exifData.camera, exifData.focal, exifData.aperture, exifData.shutter, exifData.iso].filter(Boolean);
            const badge = document.getElementById('exifStatusBadge');
            if (badge) {
                if (parts.length > 0) {
                    badge.textContent = '✓ Udlæst fra foto';
                    badge.style.color = 'var(--accent-red)';
                } else {
                    badge.textContent = 'Ingen EXIF i fil – indtast evt. manuelt';
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

            // Hvis fotoet indeholder GPS-koordinater, sæt koordinater OG slå automatisk stednavn op!
            if (typeof exifData.latitude === 'number' && typeof exifData.longitude === 'number') {
                document.getElementById('latInput').value = exifData.latitude;
                document.getElementById('lonInput').value = exifData.longitude;
                reverseGeocodeCoords(exifData.latitude, exifData.longitude, true);
            }
        }
    } catch (err) {
        console.warn('EXIF udtræk bemærkning:', err);
    }

    // 2. Skaler til 2400px @ 92% JPEG
    try {
        const res = await compressImageFileToDataUrl(file);
        compressedImageData = res.dataUrl;
        cachedPreviewImg = null;
        renderOgLivePreview();

        const preview = document.getElementById('imagePreview');
        preview.src = compressedImageData;
        preview.style.display = 'block';
        document.getElementById('photoPickerPrompt').style.display = 'none';

        const info = document.getElementById('imageResInfo');
        info.textContent = `Optimeret: ${res.width}×${res.height}px (92% kvalitet · ~${res.approxSizeKb} KB)`;
        info.style.display = 'block';
    } catch (err) {
        showAlert(err.message || 'Kunne ikke indlæse billede', 'error');
    }
}

// Tags håndtering i Compose (Sikker DOM-håndtering via data-tag attributter i stedet for inline JS-strenge)
async function loadRecentTags() {
    try {
        const res = await apiFetch('get_tags');
        if (res && res.tags) {
            recentTagsList = res.tags;
            renderRecentTags();
            renderEditRecentTags();
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
                    data-tag="${escapeHtml(t.name)}">
                #${escapeHtml(t.name)} ${isSelected ? '✓' : ''}
            </button>
        `;
    }).join('');

    container.querySelectorAll('.recent-tag-chip').forEach(btn => {
        btn.addEventListener('click', () => {
            const tag = btn.getAttribute('data-tag');
            if (tag) toggleTag(tag);
        });
    });
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
            <button type="button" class="btn-remove-tag" data-tag="${escapeHtml(t)}" title="Fjern">&times;</button>
        </span>
    `).join('');

    container.querySelectorAll('.btn-remove-tag').forEach(btn => {
        btn.addEventListener('click', () => {
            const tag = btn.getAttribute('data-tag');
            if (tag) removeTag(tag);
        });
    });

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
    saveDraft();
}

function removeTag(tag) {
    selectedTags = selectedTags.filter(t => t.toLowerCase() !== tag.toLowerCase());
    renderSelectedTags();
    saveDraft();
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

// Tags håndtering i Edit modal (inkl. klikbare tidligere tags)
function renderEditRecentTags() {
    const container = document.getElementById('editRecentTagsContainer');
    if (!container) return;
    if (recentTagsList.length === 0) {
        container.innerHTML = '';
        return;
    }
    container.innerHTML = recentTagsList.map(t => {
        const isSelected = editSelectedTags.some(st => st.toLowerCase() === t.name.toLowerCase());
        return `
            <button type="button"
                    class="recent-tag-chip ${isSelected ? 'active' : ''}"
                    data-tag="${escapeHtml(t.name)}">
                #${escapeHtml(t.name)} ${isSelected ? '✓' : ''}
            </button>
        `;
    }).join('');

    container.querySelectorAll('.recent-tag-chip').forEach(btn => {
        btn.addEventListener('click', () => {
            const tag = btn.getAttribute('data-tag');
            if (!tag) return;
            if (editSelectedTags.some(t => t.toLowerCase() === tag.toLowerCase())) {
                removeEditTag(tag);
            } else {
                addEditTag(tag);
            }
        });
    });
}

function renderEditSelectedTags() {
    const container = document.getElementById('editSelectedTagsContainer');
    if (!container) return;
    if (editSelectedTags.length === 0) {
        container.innerHTML = '<span style="font-size: 12px; color: var(--text-muted); font-style: italic;">Ingen tags</span>';
        renderEditRecentTags();
        return;
    }
    container.innerHTML = editSelectedTags.map(t => `
        <span class="tag-badge">
            #${escapeHtml(t)}
            <button type="button" class="btn-remove-tag" data-tag="${escapeHtml(t)}">&times;</button>
        </span>
    `).join('');

    container.querySelectorAll('.btn-remove-tag').forEach(btn => {
        btn.addEventListener('click', () => {
            const tag = btn.getAttribute('data-tag');
            if (tag) removeEditTag(tag);
        });
    });

    renderEditRecentTags();
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

// Hent telefonens aktuelle GPS Lokation
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
                const locName = await reverseGeocodeCoords(lat, lon, false);
                if (locName) {
                    showAlert(`⌖ Lokation fundet: ${locName}`, 'success', 2500);
                } else {
                    locInput.value = `${lat.toFixed(4)}, ${lon.toFixed(4)}`;
                }
            } finally {
                icon.textContent = '⌖';
                btn.disabled = false;
            }
        },
        (error) => {
            icon.textContent = '⌖';
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
        og_style: currentOgStyle,
        tags: selectedTags,
        exif_camera: (document.getElementById('exifCameraInput')?.value || '').trim(),
        exif_focal: (document.getElementById('exifFocalInput')?.value || '').trim(),
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
            clearDraft(false);
            renderSelectedTags();
            document.getElementById('exifCameraInput').value = '';
            document.getElementById('exifFocalInput').value = '';
            document.getElementById('exifApertureInput').value = '';
            document.getElementById('exifShutterInput').value = '';
            document.getElementById('exifIsoInput').value = '';
            const exifBadge = document.getElementById('exifStatusBadge');
            if (exifBadge) {
                exifBadge.textContent = 'Udlæses automatisk fra foto';
                exifBadge.style.color = 'var(--text-muted)';
            }
            const dateBadge = document.getElementById('dateOriginBadge');
            if (dateBadge) dateBadge.textContent = '';
            const locBadge = document.getElementById('locOriginBadge');
            if (locBadge) locBadge.textContent = '';

            document.getElementById('imagePreview').style.display = 'none';
            document.getElementById('photoPickerPrompt').style.display = 'block';
            document.getElementById('imageResInfo').style.display = 'none';
            setDefaultDate();
            loadRecentTags();
            setOgStyle('polaroid');
            cachedPreviewImg = null;
            renderOgLivePreview();
        }
    } catch (err) {
        showAlert(err.message || 'Fejl under gemning', 'error');
    } finally {
        saveBtn.disabled = false;
        saveBtn.textContent = 'Gem i logbog';
    }
}

// Historik-søgning, paginering og manglende datoer
let historyOffset = 0;
const HISTORY_LIMIT = 50;
let historySearchTimer = null;

function handleHistorySearchInput() {
    clearTimeout(historySearchTimer);
    historySearchTimer = setTimeout(() => {
        loadPostsHistory(true);
    }, 250);
}

function loadMoreHistory() {
    historyOffset += HISTORY_LIMIT;
    loadPostsHistory(false);
}

function prefillComposeDate(dateIso) {
    switchView('compose');
    document.getElementById('dateInput').value = dateIso + 'T12:00';
    const dateBadge = document.getElementById('dateOriginBadge');
    if (dateBadge) {
        dateBadge.textContent = 'Dato valgt: ' + dateIso;
        dateBadge.style.color = 'var(--accent-red)';
    }
    showAlert('Dato sat til ' + dateIso + ' – når du vælger et foto, opdateres klokkeslættet fra EXIF.', 'success', 3500);
}

async function loadPostsHistory(reset = true) {
    const container = document.getElementById('postsListContainer');
    const loadMoreBtn = document.getElementById('loadMoreHistoryBtn');
    const totalLabel = document.getElementById('historyTotalLabel');
    const q = (document.getElementById('historySearchInput')?.value || '').trim();

    if (reset) {
        historyOffset = 0;
        container.innerHTML = '<p style="font-family: var(--font-mono); font-size: 13px; color: var(--text-muted);">Henter indlæg...</p>';
    }

    try {
        const data = await apiFetch('get_posts', {
            params: { q: q, limit: HISTORY_LIMIT, offset: historyOffset }
        });

        // Vis manglende dage (seneste 14 dage) i toppen af Historik-fanen
        const missingBox = document.getElementById('missingDatesBox');
        const missingChips = document.getElementById('missingDatesChips');
        if (missingBox && missingChips && Array.isArray(data.missing_recent_dates)) {
            if (data.missing_recent_dates.length > 0 && !q) {
                missingBox.style.display = 'block';
                missingChips.innerHTML = data.missing_recent_dates.map(d => `
                    <button type="button" class="missing-date-chip" data-date="${escapeHtml(d.date_iso)}">
                        + ${escapeHtml(d.label)}
                    </button>
                `).join('');
                missingChips.querySelectorAll('.missing-date-chip').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const dIso = btn.getAttribute('data-date');
                        if (dIso) prefillComposeDate(dIso);
                    });
                });
            } else {
                missingBox.style.display = 'none';
            }
        }

        if (totalLabel && typeof data.total === 'number') {
            totalLabel.textContent = `${data.total} indlæg i alt`;
        }

        if (data && Array.isArray(data.posts)) {
            if (reset && data.posts.length === 0) {
                container.innerHTML = '<p style="font-family: var(--font-mono); font-size: 13px; color: var(--text-muted);">Ingen indlæg fundet.</p>';
                if (loadMoreBtn) loadMoreBtn.style.display = 'none';
                return;
            }

            let html = '';
            data.posts.forEach(p => {
                const imgUrl = '../uploads/' + encodeURIComponent(p.image_path);
                const dateStr = (p.published_at || '').substring(0, 10);
                const tagNames = (p.tags || []).map(t => '#' + t.name).join(' ');
                const postIdentifier = encodeURIComponent(p.uid || p.id);
                html += `
                    <div class="history-item" id="hist-${Number(p.id)}">
                        <img src="${imgUrl}" class="history-thumb" alt="">
                        <div class="history-info">
                            <div class="history-title">${escapeHtml(p.title)}</div>
                            <div class="history-date">[${escapeHtml(dateStr)}] ${p.location ? '• ' + escapeHtml(p.location) : ''}</div>
                            ${tagNames ? `<div style="font-family: var(--font-mono); font-size: 11px; color: var(--text-muted); margin-top: 2px;">${escapeHtml(tagNames)}</div>` : ''}
                        </div>
                        <div class="history-actions">
                            <a href="../post.php?p=${postIdentifier}" target="_blank" class="btn-small" style="text-decoration:none;">Vis</a>
                            <button type="button" onclick="openEditModal(${Number(p.id)})" class="btn-small">Rediger</button>
                            <button type="button" onclick="deletePost(${Number(p.id)})" class="btn-small btn-danger">Slet</button>
                        </div>
                    </div>
                `;
            });

            if (reset) {
                container.innerHTML = html;
            } else {
                container.insertAdjacentHTML('beforeend', html);
            }

            if (loadMoreBtn) {
                const shownCount = historyOffset + data.posts.length;
                loadMoreBtn.style.display = (shownCount < (data.total || 0)) ? 'block' : 'none';
            }
        }
    } catch (e) {
        if (reset) {
            container.innerHTML = `<p style="color: var(--accent-red);">${escapeHtml(e.message || 'Kunne ikke hente indlæg')}</p>`;
        }
    }
}

// Billedskift i Rediger-modalen
async function handleEditImageSelected(e) {
    const file = e.target.files[0];
    if (!file) return;

    try {
        const res = await compressImageFileToDataUrl(file);
        editCompressedImageData = res.dataUrl;
        document.getElementById('editImagePreview').src = editCompressedImageData;
        document.getElementById('editImageStatus').textContent = `✓ Nyt foto valgt (${res.width}×${res.height}px · ~${res.approxSizeKb} KB)`;

        const exifData = await extractExifFromFile(file);
        if (exifData) {
            if (exifData.camera && !document.getElementById('editExifCamera').value) {
                document.getElementById('editExifCamera').value = exifData.camera;
            }
            if (exifData.focal && !document.getElementById('editExifFocal').value) {
                document.getElementById('editExifFocal').value = exifData.focal;
            }
            if (exifData.aperture) document.getElementById('editExifAperture').value = exifData.aperture;
            if (exifData.shutter) document.getElementById('editExifShutter').value = exifData.shutter;
            if (exifData.iso) document.getElementById('editExifIso').value = exifData.iso;
        }
    } catch (err) {
        showAlert(err.message || 'Kunne ikke indlæse nyt billede', 'error');
    }
}

// Rediger indlæg modal logik
async function openEditModal(id) {
    try {
        if (recentTagsList.length === 0) {
            await loadRecentTags();
        }
        const data = await apiFetch('get_post', { params: { id: id } });
        if (!data || !data.post) return;
        const p = data.post;

        editCompressedImageData = null;
        document.getElementById('editFileInput').value = '';
        document.getElementById('editImagePreview').src = '../uploads/' + encodeURIComponent(p.image_path);
        document.getElementById('editImageStatus').textContent = 'Behold nuværende foto, eller vælg et nyt';

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

        document.getElementById('editExifCamera').value = p.exif_camera || '';
        document.getElementById('editExifFocal').value = p.exif_focal || '';
        document.getElementById('editExifAperture').value = p.exif_aperture || '';
        document.getElementById('editExifShutter').value = p.exif_shutter || '';
        document.getElementById('editExifIso').value = p.exif_iso || '';

        setEditOgStyle(p.og_style || 'polaroid');

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
        og_style: editOgStyle,
        tags: editSelectedTags,
        exif_camera: document.getElementById('editExifCamera').value,
        exif_focal: document.getElementById('editExifFocal').value,
        exif_aperture: document.getElementById('editExifAperture').value,
        exif_shutter: document.getElementById('editExifShutter').value,
        exif_iso: document.getElementById('editExifIso').value
    };

    if (editCompressedImageData) {
        payload.image_data = editCompressedImageData;
    }

    try {
        const data = await apiFetch('update_post', {
            method: 'POST',
            body: payload
        });
        if (data.success) {
            showAlert('✓ Indlæg opdateret!', 'success', 3000);
            closeEditModal();
            loadPostsHistory(true);
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
            loadRecentTags();
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

// Download database backup via POST + Bearer token
async function downloadDatabaseBackup() {
    const btn = document.getElementById('btnBackupDb');
    if (btn) btn.disabled = true;
    try {
        const token = localStorage.getItem('logbog_token') || '';
        const res = await fetch('api.php?action=backup', {
            method: 'POST',
            headers: {
                'Authorization': 'Bearer ' + token,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ token })
        });
        if (!res.ok) {
            throw new Error('Kunne ikke hente backup (' + res.status + ')');
        }
        const blob = await res.blob();
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        const dateStr = new Date().toISOString().slice(0, 10);
        a.href = url;
        a.download = `logbog_backup_${dateStr}.db`;
        document.body.appendChild(a);
        a.click();
        a.remove();
        URL.revokeObjectURL(url);
        showAlert('✓ Backup downloadet', 'success', 2500);
    } catch (e) {
        showAlert(e.message || 'Fejl under backup', 'error');
    } finally {
        if (btn) btn.disabled = false;
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
            if (data.token) {
                localStorage.setItem('logbog_token', data.token);
            }
            showAlert('✓ PIN-koden er ændret!', 'success', 3000);
            document.getElementById('newPinInput').value = '';
        }
    } catch (e) {
        showAlert(e.message || 'Fejl under ændring', 'error');
    }
}

// Ugyldiggør andre enheders sessioner
async function handleRevokeSessions() {
    try {
        const data = await apiFetch('revoke_sessions', { method: 'POST' });
        if (data.success) {
            if (data.token) {
                localStorage.setItem('logbog_token', data.token);
            }
            showAlert('✓ ' + data.message, 'success', 3500);
        }
    } catch (e) {
        showAlert(e.message || 'Fejl', 'error');
    }
}

// Log ud
async function handleLogout() {
    try {
        await apiFetch('logout', { method: 'POST' });
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
