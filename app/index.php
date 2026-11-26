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

        .tab-btn {
            position: relative;
        }

        .postcard-unread-badge {
            position: absolute;
            top: -2px;
            right: calc(50% - 22px);
            background: var(--accent);
            color: #fff;
            font-family: var(--font-mono);
            font-size: 10px;
            font-weight: 700;
            line-height: 1;
            padding: 2px 5px;
            border-radius: 999px;
        }

        .postcard-inbox-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 14px;
            margin-bottom: 12px;
        }

        .postcard-inbox-card.is-unread {
            border-left: 4px solid var(--accent);
        }

        .postcard-inbox-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
            font-family: var(--font-mono);
            font-size: 11px;
            color: var(--text-muted);
        }

        .postcard-inbox-post {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px;
            background: var(--bg-page);
            border: 1px dashed var(--border-color);
            border-radius: 6px;
            margin-bottom: 10px;
            text-decoration: none;
            color: var(--text-main);
        }

        .postcard-inbox-post img {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 4px;
        }

        .postcard-inbox-msg {
            font-size: 14px;
            line-height: 1.5;
            margin: 6px 0 10px;
            white-space: pre-wrap;
        }

        .postcard-inbox-actions {
            display: flex;
            gap: 8px;
            justify-content: flex-end;
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

        /* Håndskrift / Tegneflade (iPad + Apple Pencil) */
        .hw-toggle-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            flex-wrap: wrap;
        }
        .hw-panel {
            margin-top: 10px;
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            overflow: hidden;
        }
        .hw-panel.hw-fullscreen {
            position: fixed;
            inset: 0;
            z-index: 9999;
            margin: 0;
            border-radius: 0;
            border: none;
            display: flex;
            flex-direction: column;
            background: #e8e4da;
            padding-top: var(--safe-top);
            padding-bottom: var(--safe-bottom);
        }
        .hw-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 6px;
            padding: 8px 10px;
            background: var(--bg-color);
            border-bottom: 1px solid var(--border-color);
            user-select: none;
            -webkit-user-select: none;
        }
        .hw-tool-group {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding-right: 6px;
            border-right: 1px solid var(--border-color);
        }
        .hw-tool-group:last-child {
            border-right: none;
            padding-right: 0;
            margin-left: auto;
        }
        .hw-swatch {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            border: 2px solid transparent;
            cursor: pointer;
            padding: 0;
            box-shadow: inset 0 0 0 1px rgba(0,0,0,0.2);
            transition: transform 0.12s, border-color 0.12s;
        }
        .hw-swatch.active {
            border-color: var(--accent);
            transform: scale(1.14);
        }
        .hw-btn {
            background: var(--card-bg);
            color: var(--text-main);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            padding: 4px 8px;
            font-family: var(--font-mono);
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            line-height: 1.3;
            white-space: nowrap;
        }
        .hw-btn.active {
            background: var(--accent);
            color: var(--bg-color);
            border-color: var(--accent);
        }
        .hw-btn:disabled {
            opacity: 0.4;
            cursor: default;
        }
        .hw-paper-scroll {
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            background: #e8e4da;
            padding: 10px;
            display: flex;
            justify-content: center;
        }
        .hw-panel.hw-fullscreen .hw-paper-scroll {
            flex: 1;
            padding: 16px;
            align-items: flex-start;
        }
        .hw-paper-wrap {
            position: relative;
            width: 100%;
            max-width: 644px;
            background-color: #f9f7f1;
            border: 1px solid #d5cfc2;
            border-radius: 4px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
            overflow: hidden;
        }
        .hw-guide-canvas {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            display: block;
            pointer-events: none;
            z-index: 0;
        }
        .hw-canvas {
            position: relative;
            z-index: 1;
            display: block;
            width: 100%;
            height: auto;
            touch-action: none;
            cursor: crosshair;
            user-select: none;
            -webkit-user-select: none;
            -webkit-touch-callout: none;
        }
        .hw-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 8px;
            padding: 8px 10px;
            background: var(--bg-color);
            border-top: 1px solid var(--border-color);
            font-family: var(--font-mono);
            font-size: 11px;
            color: var(--text-muted);
        }
        .hw-status-badge {
            font-family: var(--font-mono);
            font-size: 11px;
            color: var(--text-muted);
        }
        .history-thumb-hw {
            background-color: #f9f7f1 !important;
            background-image: repeating-linear-gradient(
                to bottom,
                transparent 0,
                transparent 9px,
                rgba(110, 102, 92, 0.22) 9px,
                rgba(110, 102, 92, 0.22) 10px
            );
            object-fit: contain !important;
            object-position: center center !important;
            padding: 4px;
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
            
            <!-- Billedvælger (1–3 billeder / diptykon) -->
            <div class="form-group">
                <input 
                    type="file" 
                    id="fileInput" 
                    accept="image/*" 
                    multiple
                    style="display: none;" 
                    onchange="handleImageSelected(event)"
                >
                <div class="photo-picker" id="photoDropArea" onclick="document.getElementById('fileInput').click()">
                    <span class="photo-picker-icon">[ ⌀ FOTO ]</span>
                    <div class="photo-picker-text" id="photoPickerPrompt">
                        <strong>Tryk her for at vælge dagens fotografi (op til 3 til diptykon)</strong><br>
                        (Udlæser automatisk optagedato, GPS &amp; EXIF · valgfrit ved håndskreven indførsel)
                    </div>
                    <img id="imagePreview" class="photo-preview" alt="Preview">
                </div>
                <div id="selectedPhotosStrip" style="display: none; gap: 8px; flex-wrap: wrap; margin-top: 8px;"></div>
                <div id="imageResInfo" style="font-family: var(--font-mono); font-size: 11px; color: var(--text-muted); margin-top: 4px; display: none;"></div>
            </div>

            <!-- Kameradata (EXIF) inkl. Kamera, Optik og Brændvidde -->
            <div class="form-group">
                <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 6px;">
                    <label class="form-label" style="margin-bottom: 0;">Kameradata &amp; Optik (EXIF)</label>
                    <span id="exifStatusBadge" style="font-family: var(--font-mono); font-size: 11px; color: var(--text-muted);">
                        Udlæses automatisk fra foto
                    </span>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 8px;">
                    <input type="text" id="exifCameraInput" class="form-control" placeholder="Kamera (f.eks. FUJIFILM X-Pro3)" title="Kamera">
                    <input type="text" id="exifLensInput" class="form-control" placeholder="Objektiv (f.eks. XF 35mm f/1.4)" title="Objektiv">
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 8px;">
                    <input type="text" id="exifFocalInput" class="form-control" placeholder="35mm" title="Brændvidde">
                    <input type="text" id="exifApertureInput" class="form-control" placeholder="ƒ/2.8" title="Blænde">
                    <input type="text" id="exifShutterInput" class="form-control" placeholder="1/250s" title="Lukkertid">
                    <input type="text" id="exifIsoInput" class="form-control" placeholder="ISO 400" title="ISO">
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

            <!-- Håndskrift / Skitse (iPad & Apple Pencil) -->
            <div class="form-group">
                <div class="hw-toggle-bar">
                    <label class="form-label" style="margin-bottom: 0;">Håndskrift / Skitse</label>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span id="composeHwStatus" class="hw-status-badge"></span>
                        <button type="button" class="btn-small" id="composeHwToggleBtn" onclick="toggleHandwritingPanel('compose')">
                            ✎ Skriv / tegn i hånden
                        </button>
                    </div>
                </div>
                <div id="composeHwPanel" class="hw-panel" style="display: none;"></div>
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
                    <button type="button" class="og-style-btn" data-style="polaroid" onclick="setOgStyle('polaroid')">
                        <span class="og-style-icon">📷</span>
                        <span class="og-style-label">Polaroid</span>
                    </button>
                    <button type="button" class="og-style-btn" data-style="darkroom" onclick="setOgStyle('darkroom')">
                        <span class="og-style-icon">🎞️</span>
                        <span class="og-style-label">35mm Film</span>
                    </button>
                    <button type="button" class="og-style-btn active" data-style="notebook" onclick="setOgStyle('notebook')">
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

            <!-- Gem / Kladde knapper -->
            <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 10px;">
                <button type="button" class="btn-small" id="saveDraftBtn" onclick="submitPostWithStatus('draft')" style="padding: 14px 10px; font-family: var(--font-mono); font-size: 13px; font-weight: 600;">
                    [ GEM KLADDE ]
                </button>
                <button type="submit" class="btn-primary" id="savePostBtn">
                    Udgiv i logbog
                </button>
            </div>
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

    <!-- 3B. PRIVATE POSTKORT (Indbakke fra læsere) -->
    <div id="viewPostcards" class="view-section">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; gap: 10px;">
            <h3 style="font-family: var(--font-mono); font-size: 14px; text-transform: uppercase;">
                [ ✉ PRIVATE POSTKORT ]
            </h3>
            <button type="button" id="markAllPostcardsReadBtn" class="btn-small" onclick="markAllPostcardsRead()" style="display: none;">
                ✓ Marker alle som læst
            </button>
        </div>
        <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 14px;">
            Stille nik og hilsener sendt privat fra dine læsere. Kun synligt her for dig.
        </p>
        <div id="postcardsListContainer">
            <p style="font-family: var(--font-mono); font-size: 13px; color: var(--text-muted);">Indlæser postkort...</p>
        </div>
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
                    <label class="form-label">Nuværende PIN-kode</label>
                    <input type="password" id="currentPinInput" class="form-control" required placeholder="Nuværende PIN">
                </div>
                <div class="form-group">
                    <label class="form-label">Ny PIN-kode (min. 6 tegn)</label>
                    <input type="password" id="newPinInput" class="form-control" required minlength="6" placeholder="Ny PIN (min. 6 tegn)">
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

            <!-- Mulighed for at skifte eller fjerne billede ved redigering -->
            <div class="form-group">
                <label class="form-label">Fotografi</label>
                <input type="file" id="editFileInput" accept="image/*" style="display: none;" onchange="handleEditImageSelected(event)">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <img id="editImagePreview" src="" alt="Aktuelt foto" style="width: 72px; height: 72px; object-fit: cover; border-radius: 6px; border: 1px solid var(--border-color); background: #f9f7f1;">
                    <div>
                        <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                            <button type="button" class="btn-small" onclick="document.getElementById('editFileInput').click()">
                                Skift / tilføj foto...
                            </button>
                            <button type="button" id="editRemoveImageBtn" class="btn-small btn-danger" style="display: none;" onclick="markEditImageRemoved()">
                                Fjern foto
                            </button>
                        </div>
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
                <div class="hw-toggle-bar">
                    <label class="form-label" style="margin-bottom: 0;">Håndskrift / Skitse</label>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span id="editHwStatus" class="hw-status-badge"></span>
                        <button type="button" class="btn-small" id="editHwToggleBtn" onclick="toggleHandwritingPanel('edit')">
                            ✎ Tilføj / ret håndskrift
                        </button>
                    </div>
                </div>
                <div id="editHwPanel" class="hw-panel" style="display: none;"></div>
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
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 8px;">
                    <input type="text" id="editExifCamera" class="form-control" placeholder="Kamera" title="Kamera">
                    <input type="text" id="editExifLens" class="form-control" placeholder="Objektiv" title="Objektiv">
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 8px;">
                    <input type="text" id="editExifFocal" class="form-control" placeholder="35mm" title="Brændvidde">
                    <input type="text" id="editExifAperture" class="form-control" placeholder="ƒ/2.8" title="Blænde">
                    <input type="text" id="editExifShutter" class="form-control" placeholder="1/250s" title="Lukkertid">
                    <input type="text" id="editExifIso" class="form-control" placeholder="ISO 400" title="ISO">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label" for="editStatusSelect">Status</label>
                <select id="editStatusSelect" class="form-control">
                    <option value="published">Publiceret (synlig i logbogen)</option>
                    <option value="draft">Kladde (kun synlig her i appen)</option>
                </select>
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
    <button class="tab-btn" onclick="switchView('postcards')">
        <span class="tab-icon">[ ✉ ]</span>
        <span>Postkort</span>
        <span id="postcardsUnreadBadge" class="postcard-unread-badge" style="display: none;"></span>
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
let selectedImagesData = []; // Op til 3 billeder (diptykon / serie)
let editCompressedImageData = null;
let csrfToken = '';
const DRAFT_STORAGE_KEY = 'logbog_draft_v1';
// Ryd evt. tidligere token fra localStorage (vi bruger nu kun HttpOnly-cookie + CSRF)
try { localStorage.removeItem('logbog_token'); } catch (_) {}

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
    } else if (viewName === 'postcards') {
        document.getElementById('viewPostcards').classList.add('active');
        document.getElementById('bottomNav').style.display = 'flex';
        document.querySelectorAll('.tab-btn')[2].classList.add('active');
        loadPostcards();
    } else if (viewName === 'settings') {
        document.getElementById('viewSettings').classList.add('active');
        document.getElementById('bottomNav').style.display = 'flex';
        document.querySelectorAll('.tab-btn')[3].classList.add('active');
        loadSecuritySettings();
    }
    window.scrollTo(0, 0);
}

