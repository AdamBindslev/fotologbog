# 📓 Logbog – SYS.LOG // ANALOG

En minimalistisk, hurtig og personlig fotologbog med notesbogs-æstetik. Bygget til nem daglig publicering direkte fra din telefon via en dedikeret PWA (Progressive Web App) og afviklet på Gigahost.dk med PHP og SQLite.

---

## ⚡ Nøglefunktioner

* **Lynhurtig mobil PWA (`/app/`):**
  * Kan tilføjes til hjemmeskærmen på din iPhone eller Android som en app.
  * Åbner direkte i "Nyt indlæg".
  * **Klient-side optimering:** Billeder skaleres automatisk ned i telefonens browser før upload (maks 2048px / JPEG), så uploads tager under et sekund selv på 4G/5G.
  * **📍 Auto-GPS:** Ét tryk henter din nøjagtige position og udfylder automatisk f.eks. "København, Vesterbro".
  * **Husk mig:** Log ind én gang med din PIN-kode (standard: `1234`), og appen husker dig på din enhed.
* **Analog notesbogs-æstetik:**
  * Inspireret af `SYS.LOG // ANALOG` med tidsstempler som `[2026.11.11 // KØBENHAVN]`.
  * Rolige, varme toner, skarp typografi og fokus på fotografiet.
* **Nul database-opsætning:**
  * Kører på en lokal **SQLite** database (`data/logbog.db`), der oprettes automatisk. Ingen behov for at oprette brugere eller databaser i Gigahosts kontrolpanel.
  * 1-kliks download af database-backup direkte fra PWA'en.
* **RSS Feed & Arkiv:**
  * Måneds- og årsopdelt arkiv.
  * Fuldgyldigt RSS 2.0 feed på `/rss.php` eller `/feed.xml`.

---

## 🚀 Opsætning på Gigahost.dk

### 1. Upload filerne
Upload hele indholdet af denne mappe til roden af dit domæne (eller subdomæne som `log.adambindslev.dk`) via SFTP eller FTP (f.eks. med FileZilla eller Cyberduck).

### 2. Tjek skriverettigheder (vigtigt)
Serveren skal have lov til at oprette databasen og gemme billeder:
* Mappen `data/` skal være skrivbar (`chmod 775` eller `777`).
* Mappen `uploads/` skal være skrivbar (`chmod 775` eller `777`).

*Bemærk: SQLite-filen (`data/logbog.db`) beskyttes automatisk mod download af uvedkommende via `.htaccess`.*

### 3. Første login & skift PIN-kode
1. Gå til dit domæne i browseren på din telefon: `https://ditdomaene.dk/app/` (eller `/skriv`).
2. Log ind med standard PIN-koden: **`1234`**.
3. Gå ind under fanen **⚙️ Indstillinger** og skift koden til din egen personlige PIN.

### 4. Tilføj til hjemmeskærmen på din telefon
* **På iPhone (Safari):**
  1. Åbn `https://ditdomaene.dk/app/` i Safari.
  2. Tryk på Del-knappen (firkanten med pilen opad).
  3. Vælg **"Føj til hjemmeskærm"** (Add to Home Screen).
  4. Nu har du et app-ikon med kamera-søgeren på din telefon, der åbner i fuld skærm uden browserbjælker.
* **På Android (Chrome):**
  1. Åbn i Chrome.
  2. Tryk på menuen (de tre prikker) og vælg **"Føj til startskærm"** eller **"Installer app"**.

---

## 🛠️ Justering og tilpasning

Alle generelle indstillinger findes øverst i `config.php`:

```php
define('SITE_TITLE', 'ADAM BINDSLEV // LOGBOG');
define('SITE_SUBTITLE', 'SYS.LOG // ANALOG – Et billede og en tanke om dagen');
define('SITE_AUTHOR', 'Adam Bindslev');
```

---

## 💾 Backup

Du har to nemme måder at tage backup på:
1. **Fra appen:** Tryk på fanen **⚙️ Indstillinger** og klik **"Download database backup"**. Det henter `logbog_backup_YYYY-MM-DD.db` ned på din telefon eller computer.
2. **Via FTP:** Download filen `data/logbog.db` og mappen `uploads/`.