// Robust API-kald hjælper (bruger HttpOnly-cookie + sessions-bundet X-CSRF-Token)
async function apiFetch(action, options = {}) {
    const method = options.method || 'GET';
    const headers = options.headers || {};

    if (method === 'POST' && csrfToken) {
        headers['X-CSRF-Token'] = csrfToken;
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
            body = JSON.stringify(body);
        } else if (!body) {
            headers['Content-Type'] = 'application/json';
            body = JSON.stringify({});
        }
    }

    const res = await fetch(url, { method, headers, body, credentials: 'same-origin' });
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
            csrfToken = '';
            switchView('login');
            throw new Error(data.error || 'Session udløbet. Log ind igen.');
        }
        throw new Error(data.error || 'Fejl fra server (' + res.status + ')');
    }

    if (data && data.csrf_token) {
        csrfToken = data.csrf_token;
    }

    return data;
}

// Tjek om brugeren er autentificeret
async function checkAuthStatus() {
    try {
        const data = await apiFetch('check_auth');
        if (data && data.authenticated) {
            if (data.csrf_token) csrfToken = data.csrf_token;
            setDefaultDate();
            restoreDraft();
            switchView('compose');
            refreshPostcardBadge();
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
            if (data.csrf_token) csrfToken = data.csrf_token;
            showAlert('Logget ind!', 'success', 2000);
            document.getElementById('pinInput').value = '';
            setDefaultDate();
            restoreDraft();
            switchView('compose');
            refreshPostcardBadge();
        }
    } catch (err) {
        showAlert(err.message || 'Forkert PIN-kode', 'error');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Lås op';
    }
}

function updatePostcardBadge(unreadCount) {
    const badge = document.getElementById('postcardsUnreadBadge');
    const markAllBtn = document.getElementById('markAllPostcardsReadBtn');
    const count = Number(unreadCount) || 0;
    if (badge) {
        if (count > 0) {
            badge.textContent = count > 99 ? '99+' : String(count);
            badge.style.display = 'inline-block';
        } else {
            badge.style.display = 'none';
        }
    }
    if (markAllBtn) {
        markAllBtn.style.display = count > 0 ? 'inline-block' : 'none';
    }
}

async function refreshPostcardBadge() {
    try {
        const data = await apiFetch('get_postcards');
        if (data && data.success) {
            updatePostcardBadge(data.unread_count);
        }
    } catch (_) {}
}

function formatPostcardContact(raw) {
    if (!raw) return '';
    const trimmed = raw.trim();
    if (/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(trimmed)) {
        return `<a href="mailto:${escapeHtml(trimmed)}" style="color: var(--accent);">${escapeHtml(trimmed)}</a>`;
    }
    if (/^https?:\/\/[^\s]+$/i.test(trimmed)) {
        return `<a href="${escapeHtml(trimmed)}" target="_blank" rel="noopener noreferrer" style="color: var(--accent);">${escapeHtml(trimmed)}</a>`;
    }
    return escapeHtml(trimmed);
}

async function loadPostcards() {
    const container = document.getElementById('postcardsListContainer');
    if (!container) return;
    container.innerHTML = '<p style="font-family: var(--font-mono); font-size: 13px; color: var(--text-muted);">Indlæser postkort...</p>';

    try {
        const data = await apiFetch('get_postcards');
        updatePostcardBadge(data.unread_count);
        const list = data.postcards || [];

        if (list.length === 0) {
            container.innerHTML = '<p style="font-family: var(--font-mono); font-size: 13px; color: var(--text-muted);">Ingen postkort endnu. Når en læser sender et stille nik eller en hilsen, dukker den op her.</p>';
            return;
        }

        container.innerHTML = list.map(pc => {
            const isUnread = pc.is_read === 0;
            const kindLabel = pc.kind === 'note' ? '✉ Postkort' : '♡ Stille nik';
            const sender = pc.sender_name ? escapeHtml(pc.sender_name) : 'Anonym læser';
            const contactHtml = pc.sender_contact ? ` · ${formatPostcardContact(pc.sender_contact)}` : '';
            const thumbHtml = pc.post_thumb_url ? `<img src="${escapeHtml(pc.post_thumb_url)}" alt="" loading="lazy">` : '';
            const msgHtml = pc.message ? `<div class="postcard-inbox-msg">${escapeHtml(pc.message)}</div>` : '';
            const readBtnHtml = isUnread
                ? `<button type="button" class="btn-small" onclick="markPostcardRead(${pc.id})">✓ Marker læst</button>`
                : '';

            return `
                <div class="postcard-inbox-card ${isUnread ? 'is-unread' : ''}" id="postcard-card-${pc.id}">
                    <div class="postcard-inbox-meta">
                        <span><strong>${kindLabel}</strong> · ${sender}${contactHtml}</span>
                        <span>${escapeHtml(pc.created_at || '')}</span>
                    </div>
                    <a class="postcard-inbox-post" href="${escapeHtml(pc.post_url || '#')}" target="_blank" rel="noopener noreferrer">
                        ${thumbHtml}
                        <div>
                            <div style="font-size: 13px; font-weight: 600;">${escapeHtml(pc.post_title || 'Indlæg')}</div>
                            <div style="font-family: var(--font-mono); font-size: 11px; color: var(--text-muted);">REF ${escapeHtml(pc.post_uid || '')}</div>
                        </div>
                    </a>
                    ${msgHtml}
                    <div class="postcard-inbox-actions">
                        ${readBtnHtml}
                        <button type="button" class="btn-small btn-danger" onclick="deletePostcard(${pc.id})">Slet</button>
                    </div>
                </div>
            `;
        }).join('');
    } catch (err) {
        container.innerHTML = `<p style="color: var(--accent-red);">${escapeHtml(err.message || 'Kunne ikke hente postkort')}</p>`;
    }
}

async function markPostcardRead(id) {
    try {
        const data = await apiFetch('mark_postcard_read', { method: 'POST', body: { id } });
        updatePostcardBadge(data.unread_count);
        loadPostcards();
    } catch (err) {
        showAlert(err.message || 'Kunne ikke opdatere postkort', 'error');
    }
}

async function markAllPostcardsRead() {
    try {
        const data = await apiFetch('mark_postcard_read', { method: 'POST', body: { all: true } });
        updatePostcardBadge(data.unread_count);
        loadPostcards();
    } catch (err) {
        showAlert(err.message || 'Kunne ikke opdatere postkort', 'error');
    }
}

async function deletePostcard(id) {
    if (!confirm('Vil du slette dette postkort?')) return;
    try {
        const data = await apiFetch('delete_postcard', { method: 'POST', body: { id } });
        updatePostcardBadge(data.unread_count);
        loadPostcards();
    } catch (err) {
        showAlert(err.message || 'Kunne ikke slette postkort', 'error');
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
let currentOgStyle = 'notebook';
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
            ctx.font = 'bold 24px "Courier Prime", Courier, monospace';
            const displayTitle = title.length > 34 ? title.substring(0, 33) + '…' : title;
            ctx.fillText(displayTitle, px + 4, py + ph + 54);

            // Stempel i terracotta-rød (#b53424)
            ctx.textAlign = 'right';
            ctx.fillStyle = '#b53424';
            ctx.font = 'bold 16px "Courier Prime", Courier, monospace';
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
            ctx.font = '14px "Courier Prime", Courier, monospace';
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
            ctx.font = 'bold 22px "Courier Prime", Courier, monospace';
            ctx.fillText(title, fx + 24, fy + fh - 38);

            if (location || exifCam) {
                ctx.fillStyle = '#b0a79a';
                ctx.font = '14px "Courier Prime", Courier, monospace';
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
            ctx.font = 'bold 15px "Courier Prime", Courier, monospace';
            const hdr = 'LOG // ' + dateStr + (location ? ' · ' + location.toUpperCase() : '');
            ctx.fillText(hdr, cx, 85);

            ctx.strokeStyle = '#ded5c2';
            ctx.beginPath();
            ctx.moveTo(cx, 105);
            ctx.lineTo(w - 48, 105);
            ctx.stroke();

            ctx.fillStyle = '#181513';
            ctx.font = 'bold 36px "Fraunces", Georgia, serif';
            wrapTextCanvas(ctx, title, cx, 160, w - cx - 48, 44, 3);

            if (content) {
                const cleanContent = content.replace(/[*_=#]/g, '').replace(/\s+/g, ' ').trim();
                ctx.fillStyle = '#544b43';
                ctx.font = '15px "Courier Prime", Courier, monospace';
                wrapTextCanvas(ctx, cleanContent, cx, 310, w - cx - 48, 26, 4);
            }

            if (exifCam) {
                ctx.fillStyle = '#918579';
                ctx.font = '13px "Courier Prime", Courier, monospace';
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
    } else if (typeof handwritingPads !== 'undefined' && handwritingPads.compose && handwritingPads.compose.hasInk()) {
        const pad = handwritingPads.compose;
        const tmp = document.createElement('canvas');
        tmp.width = pad.canvas.width;
        tmp.height = pad.canvas.height;
        const tCtx = tmp.getContext('2d');
        tCtx.fillStyle = '#f9f7f1';
        tCtx.fillRect(0, 0, tmp.width, tmp.height);
        if (pad.guideCanvas) tCtx.drawImage(pad.guideCanvas, 0, 0);
        tCtx.drawImage(pad.committedCanvas || pad.canvas, 0, 0);
        drawPreviewWithImage(tmp);
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
                // Kamera & Objektiv separat
                const make = (data.Make || '').trim();
                const model = (data.Model || '').trim();
                const lens = (data.LensModel || '').trim();
                if (model) {
                    result.camera = (make && !model.toLowerCase().includes(make.toLowerCase()))
                        ? `${make} ${model}`
                        : model;
                }
                if (lens) {
                    result.lens = lens;
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

// Smart hybrid billedhåndtering:
// Behold originalen direkte (100% Fuji-farver/skarphed), hvis den er <= 2400px og < 4 MB i JPEG/PNG/WebP.
// Ellers skaler skånsomt til 2400px @ 92% JPEG.
function compressImageFileToDataUrl(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onerror = () => reject(new Error('Kunne ikke læse billedfil'));
        reader.onload = function(event) {
            const rawDataUrl = event.target.result;
            const img = new Image();
            img.onerror = () => reject(new Error('Ugyldigt billede (HEIC kræver Safari eller eksport til JPEG)'));
            img.onload = function() {
                const maxDimension = 2400;
                const maxDirectBytes = 4 * 1024 * 1024;
                const supportedDirectTypes = ['image/jpeg', 'image/png', 'image/webp'];
                let width = img.width;
                let height = img.height;

                if (
                    width <= maxDimension &&
                    height <= maxDimension &&
                    file.size <= maxDirectBytes &&
                    supportedDirectTypes.includes(file.type)
                ) {
                    const approxSizeKb = Math.round(file.size / 1024);
                    resolve({ dataUrl: rawDataUrl, width, height, approxSizeKb, originalPreserved: true });
                    return;
                }

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
                resolve({ dataUrl, width, height, approxSizeKb, originalPreserved: false });
            };
            img.src = rawDataUrl;
        };
        reader.readAsDataURL(file);
    });
}

function renderSelectedPhotosStrip() {
    const strip = document.getElementById('selectedPhotosStrip');
    const preview = document.getElementById('imagePreview');
    const prompt = document.getElementById('photoPickerPrompt');
    if (!strip) return;

    if (selectedImagesData.length === 0) {
        strip.style.display = 'none';
        strip.innerHTML = '';
        preview.style.display = 'none';
        prompt.style.display = 'block';
        compressedImageData = null;
        cachedPreviewImg = null;
        renderOgLivePreview();
        return;
    }

    compressedImageData = selectedImagesData[0].dataUrl;
    preview.src = compressedImageData;
    preview.style.display = 'block';
    prompt.style.display = 'none';
    cachedPreviewImg = null;
    renderOgLivePreview();

    if (selectedImagesData.length === 1) {
        strip.style.display = 'none';
        strip.innerHTML = '';
    } else {
        strip.style.display = 'flex';
        strip.innerHTML = selectedImagesData.map((item, idx) => `
            <div style="position: relative; width: 64px; height: 64px; border: 1px solid var(--border-color); border-radius: 6px; overflow: hidden;">
                <img src="${item.dataUrl}" style="width: 100%; height: 100%; object-fit: cover;">
                <button type="button" onclick="removeSelectedPhoto(${idx}); event.stopPropagation();" style="position: absolute; top: 2px; right: 2px; background: rgba(0,0,0,0.75); color: #fff; border: none; border-radius: 50%; width: 18px; height: 18px; font-size: 11px; line-height: 18px; cursor: pointer;">×</button>
            </div>
        `).join('');
    }
}

function removeSelectedPhoto(idx) {
    selectedImagesData.splice(idx, 1);
    renderSelectedPhotosStrip();
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

// Billedbehandling (1-3 billeder / diptykon) & smart hybrid-optimering
async function handleImageSelected(e) {
    const files = Array.from(e.target.files || []).slice(0, 3);
    if (files.length === 0) return;

    const primaryFile = files[0];

    // 1. Udtræk EXIF direkte fra råfilen før evt. skalering
    try {
        const exifData = await extractExifFromFile(primaryFile);
        if (exifData) {
            currentExifData = exifData;

            if (exifData.camera) document.getElementById('exifCameraInput').value = exifData.camera;
            if (exifData.lens && document.getElementById('exifLensInput')) document.getElementById('exifLensInput').value = exifData.lens;
            if (exifData.focal) document.getElementById('exifFocalInput').value = exifData.focal;
            if (exifData.aperture) document.getElementById('exifApertureInput').value = exifData.aperture;
            if (exifData.shutter) document.getElementById('exifShutterInput').value = exifData.shutter;
            if (exifData.iso) document.getElementById('exifIsoInput').value = exifData.iso;

            const parts = [exifData.camera, exifData.lens, exifData.focal, exifData.aperture, exifData.shutter, exifData.iso].filter(Boolean);
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

            if (typeof exifData.latitude === 'number' && typeof exifData.longitude === 'number') {
                document.getElementById('latInput').value = exifData.latitude;
                document.getElementById('lonInput').value = exifData.longitude;
                reverseGeocodeCoords(exifData.latitude, exifData.longitude, true);
            }
        }
    } catch (err) {
        console.warn('EXIF udtræk bemærkning:', err);
    }

    // 2. Behandl billeder (smart hybrid)
    try {
        selectedImagesData = [];
        for (const f of files) {
            const res = await compressImageFileToDataUrl(f);
            selectedImagesData.push(res);
        }
        renderSelectedPhotosStrip();

        const first = selectedImagesData[0];
        const info = document.getElementById('imageResInfo');
        const modeLabel = first.originalPreserved ? 'Original bevaret' : 'Skaleret 92% JPEG';
        const countLabel = selectedImagesData.length > 1 ? ` · ${selectedImagesData.length} fotos (diptykon)` : '';
        info.textContent = `${modeLabel}: ${first.width}×${first.height}px (~${first.approxSizeKb} KB)${countLabel}`;
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

// Opret nyt indlæg (eller gem som kladde)
async function handleCreatePost(e) {
    e.preventDefault();
    await submitPostWithStatus('published');
}

async function submitPostWithStatus(status = 'published') {
    const imagesPayload = selectedImagesData.length > 0
        ? selectedImagesData.map(x => x.dataUrl)
        : (compressedImageData ? [compressedImageData] : []);

    const hwPad = getHandwritingPad('compose');
    const hwPng = hwPad && hwPad.hasInk() ? hwPad.exportPngDataUrl() : null;
    const hwJson = hwPng ? hwPad.exportJson() : null;

    if (imagesPayload.length === 0 && !hwPng) {
        showAlert('Vælg mindst ét billede – eller lav en håndskreven indførsel først', 'error');
        return;
    }

    const saveBtn = document.getElementById('savePostBtn');
    const draftBtn = document.getElementById('saveDraftBtn');
    saveBtn.disabled = true;
    if (draftBtn) draftBtn.disabled = true;
    saveBtn.textContent = status === 'draft' ? 'Gemmer kladde...' : 'Udgiver...';

    const payload = {
        title: document.getElementById('titleInput').value,
        content: document.getElementById('contentInput').value,
        location: document.getElementById('locInput').value,
        latitude: document.getElementById('latInput').value,
        longitude: document.getElementById('lonInput').value,
        published_at: document.getElementById('dateInput').value,
        status: status,
        images_data: imagesPayload,
        handwriting_image_data: hwPng || '',
        handwriting_data: hwJson,
        og_style: currentOgStyle,
        tags: selectedTags,
        exif_camera: (document.getElementById('exifCameraInput')?.value || '').trim(),
        exif_lens: (document.getElementById('exifLensInput')?.value || '').trim(),
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
            showAlert(status === 'draft' ? '✓ Kladde gemt! Du finder den under "Indlæg".' : '✓ Dit indlæg er udgivet i logbogen!', 'success', 4000);
            document.getElementById('postForm').reset();
            compressedImageData = null;
            selectedImagesData = [];
            currentExifData = null;
            selectedTags = [];
            if (hwPad) {
                hwPad.reset();
                closeHandwritingPanel('compose');
            }
            clearDraft(false);
            renderSelectedTags();
            renderSelectedPhotosStrip();
            document.getElementById('exifCameraInput').value = '';
            if (document.getElementById('exifLensInput')) document.getElementById('exifLensInput').value = '';
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
            setOgStyle('notebook');
            cachedPreviewImg = null;
            renderOgLivePreview();
        }
    } catch (err) {
        showAlert(err.message || 'Fejl under gemning', 'error');
    } finally {
        saveBtn.disabled = false;
        if (draftBtn) draftBtn.disabled = false;
        saveBtn.textContent = 'Udgiv i logbog';
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
                const hasPhoto = !!(p.image_path && p.image_path.trim() !== '');
                const hasHw = !!(p.has_handwriting || p.handwriting_image);
                const imgUrl = hasPhoto
                    ? ('../uploads/' + encodeURIComponent(p.image_path))
                    : (p.handwriting_image ? ('../uploads/' + encodeURIComponent(p.handwriting_image)) : '');
                const thumbClass = hasPhoto ? 'history-thumb' : 'history-thumb history-thumb-hw';
                const dateStr = (p.published_at || '').substring(0, 10);
                const tagNames = (p.tags || []).map(t => '#' + t.name).join(' ');
                const postIdentifier = encodeURIComponent(p.uid || p.id);
                const isDraft = p.status === 'draft';
                const draftBadge = isDraft ? `<span style="font-family: var(--font-mono); font-size: 10px; color: var(--accent-red); border: 1px dashed var(--accent-red); padding: 1px 5px; margin-left: 6px;">KLADDE</span>` : '';
                const hwBadge = hasHw ? `<span style="font-family: var(--font-mono); font-size: 10px; color: var(--text-muted); border: 1px solid var(--border-color); padding: 1px 5px; margin-left: 6px;">✎ HÅNDSKRIFT</span>` : '';
                html += `
                    <div class="history-item" id="hist-${Number(p.id)}">
                        ${imgUrl ? `<img src="${imgUrl}" class="${thumbClass}" alt="">` : `<div class="history-thumb history-thumb-hw" style="display:flex;align-items:center;justify-content:center;font-family:var(--font-mono);font-size:18px;color:#5c5852;">✎</div>`}
                        <div class="history-info">
                            <div class="history-title">${escapeHtml(p.title)}${draftBadge}${hwBadge}</div>
                            <div class="history-date">[${escapeHtml(dateStr)}] ${p.location ? '• ' + escapeHtml(p.location) : ''}</div>
                            ${tagNames ? `<div style="font-family: var(--font-mono); font-size: 11px; color: var(--text-muted); margin-top: 2px;">${escapeHtml(tagNames)}</div>` : ''}
                        </div>
                        <div class="history-actions">
                            <a href="../post/${postIdentifier}" target="_blank" class="btn-small" style="text-decoration:none;">Vis</a>
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

let editRemoveImages = false;
let editHadExistingHandwriting = false;

function markEditImageRemoved() {
    editRemoveImages = true;
    editCompressedImageData = null;
    document.getElementById('editFileInput').value = '';
    const preview = document.getElementById('editImagePreview');
    preview.src = '';
    preview.style.display = 'none';
    const rmBtn = document.getElementById('editRemoveImageBtn');
    if (rmBtn) rmBtn.style.display = 'none';
    document.getElementById('editImageStatus').textContent = 'Foto fjernes ved gemning (kræver håndskrift)';
}

// Billedskift i Rediger-modalen
async function handleEditImageSelected(e) {
    const file = e.target.files[0];
    if (!file) return;

    try {
        const res = await compressImageFileToDataUrl(file);
        editCompressedImageData = res.dataUrl;
        editRemoveImages = false;
        const preview = document.getElementById('editImagePreview');
        preview.src = editCompressedImageData;
        preview.style.display = 'block';
        const rmBtn = document.getElementById('editRemoveImageBtn');
        if (rmBtn) rmBtn.style.display = 'inline-block';
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
        editRemoveImages = false;
        editHadExistingHandwriting = !!(p.handwriting_image);
        document.getElementById('editFileInput').value = '';

        const preview = document.getElementById('editImagePreview');
        const rmBtn = document.getElementById('editRemoveImageBtn');
        if (p.image_path) {
            preview.src = '../uploads/' + encodeURIComponent(p.image_path);
            preview.style.display = 'block';
            if (rmBtn) rmBtn.style.display = 'inline-block';
            document.getElementById('editImageStatus').textContent = 'Behold nuværende foto, skift eller fjern det';
        } else {
            preview.src = '';
            preview.style.display = 'none';
            if (rmBtn) rmBtn.style.display = 'none';
            document.getElementById('editImageStatus').textContent = 'Intet foto (ren håndskreven indførsel)';
        }

        // Indlæs eksisterende håndskrift i editPad så streger kan rettes
        const editPad = getHandwritingPad('edit');
        closeHandwritingPanel('edit');
        if (editPad) {
            editPad.reset();
            if (p.handwriting_data) {
                try {
                    const parsedHw = typeof p.handwriting_data === 'string'
                        ? JSON.parse(p.handwriting_data)
                        : p.handwriting_data;
                    editPad.loadJson(parsedHw);
                } catch (err) {
                    console.warn('Kunne ikke indlæse håndskriftsdata:', err);
                }
            }
            editPad.dirty = false;
            updateHandwritingStatus('edit');
        }

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
        if (document.getElementById('editExifLens')) document.getElementById('editExifLens').value = p.exif_lens || '';
        document.getElementById('editExifFocal').value = p.exif_focal || '';
        document.getElementById('editExifAperture').value = p.exif_aperture || '';
        document.getElementById('editExifShutter').value = p.exif_shutter || '';
        document.getElementById('editExifIso').value = p.exif_iso || '';
        if (document.getElementById('editStatusSelect')) document.getElementById('editStatusSelect').value = p.status || 'published';

        setEditOgStyle(p.og_style || 'notebook');

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
        status: document.getElementById('editStatusSelect') ? document.getElementById('editStatusSelect').value : 'published',
        og_style: editOgStyle,
        tags: editSelectedTags,
        exif_camera: document.getElementById('editExifCamera').value,
        exif_lens: document.getElementById('editExifLens') ? document.getElementById('editExifLens').value : '',
        exif_focal: document.getElementById('editExifFocal').value,
        exif_aperture: document.getElementById('editExifAperture').value,
        exif_shutter: document.getElementById('editExifShutter').value,
        exif_iso: document.getElementById('editExifIso').value
    };

    if (editCompressedImageData) {
        payload.image_data = editCompressedImageData;
    } else if (editRemoveImages) {
        payload.remove_images = true;
    }

    const editPad = getHandwritingPad('edit');
    if (editPad && editPad.dirty) {
        if (editPad.hasInk()) {
            const png = editPad.exportPngDataUrl();
            if (png) {
                payload.handwriting_image_data = png;
                payload.handwriting_data = editPad.exportJson();
            } else if (editHadExistingHandwriting) {
                payload.clear_handwriting = true;
            }
        } else if (editHadExistingHandwriting) {
            payload.clear_handwriting = true;
        }
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

// Download database backup via POST + HttpOnly cookie + CSRF
async function downloadDatabaseBackup() {
    const btn = document.getElementById('btnBackupDb');
    if (btn) btn.disabled = true;
    try {
        const res = await fetch('api.php?action=backup', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'X-CSRF-Token': csrfToken,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({})
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
    const currentPin = document.getElementById('currentPinInput').value;
    const newPin = document.getElementById('newPinInput').value;

    try {
        const data = await apiFetch('update_pin', {
            method: 'POST',
            body: { current_pin: currentPin, new_pin: newPin }
        });
        if (data.success) {
            if (data.csrf_token) csrfToken = data.csrf_token;
            showAlert('✓ PIN-koden er ændret!', 'success', 3000);
            document.getElementById('currentPinInput').value = '';
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
            if (data.csrf_token) csrfToken = data.csrf_token;
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
    csrfToken = '';
    switchView('login');
}

// =============================================================
// Håndskrift / Skitse-tegneflade (iPad + Apple Pencil / Mus)
// =============================================================
class HandwritingPad {
    constructor(containerEl, modeKey) {
        this.container = containerEl;
        this.modeKey = modeKey;
        this.logicalW = 644; // 1:1 med logbogens indre tekstbredde på skrivebord
        this.lineHeight = 32; // 1:1 med logbogens 32px-papirlinjer
        this.minLines = 6;
        this.maxLines = 56;
        this.logicalH = 12 * this.lineHeight;
        this.scale = 2;
        this.exportScale = 3; // 3x Retina-eksport for knivskarpe blækstreger

        this.strokes = [];
        this.redoStack = [];
        this.currentStroke = null;
        this.activePointerId = null;

        this.color = '#1e1c1a';
        this.size = 3.2;
        this.penStyle = 'fountain'; // 'fountain' | 'fineliner'
        this.tool = 'pen'; // 'pen' | 'eraser'
        this.guide = 'lines'; // 'lines' | 'dots' | 'blank'
        this.pencilOnly = false;
        this.isFullscreen = false;
        this.dirty = false;

        this.committedCanvas = document.createElement('canvas');
        this.committedCtx = this.committedCanvas.getContext('2d');

        this.buildUI();
        this.bindEvents();
        this.resizeCanvas();
        this.redraw();
        this.updateToolbarState();
    }

    buildUI() {
        this.container.innerHTML = `
            <div class="hw-toolbar">
                <div class="hw-tool-group" data-group="colors">
                    <button type="button" class="hw-swatch active" data-color="#1e1c1a" style="background:#1e1c1a;" title="Sort blæk"></button>
                    <button type="button" class="hw-swatch" data-color="#1b3a6b" style="background:#1b3a6b;" title="Mørkeblå fyldepen"></button>
                    <button type="button" class="hw-swatch" data-color="#a62c22" style="background:#a62c22;" title="Stempelrød"></button>
                    <button type="button" class="hw-swatch" data-color="#5c5852" style="background:#5c5852;" title="Blyantgrå"></button>
                </div>
                <div class="hw-tool-group" data-group="sizes">
                    <button type="button" class="hw-btn" data-action="penstyle" title="Skift mellem Fyldepen (dynamisk blækflow) og Fineliner (jævn streg)">Fyldepen</button>
                    <button type="button" class="hw-btn" data-size="2.0" title="Fin streg">Fin</button>
                    <button type="button" class="hw-btn active" data-size="3.2" title="Normal streg">Normal</button>
                    <button type="button" class="hw-btn" data-size="5.4" title="Bred streg">Bred</button>
                    <button type="button" class="hw-btn" data-tool="eraser" title="Viskelæder">Visk</button>
                </div>
                <div class="hw-tool-group" data-group="history">
                    <button type="button" class="hw-btn" data-action="undo" title="Fortryd">↶</button>
                    <button type="button" class="hw-btn" data-action="redo" title="Gendan">↷</button>
                    <button type="button" class="hw-btn" data-action="guide" title="Skift papirlinjer">Linjer</button>
                    <button type="button" class="hw-btn" data-action="pencil" title="Kun Apple Pencil (ignorer fingre/håndflade)">✎ Pencil</button>
                </div>
                <div class="hw-tool-group" data-group="view">
                    <button type="button" class="hw-btn" data-action="fullscreen" title="Fuld skærm til iPad">⤢ Fuld</button>
                    <button type="button" class="hw-btn" data-action="clear" style="color:var(--accent-red);" title="Ryd hele arket">Ryd</button>
                </div>
            </div>
            <div class="hw-paper-scroll">
                <div class="hw-paper-wrap">
                    <canvas class="hw-guide-canvas"></canvas>
                    <canvas class="hw-canvas"></canvas>
                </div>
            </div>
            <div class="hw-footer">
                <span class="hw-hint">Linjerne matcher logbogens papir 1:1</span>
                <div style="display:flex; gap:6px; align-items:center;">
                    <span class="hw-lines-label">12 linjer</span>
                    <button type="button" class="hw-btn" data-action="shrink">− Mindre</button>
                    <button type="button" class="hw-btn" data-action="expand">+ Mere</button>
                </div>
            </div>
        `;

        this.paperWrap = this.container.querySelector('.hw-paper-wrap');
        this.guideCanvas = this.container.querySelector('.hw-guide-canvas');
        this.guideCtx = this.guideCanvas.getContext('2d');
        this.canvas = this.container.querySelector('.hw-canvas');
        this.ctx = this.canvas.getContext('2d');
        this.hintEl = this.container.querySelector('.hw-hint');
        this.linesLabelEl = this.container.querySelector('.hw-lines-label');
    }

    bindEvents() {
        // Farver
        this.container.querySelectorAll('.hw-swatch').forEach(btn => {
            btn.addEventListener('click', () => {
                this.color = btn.dataset.color;
                this.tool = 'pen';
                this.updateToolbarState();
            });
        });

        // Stregtykkelser
        this.container.querySelectorAll('[data-size]').forEach(btn => {
            btn.addEventListener('click', () => {
                this.size = parseFloat(btn.dataset.size) || 3.6;
                this.tool = 'pen';
                this.updateToolbarState();
            });
        });

        // Viskelæder
        const eraserBtn = this.container.querySelector('[data-tool="eraser"]');
        if (eraserBtn) {
            eraserBtn.addEventListener('click', () => {
                this.tool = this.tool === 'eraser' ? 'pen' : 'eraser';
                this.updateToolbarState();
            });
        }

        // Handlinger
        const penStyleBtn = this.container.querySelector('[data-action="penstyle"]');
        if (penStyleBtn) {
            penStyleBtn.addEventListener('click', () => {
                const order = ['fountain', 'fineliner', 'pencil'];
                this.penStyle = order[(order.indexOf(this.penStyle) + 1) % order.length];
                this.tool = 'pen';
                this.updateToolbarState();
            });
        }
        this.container.querySelector('[data-action="undo"]').addEventListener('click', () => this.undo());
        this.container.querySelector('[data-action="redo"]').addEventListener('click', () => this.redo());
        this.container.querySelector('[data-action="guide"]').addEventListener('click', () => this.cycleGuide());
        this.container.querySelector('[data-action="pencil"]').addEventListener('click', () => this.togglePencilOnly());
        this.container.querySelector('[data-action="fullscreen"]').addEventListener('click', () => this.toggleFullscreen());
        this.container.querySelector('[data-action="clear"]').addEventListener('click', () => this.clearWithConfirm());
        this.container.querySelector('[data-action="shrink"]').addEventListener('click', () => this.shrinkHeight());
        this.container.querySelector('[data-action="expand"]').addEventListener('click', () => this.expandHeight());

        // Pointer Events til pen / touch / mus
        this.canvas.addEventListener('pointerdown', (e) => this.onPointerDown(e));
        this.canvas.addEventListener('pointermove', (e) => this.onPointerMove(e));
        this.canvas.addEventListener('pointerup', (e) => this.onPointerUp(e));
        this.canvas.addEventListener('pointercancel', (e) => this.onPointerUp(e));

        // Undgå iOS Safari tekstmarkering under tegning
        this.canvas.addEventListener('touchstart', (e) => {
            if (!this.pencilOnly) e.preventDefault();
        }, { passive: false });
    }

    resizeCanvas() {
        const w = this.logicalW * this.scale;
        const h = this.logicalH * this.scale;
        this.canvas.width = w;
        this.canvas.height = h;
        this.canvas.style.aspectRatio = `${this.logicalW} / ${this.logicalH}`;

        if (this.committedCanvas) {
            this.committedCanvas.width = w;
            this.committedCanvas.height = h;
        }

        if (this.guideCanvas) {
            this.guideCanvas.width = w;
            this.guideCanvas.height = h;
            this.drawGuides();
        }
    }

    drawGuides() {
        if (!this.guideCtx) return;
        const ctx = this.guideCtx;
        const w = this.logicalW * this.scale;
        const h = this.logicalH * this.scale;
        ctx.clearRect(0, 0, w, h);
        if (this.guide === 'blank') return;

        ctx.save();
        ctx.scale(this.scale, this.scale);
        if (this.guide === 'lines') {
            // Præcis 32px linjeafstand så hver grundlinje flugter med .log-entry på logbogen
            ctx.strokeStyle = 'rgba(110, 102, 92, 0.22)';
            ctx.lineWidth = 1;
            for (let y = this.lineHeight; y <= this.logicalH; y += this.lineHeight) {
                const py = y - 0.5;
                ctx.beginPath();
                ctx.moveTo(0, py);
                ctx.lineTo(this.logicalW, py);
                ctx.stroke();
            }
        } else if (this.guide === 'dots') {
            ctx.fillStyle = 'rgba(100, 94, 84, 0.32)';
            for (let y = this.lineHeight; y <= this.logicalH; y += this.lineHeight) {
                for (let x = 22; x < this.logicalW; x += 24) {
                    ctx.beginPath();
                    ctx.arc(x, y - 0.5, 1, 0, Math.PI * 2);
                    ctx.fill();
                }
            }
        }
        ctx.restore();
    }

    getPointFromEvent(e) {
        const rect = this.canvas.getBoundingClientRect();
        const x = ((e.clientX - rect.left) / rect.width) * this.logicalW;
        const y = ((e.clientY - rect.top) / rect.height) * this.logicalH;
        let p = 0.5;
        if (e.pointerType === 'pen' && typeof e.pressure === 'number' && e.pressure > 0) {
            p = Math.max(0.15, Math.min(1.0, e.pressure));
        }
        const rx = Math.round(x * 10) / 10;
        const ry = Math.round(y * 10) / 10;
        const rp = Math.round(p * 100) / 100;

        // Gem hældning (tilt) ved blyant, så Apple Pencil kan skyggelægge med siden af pennen
        if (this.tool === 'pen' && this.penStyle === 'pencil' && e.pointerType === 'pen') {
            const tx = typeof e.tiltX === 'number' ? e.tiltX : 0;
            const ty = typeof e.tiltY === 'number' ? e.tiltY : 0;
            let tilt = Math.min(1, Math.hypot(tx, ty) / 68);
            if (tilt === 0 && typeof e.altitudeAngle === 'number' && e.altitudeAngle > 0 && e.altitudeAngle < Math.PI / 2) {
                tilt = Math.min(1, Math.max(0, 1 - (e.altitudeAngle / (Math.PI / 2))));
            }
            const angle = Math.atan2(ty, tx);
            return [rx, ry, rp, Math.round(tilt * 100) / 100, Math.round(angle * 100) / 100];
        }
        return [rx, ry, rp];
    }

    onPointerDown(e) {
        if (e.button !== undefined && e.button !== 0 && e.pointerType === 'mouse') return;
        if (this.pencilOnly && e.pointerType !== 'pen') return;
        if (this.activePointerId !== null) return;

        e.preventDefault();
        this.activePointerId = e.pointerId;
        try { this.canvas.setPointerCapture(e.pointerId); } catch (_) {}

        const pt = this.getPointFromEvent(e);
        this.currentStroke = {
            tool: this.tool,
            style: this.penStyle,
            color: this.color,
            size: this.tool === 'eraser' ? 22 : this.size,
            points: [pt]
        };
        this.strokes.push(this.currentStroke);
        this.redoStack = [];
        this.dirty = true;
        this.renderActiveFrame();
    }

    onPointerMove(e) {
        if (this.activePointerId !== e.pointerId || !this.currentStroke) return;
        e.preventDefault();

        // Brug direkte pointermove (uden WebKit getCoalescedEvents, som på iPadOS kan levere punkter ude af rækkefølge)
        const pt = this.getPointFromEvent(e);
        const pts = this.currentStroke.points;
        const prev = pts[pts.length - 1];
        const dx = pt[0] - prev[0];
        const dy = pt[1] - prev[1];
        if (dx * dx + dy * dy < 1.8) return;
        pts.push(pt);
        this.renderActiveFrame();
    }

    onPointerUp(e) {
        if (this.activePointerId !== e.pointerId) return;
        try { this.canvas.releasePointerCapture(e.pointerId); } catch (_) {}
        const finished = this.currentStroke;
        this.activePointerId = null;
        this.currentStroke = null;
        if (finished && this.committedCtx) {
            this.renderStroke(this.committedCtx, finished, this.scale);
            this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
            this.ctx.drawImage(this.committedCanvas, 0, 0);
        } else {
            this.redraw();
        }
        this.updateToolbarState();
        updateHandwritingStatus(this.modeKey);
        if (this.modeKey === 'compose' && typeof renderOgLivePreview === 'function') {
            renderOgLivePreview();
        }
    }

    renderActiveFrame() {
        const w = this.canvas.width;
        const h = this.canvas.height;
        this.ctx.clearRect(0, 0, w, h);
        if (this.committedCanvas) {
            this.ctx.drawImage(this.committedCanvas, 0, 0);
        }
        if (this.currentStroke) {
            this.renderStroke(this.ctx, this.currentStroke, this.scale);
        }
    }

    renderDot(ctx, stroke, pt, scale) {
        ctx.save();
        ctx.scale(scale, scale);
        if (stroke.tool === 'eraser') {
            ctx.globalCompositeOperation = 'destination-out';
            ctx.fillStyle = 'rgba(0,0,0,1)';
        } else {
            ctx.globalCompositeOperation = 'source-over';
            ctx.fillStyle = stroke.color || '#1e1c1a';
        }
        const p = pt[2] ?? 0.5;
        const r = (stroke.tool === 'eraser' ? stroke.size : stroke.size * (0.55 + p * 0.75)) * 0.5;
        ctx.beginPath();
        ctx.arc(pt[0], pt[1], Math.max(0.65, r), 0, Math.PI * 2);
        ctx.fill();
        ctx.restore();
    }

    buildInkPolygon(rawPoints, stroke) {
        const isFountain = (stroke.style || 'fountain') !== 'fineliner';
        const streamline = isFountain ? 0.42 : 0.28;
        const baseR = (stroke.size || 3.2) * 0.5;

        // 1. Blød udjævning (streamlining) + beskyttelse mod mikro-tilbagespring (jitter)
        const smooth = [];
        let runningLen = 0;
        let lastDirX = 0, lastDirY = 0;
        for (let i = 0; i < rawPoints.length; i++) {
            const raw = rawPoints[i];
            const pRaw = raw[2] ?? 0.5;
            if (i === 0) {
                smooth.push({ x: raw[0], y: raw[1], p: pRaw, dist: 0, len: 0 });
                continue;
            }
            const prev = smooth[smooth.length - 1];
            const t = (i === rawPoints.length - 1) ? 0.78 : (1 - streamline);
            const sx = prev.x + (raw[0] - prev.x) * t;
            const sy = prev.y + (raw[1] - prev.y) * t;
            const dx = sx - prev.x;
            const dy = sy - prev.y;
            const dist = Math.hypot(dx, dy);
            if (dist < 0.8 && i < rawPoints.length - 1) continue;

            // Ignorer pludseligt 180°-mikrohop over < 4px (forhindrer savtakket normal-flip)
            const ux = dx / (dist || 1);
            const uy = dy / (dist || 1);
            if (smooth.length >= 2 && dist < 4.0 && (ux * lastDirX + uy * lastDirY) < -0.25 && i < rawPoints.length - 1) {
                continue;
            }
            lastDirX = ux;
            lastDirY = uy;

            runningLen += dist;
            const sp = prev.p + (pRaw - prev.p) * 0.34;
            smooth.push({ x: sx, y: sy, p: sp, dist, len: runningLen });
        }

        if (smooth.length < 2) return null;
        const totalLen = runningLen;
        const taperStart = isFountain ? Math.min(9, totalLen * 0.22) : 0;
        const taperEnd = isFountain ? Math.min(15, totalLen * 0.32) : 0;

        // 2. Beregn dynamisk radius pr. punkt (tryk + hastighed + blød tilspidsning)
        const radii = new Array(smooth.length);
        let prevVel = 1.0;
        for (let i = 0; i < smooth.length; i++) {
            const pt = smooth[i];
            let pressFactor = 1.0;
            let velFactor = 1.0;
            if (isFountain) {
                pressFactor = 0.46 + Math.pow(pt.p, 0.85) * 0.96;
                const rawVel = Math.max(0.68, Math.min(1.08, 1.06 - (pt.dist / (stroke.size * 4.2)) * 0.34));
                velFactor = prevVel + (rawVel - prevVel) * 0.35;
                prevVel = velFactor;
            } else {
                pressFactor = 0.88 + pt.p * 0.22;
            }

            let taper = 1.0;
            if (taperStart > 0 && pt.len < taperStart) {
                taper *= 0.48 + 0.52 * Math.sin((pt.len / taperStart) * Math.PI * 0.5);
            }
            const rem = totalLen - pt.len;
            if (taperEnd > 0 && rem < taperEnd) {
                taper *= 0.26 + 0.74 * Math.sin((rem / taperEnd) * Math.PI * 0.5);
            }
            radii[i] = Math.max(0.32, baseR * pressFactor * velFactor * taper);
        }

        // 3. Venstre og højre blækkontur med glatte normaler
        const rightPts = [];
        const leftPts = [];
        let firstTx = 1, firstTy = 0;
        let lastTx = 1, lastTy = 0;

        for (let i = 0; i < smooth.length; i++) {
            let tx = 0, ty = 0;
            if (i > 0) {
                const dx = smooth[i].x - smooth[i - 1].x;
                const dy = smooth[i].y - smooth[i - 1].y;
                const d = Math.hypot(dx, dy) || 1;
                tx += dx / d;
                ty += dy / d;
            }
            if (i < smooth.length - 1) {
                const dx = smooth[i + 1].x - smooth[i].x;
                const dy = smooth[i + 1].y - smooth[i].y;
                const d = Math.hypot(dx, dy) || 1;
                tx += dx / d;
                ty += dy / d;
            }
            const tLen = Math.hypot(tx, ty);
            if (tLen < 0.001) {
                tx = lastTx;
                ty = lastTy;
            } else {
                tx /= tLen;
                ty /= tLen;
            }
            if (i === 0) { firstTx = tx; firstTy = ty; }
            lastTx = tx;
            lastTy = ty;

            const nx = -ty;
            const ny = tx;
            const r = radii[i];
            rightPts.push([smooth[i].x + nx * r, smooth[i].y + ny * r]);
            leftPts.push([smooth[i].x - nx * r, smooth[i].y - ny * r]);
        }

        // 4. Afrundede ender ved start og slut
        const poly = [];
        const pStart = smooth[0];
        const rStart = radii[0];
        const startBaseAngle = Math.atan2(-firstTx, firstTy); // -normal
        const capSteps = 5;
        for (let s = 1; s < capSteps; s++) {
            const a = startBaseAngle - (Math.PI * s) / capSteps;
            poly.push([pStart.x + Math.cos(a) * rStart, pStart.y + Math.sin(a) * rStart]);
        }

        for (let i = 0; i < rightPts.length; i++) poly.push(rightPts[i]);

        const pEnd = smooth[smooth.length - 1];
        const rEnd = radii[radii.length - 1];
        const endBaseAngle = Math.atan2(lastTx, -lastTy); // +normal
        for (let s = 1; s < capSteps; s++) {
            const a = endBaseAngle - (Math.PI * s) / capSteps;
            poly.push([pEnd.x + Math.cos(a) * rEnd, pEnd.y + Math.sin(a) * rEnd]);
        }

        for (let i = leftPts.length - 1; i >= 0; i--) poly.push(leftPts[i]);
        return poly;
    }

    getGraphitePattern(ctx, colorHex) {
        if (!this._graphiteTiles) this._graphiteTiles = {};
        const key = colorHex || '#5c5852';
        if (!this._graphiteTiles[key]) {
            // Deterministisk papir-korn tekstur (64x64) som giver ægte grafit-fornemmelse
            const tile = document.createElement('canvas');
            tile.width = 64;
            tile.height = 64;
            const tCtx = tile.getContext('2d');
            tCtx.fillStyle = key;

            let seed = 1337;
            const rand = () => {
                seed = (seed * 16807) % 2147483647;
                return (seed - 1) / 2147483646;
            };
            for (let y = 0; y < 64; y++) {
                for (let x = 0; x < 64; x++) {
                    const n = rand();
                    if (n > 0.34) {
                        tCtx.globalAlpha = 0.25 + (n - 0.34) * 1.1;
                        tCtx.fillRect(x, y, 1, 1);
                    }
                }
            }
            this._graphiteTiles[key] = tile;
        }
        return ctx.createPattern(this._graphiteTiles[key], 'repeat');
    }

    renderPencilStroke(ctx, stroke, scale) {
        const pts = stroke.points;
        if (!pts || pts.length === 0) return;

        const baseSize = stroke.size || 3.2;
        const colorHex = stroke.color || '#5c5852';
        const pattern = this.getGraphitePattern(ctx, colorHex);

        ctx.save();
        ctx.scale(scale, scale);
        ctx.globalCompositeOperation = 'source-over';
        ctx.fillStyle = pattern || colorHex;

        const drawDab = (x, y, p, tilt, angle) => {
            // Op til ~0.35 tilt = spids blyant; over ~0.35 tilt = bred, blød skyggelægning med siden af pennen
            const shadeFactor = Math.max(0, (tilt - 0.32) / 0.68);
            const rx = Math.max(0.7, baseSize * (0.34 + p * 0.28 + shadeFactor * 2.1));
            const ry = Math.max(0.6, baseSize * (0.34 + p * 0.24 + shadeFactor * 0.62));
            const alpha = shadeFactor > 0.05
                ? Math.min(0.42, (0.07 + p * 0.22) * (1 - shadeFactor * 0.35))
                : Math.min(0.88, 0.36 + p * 0.45);

            ctx.save();
            ctx.translate(x, y);
            if (shadeFactor > 0.05) {
                ctx.rotate(angle);
            }
            ctx.globalAlpha = alpha;
            ctx.beginPath();
            ctx.ellipse(0, 0, rx, ry, 0, 0, Math.PI * 2);
            ctx.fill();
            ctx.restore();
        };

        if (pts.length === 1) {
            drawDab(pts[0][0], pts[0][1], pts[0][2] ?? 0.5, pts[0][3] ?? 0, pts[0][4] ?? 0);
            ctx.restore();
            return;
        }

        for (let i = 1; i < pts.length; i++) {
            const p0 = pts[i - 1];
            const p1 = pts[i];
            const dx = p1[0] - p0[0];
            const dy = p1[1] - p0[1];
            const dist = Math.hypot(dx, dy);
            const tiltAvg = ((p0[3] ?? 0) + (p1[3] ?? 0)) * 0.5;
            const stepLen = tiltAvg > 0.35 ? 1.8 : 0.9;
            const steps = Math.max(1, Math.ceil(dist / stepLen));

            for (let s = 0; s <= steps; s++) {
                const t = s / steps;
                const x = p0[0] + dx * t;
                const y = p0[1] + dy * t;
                const p = (p0[2] ?? 0.5) + ((p1[2] ?? 0.5) - (p0[2] ?? 0.5)) * t;
                const tilt = (p0[3] ?? 0) + ((p1[3] ?? 0) - (p0[3] ?? 0)) * t;
                const ang = (p0[4] ?? 0) + ((p1[4] ?? 0) - (p0[4] ?? 0)) * t;
                drawDab(x, y, p, tilt, ang);
            }
        }
        ctx.restore();
    }

    renderStroke(ctx, stroke, scale) {
        const pts = stroke.points;
        if (!pts || pts.length === 0) return;

        // Viskelæder tegnes som blød, jævn destination-out streg
        if (stroke.tool === 'eraser') {
            ctx.save();
            ctx.scale(scale, scale);
            ctx.globalCompositeOperation = 'destination-out';
            ctx.strokeStyle = 'rgba(0,0,0,1)';
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';
            ctx.lineWidth = stroke.size || 22;
            ctx.beginPath();
            ctx.moveTo(pts[0][0], pts[0][1]);
            for (let i = 1; i < pts.length; i++) {
                const prev = pts[i - 1];
                const cur = pts[i];
                ctx.quadraticCurveTo(prev[0], prev[1], (prev[0] + cur[0]) * 0.5, (prev[1] + cur[1]) * 0.5);
            }
            const last = pts[pts.length - 1];
            ctx.lineTo(last[0], last[1]);
            ctx.stroke();
            ctx.restore();
            return;
        }

        // Blyant (grafit-korn + skyggelægning ved hældning af Apple Pencil)
        if (stroke.style === 'pencil') {
            this.renderPencilStroke(ctx, stroke, scale);
            return;
        }

        if (pts.length === 1) {
            this.renderDot(ctx, stroke, pts[0], scale);
            return;
        }

        const poly = this.buildInkPolygon(pts, stroke);
        if (!poly || poly.length < 3) {
            this.renderDot(ctx, stroke, pts[0], scale);
            return;
        }

        // Fyld sammenhængende, glat blækform med kvadratiske Bézier-midtpunkter
        ctx.save();
        ctx.scale(scale, scale);
        ctx.globalCompositeOperation = 'source-over';
        ctx.fillStyle = stroke.color || '#1e1c1a';
        ctx.beginPath();
        const len = poly.length;
        const firstMidX = (poly[len - 1][0] + poly[0][0]) * 0.5;
        const firstMidY = (poly[len - 1][1] + poly[0][1]) * 0.5;
        ctx.moveTo(firstMidX, firstMidY);
        for (let i = 0; i < len; i++) {
            const cur = poly[i];
            const next = poly[(i + 1) % len];
            ctx.quadraticCurveTo(cur[0], cur[1], (cur[0] + next[0]) * 0.5, (cur[1] + next[1]) * 0.5);
        }
        ctx.closePath();
        ctx.fill('nonzero');
        ctx.restore();
    }

    redraw(targetCtx = this.ctx, targetScale = this.scale) {
        const w = this.logicalW * targetScale;
        const h = this.logicalH * targetScale;
        if (targetCtx === this.ctx && this.committedCtx) {
            this.committedCtx.clearRect(0, 0, w, h);
            for (const stroke of this.strokes) {
                this.renderStroke(this.committedCtx, stroke, targetScale);
            }
            this.ctx.clearRect(0, 0, w, h);
            this.ctx.drawImage(this.committedCanvas, 0, 0);
            return;
        }
        targetCtx.clearRect(0, 0, w, h);
        for (const stroke of this.strokes) {
            this.renderStroke(targetCtx, stroke, targetScale);
        }
    }

    undo() {
        if (this.strokes.length === 0) return;
        this.redoStack.push(this.strokes.pop());
        this.dirty = true;
        this.redraw();
        this.updateToolbarState();
        updateHandwritingStatus(this.modeKey);
    }

    redo() {
        if (this.redoStack.length === 0) return;
        this.strokes.push(this.redoStack.pop());
        this.dirty = true;
        this.redraw();
        this.updateToolbarState();
        updateHandwritingStatus(this.modeKey);
    }

    clearWithConfirm() {
        if (this.strokes.length === 0) return;
        if (!confirm('Ryd hele det håndskrevne ark?')) return;
        this.strokes = [];
        this.redoStack = [];
        this.dirty = true;
        this.redraw();
        this.updateToolbarState();
        updateHandwritingStatus(this.modeKey);
    }

    reset() {
        this.strokes = [];
        this.redoStack = [];
        this.logicalW = 644;
        this.logicalH = 12 * this.lineHeight;
        this.dirty = false;
        if (this.isFullscreen) this.toggleFullscreen(false);
        this.resizeCanvas();
        this.redraw();
        this.updateToolbarState();
        updateHandwritingStatus(this.modeKey);
    }

    getLowestInkY() {
        let maxY = 0;
        for (const s of this.strokes) {
            if (s.tool === 'eraser' || !s.points) continue;
            for (const pt of s.points) {
                if (pt[1] > maxY) maxY = pt[1];
            }
        }
        return maxY;
    }

    shrinkHeight() {
        const minH = this.minLines * this.lineHeight;
        const lowestInk = this.getLowestInkY();
        const safeMinH = lowestInk > 0
            ? Math.max(minH, (Math.ceil(lowestInk / this.lineHeight) + 1) * this.lineHeight)
            : minH;
        const targetH = Math.max(safeMinH, this.logicalH - 4 * this.lineHeight);
        if (targetH >= this.logicalH) return;
        this.logicalH = targetH;
        this.dirty = true;
        this.resizeCanvas();
        this.redraw();
        this.updateToolbarState();
    }

    expandHeight() {
        const maxH = this.maxLines * this.lineHeight;
        if (this.logicalH >= maxH) return;
        this.logicalH = Math.min(maxH, this.logicalH + 4 * this.lineHeight);
        this.dirty = true;
        this.resizeCanvas();
        this.redraw();
        this.updateToolbarState();
    }

    cycleGuide() {
        const order = ['lines', 'dots', 'blank'];
        this.guide = order[(order.indexOf(this.guide) + 1) % order.length];
        this.drawGuides();
        this.updateToolbarState();
    }

    togglePencilOnly() {
        this.pencilOnly = !this.pencilOnly;
        this.canvas.style.touchAction = this.pencilOnly ? 'pan-y' : 'none';
        this.updateToolbarState();
    }

    toggleFullscreen(forceState) {
        const next = (typeof forceState === 'boolean') ? forceState : !this.isFullscreen;
        this.isFullscreen = next;
        this.container.classList.toggle('hw-fullscreen', next);
        document.body.style.overflow = next ? 'hidden' : '';
        this.updateToolbarState();
    }

    updateToolbarState() {
        this.container.querySelectorAll('.hw-swatch').forEach(btn => {
            btn.classList.toggle('active', this.tool === 'pen' && btn.dataset.color === this.color);
        });
        this.container.querySelectorAll('[data-size]').forEach(btn => {
            btn.classList.toggle('active', this.tool === 'pen' && parseFloat(btn.dataset.size) === this.size);
        });
        const eraserBtn = this.container.querySelector('[data-tool="eraser"]');
        if (eraserBtn) eraserBtn.classList.toggle('active', this.tool === 'eraser');

        const penStyleBtn = this.container.querySelector('[data-action="penstyle"]');
        if (penStyleBtn) {
            const labels = { fountain: 'Fyldepen', fineliner: 'Fineliner', pencil: 'Blyant' };
            penStyleBtn.textContent = labels[this.penStyle] || 'Fyldepen';
            penStyleBtn.classList.toggle('active', this.tool === 'pen');
        }

        const undoBtn = this.container.querySelector('[data-action="undo"]');
        const redoBtn = this.container.querySelector('[data-action="redo"]');
        if (undoBtn) undoBtn.disabled = this.strokes.length === 0;
        if (redoBtn) redoBtn.disabled = this.redoStack.length === 0;

        const guideBtn = this.container.querySelector('[data-action="guide"]');
        if (guideBtn) {
            guideBtn.textContent = this.guide === 'lines' ? 'Linjer' : (this.guide === 'dots' ? 'Prikker' : 'Blank');
        }

        const pencilBtn = this.container.querySelector('[data-action="pencil"]');
        if (pencilBtn) {
            pencilBtn.classList.toggle('active', this.pencilOnly);
            pencilBtn.textContent = this.pencilOnly ? '✎ Kun Pencil' : '✎ Pencil';
        }

        const fsBtn = this.container.querySelector('[data-action="fullscreen"]');
        if (fsBtn) {
            fsBtn.textContent = this.isFullscreen ? '✓ Færdig' : '⤢ Fuld';
            fsBtn.classList.toggle('active', this.isFullscreen);
        }

        const linesCount = Math.round(this.logicalH / this.lineHeight);
        if (this.linesLabelEl) {
            this.linesLabelEl.textContent = `${linesCount} linjer`;
        }

        const shrinkBtn = this.container.querySelector('[data-action="shrink"]');
        if (shrinkBtn) {
            const minH = this.minLines * this.lineHeight;
            const lowestInk = this.getLowestInkY();
            const safeMinH = lowestInk > 0
                ? Math.max(minH, (Math.ceil(lowestInk / this.lineHeight) + 1) * this.lineHeight)
                : minH;
            shrinkBtn.disabled = this.logicalH <= safeMinH;
        }

        const expandBtn = this.container.querySelector('[data-action="expand"]');
        if (expandBtn) expandBtn.disabled = this.logicalH >= this.maxLines * this.lineHeight;

        if (this.hintEl) {
            this.hintEl.textContent = this.pencilOnly
                ? 'Kun Apple Pencil tegner · fingre scroller arket'
                : 'Linjerne matcher logbogens papir 1:1';
        }
    }

    hasInk() {
        return this.strokes.some(s => s.tool !== 'eraser' && s.points && s.points.length > 0);
    }

    exportJson() {
        return {
            v: 2,
            width: this.logicalW,
            height: this.logicalH,
            lineHeight: this.lineHeight,
            strokes: this.strokes
        };
    }

    loadJson(data) {
        if (!data || typeof data !== 'object') return;
        this.logicalW = Number(data.width) || 644;
        const minH = this.minLines * this.lineHeight;
        const maxH = this.maxLines * this.lineHeight;
        const rawH = Math.max(minH, Math.min(maxH, Number(data.height) || (12 * this.lineHeight)));
        this.logicalH = Math.ceil(rawH / this.lineHeight) * this.lineHeight;
        this.strokes = Array.isArray(data.strokes) ? data.strokes : [];
        this.redoStack = [];
        this.resizeCanvas();
        this.redraw();
        this.updateToolbarState();
    }

    exportPngDataUrl() {
        if (!this.hasInk()) return null;

        const exScale = this.exportScale || 3;
        const fullW = this.logicalW * exScale;
        const fullH = this.logicalH * exScale;
        const off = document.createElement('canvas');
        off.width = fullW;
        off.height = fullH;
        const offCtx = off.getContext('2d');
        this.redraw(offCtx, exScale);

        // Beskær tom top/bund til præcise hele 32px-linjer, så blækket flugter med logbogens linjer
        const imgData = offCtx.getImageData(0, 0, fullW, fullH).data;
        let minY = fullH;
        let maxY = -1;
        for (let y = 0; y < fullH; y += 2) {
            const rowOffset = y * fullW * 4;
            for (let x = 0; x < fullW; x += 4) {
                if (imgData[rowOffset + x * 4 + 3] > 8) {
                    if (y < minY) minY = y;
                    if (y > maxY) maxY = y;
                    break;
                }
            }
        }
        if (maxY < 0) return null;

        const linePx = this.lineHeight * exScale; // 96px ved 3x retina
        const cropY = Math.max(0, Math.floor(minY / linePx) * linePx);
        const cropBottom = Math.min(fullH, Math.ceil((maxY + 12) / linePx) * linePx);
        const cropH = Math.max(linePx * 2, cropBottom - cropY);

        const cropped = document.createElement('canvas');
        cropped.width = fullW;
        cropped.height = cropH;
        const cCtx = cropped.getContext('2d');
        cCtx.drawImage(off, 0, cropY, fullW, cropH, 0, 0, fullW, cropH);

        return cropped.toDataURL('image/png');
    }
}

const handwritingPads = {};

function getHandwritingPad(modeKey) {
    if (!handwritingPads[modeKey]) {
        const panelId = modeKey === 'edit' ? 'editHwPanel' : 'composeHwPanel';
        const el = document.getElementById(panelId);
        if (!el) return null;
        handwritingPads[modeKey] = new HandwritingPad(el, modeKey);
    }
    return handwritingPads[modeKey];
}

function toggleHandwritingPanel(modeKey) {
    const panelId = modeKey === 'edit' ? 'editHwPanel' : 'composeHwPanel';
    const panel = document.getElementById(panelId);
    if (!panel) return;
    const isOpen = panel.style.display !== 'none';
    if (isOpen) {
        closeHandwritingPanel(modeKey);
    } else {
        getHandwritingPad(modeKey);
        panel.style.display = 'block';
        updateHandwritingStatus(modeKey);
    }
}

function closeHandwritingPanel(modeKey) {
    const panelId = modeKey === 'edit' ? 'editHwPanel' : 'composeHwPanel';
    const panel = document.getElementById(panelId);
    const pad = handwritingPads[modeKey];
    if (pad && pad.isFullscreen) pad.toggleFullscreen(false);
    if (panel) panel.style.display = 'none';
    updateHandwritingStatus(modeKey);
}

function updateHandwritingStatus(modeKey) {
    const statusEl = document.getElementById(modeKey === 'edit' ? 'editHwStatus' : 'composeHwStatus');
    const toggleBtn = document.getElementById(modeKey === 'edit' ? 'editHwToggleBtn' : 'composeHwToggleBtn');
    const panel = document.getElementById(modeKey === 'edit' ? 'editHwPanel' : 'composeHwPanel');
    const pad = handwritingPads[modeKey];
    const isOpen = panel && panel.style.display !== 'none';
    const hasInk = pad ? pad.hasInk() : false;

    if (statusEl) {
        statusEl.textContent = hasInk ? '✓ Håndskrift klar' : '';
    }
    if (toggleBtn) {
        if (isOpen) {
            toggleBtn.textContent = 'Skjul tegneflade';
        } else if (hasInk) {
            toggleBtn.textContent = '✎ Ret håndskrift';
        } else {
            toggleBtn.textContent = modeKey === 'edit' ? '✎ Tilføj håndskrift' : '✎ Skriv / tegn i hånden';
        }
    }
}

// Kør ved start
window.addEventListener('DOMContentLoaded', () => {
    checkAuthStatus();
});
</script>

</body>
</html>
