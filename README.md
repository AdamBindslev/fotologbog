# 📓 Fotologbog – SYS.LOG // ANALOG

En minimalistisk, hurtig og personlig fotologbog med taktil spiralnotesbogs-æstetik (*The Spiral / Collegiate*), mørkekammer-lup (lightbox) og månedlige kontaktark.

Bygget til ubesværet, daglig publicering direkte fra din mobiltelefon via en dedikeret PWA (Progressive Web App). Drevet af standard PHP 8+ og en lokal SQLite-database — helt uden krav om ekstern databaseserver eller tunge frameworks.

---

## ⚡ Nøglefunktioner

* **Lynhurtig mobil PWA (`/app/`):**
  * Kan installeres direkte på hjemmeskærmen på iPhone og Android uden en app store.
  * Åbner med det samme i "Nyt indlæg".
  * **Klient-side komprimering:** Billeder optimeres direkte i telefonens browser før upload (maks 2048px / JPEG), så uploads tager under et sekund selv over mobilnetværk.
  * **📍 Auto-GPS:** Ét tryk slår din GPS-position op og udfylder automatisk f.eks. *"København, Vesterbro"*.
  * **⌀ Automatisk EXIF-udlæsning:** Kamera, objektiv, blænde, lukkertid og ISO udlæses automatisk af billedet.
  * **Husk mig:** Log ind én gang med din PIN-kode (standard: `1234`), og appen forbliver sikkert logget ind på din enhed.

* **Analog notesbogs-æstetik & Mørkekammer:**
  * Grafisk udtryk inspireret af klassiske spiralnotesbøger med terrakotta kraftomslag, perforerede manilablade og skrivemaskinetypografi.
  * **Mørkekammer-lup:** Fuldskærmsvisning i mørkt rum med EXIF-stempel og lynhurtig piletast-navigation.
  * **Månedlige kontaktark (Contact Sheets):** Visuel kalender med miniaturebilleder for hver dag i måneden.
  * **Emnesky & Tags:** Dynamisk organisk emnesky for både tags og lokationer.
  * **🖼️ Autentiske Delingskort (Open Graph 1200×630):** Vælg mellem 4 rene, analoge stilarter (*Polaroid*, *35mm Mørkekammer*, *Notesbogsark* eller *Rent foto*) med live forhåndsvisning direkte i mobilen og automatisk GD-generering til sociale medier (Facebook, iMessage, X, LinkedIn). Helt fri for CTA-støj eller læsetids-tricks.

* **Nul database-opsætning:**
  * Kører på en lokal **SQLite**-database (`data/logbog.db`), der oprettes og migreres automatisk. Ingen behov for MySQL eller serveropsætning.
  * 1-kliks backup af databasen direkte i administrationsfladen.

* **RSS Feed & Arkiv:**
  * Kronologisk arkiv opdelt efter år og måned.
  * Fuldgyldigt RSS 2.0 feed på `/rss.php` (eller `/feed.xml`).

---

## 🔒 Sikkerhed & Arkitektur

Systemet er bygget efter *defense-in-depth* princippet:

1. **Skjult indgang:** Forsiden indeholder ingen offentlige links til administrationsfladen. Kun du kender adressen (`/app/` eller genvejen `/skriv`).
2. **Brute-force beskyttelse (Rate Limiting):** Efter 5 forkerte PIN-forsøg spærres IP-adressen automatisk i 15 minutter.
3. **Lockdown Mode (Luk for nye logins):**
   * Når du har logget ind på dine foretrukne enheder (f.eks. telefon og computer), aktiverer du **"🔒 Lås for nye logins"** under *⚙️ Indstillinger*.
   * Serveren afviser derefter 100% af alle nye login-forsøg — uanset om nogen kender PIN-koden. Dine godkendte enheder forbliver logget ind.
4. **Mappebeskyttelse mod direkte download:**
   * Databasemappen `data/` og filen `logbog.db` er blokeret for HTTP-adgang via `.htaccess`.
   * Uploadsmappen `uploads/` forhindrer afvikling af enhver form for PHP- eller serverscripts via Apache-direktiver.

> [!WARNING]
> Hvis du anvender **Nginx** frem for Apache, skal du sikre, at din serverblok blokerer direkte adgang til mapperne `/data/` og forhindrer script-afvikling i `/uploads/`.

---

## 🚀 Installation & Kom i gang

### 1. Kopiér filerne til dit webhotel
Overfør hele kildekoden til roden af dit webhotel (f.eks. via SFTP eller Git).

### 2. Sæt skriverettigheder
PHP-processen skal have adgang til at skrive databasen og gemme billeder:
```bash
chmod 775 data uploads
```

### 3. Konfiguration
Kopier eventuelt `config.local.php` for personlige indstillinger:
```php
<?php
// config.local.php (ignoreres automatisk af git)
define('SITE_TITLE', 'MIT NAVN // LOGBOG');
define('SITE_AUTHOR', 'Mit Navn');
define('SITE_DESCRIPTION', 'Min daglige visuelle fotologbog.');
define('COUNTER_DEV_ID', ''); // Valgfrit Counter.dev analytics ID
```
Filen `config.local.php` indlæses automatisk af `config.php`, så dine personlige værdier aldrig overskrives eller skubbes til et offentligt Git-arkiv.

### 4. Første login & skift PIN-kode
1. Besøg dit domæne i browseren på din telefon: `https://ditdomaene.dk/app/`.
2. Log ind med standard PIN-koden: **`1234`**.
3. **Vigtigt:** Gå straks til fanen **⚙️ Indstillinger** og skift til din egen personlige PIN-kode.
4. Tryk på Del-knappen i Safari (iOS) eller menuen i Chrome (Android) og vælg **"Føj til hjemmeskærm"**.

---

## 🛠️ Krav

* PHP 8.0 eller nyere
* PHP-udvidelser: `pdo_sqlite`, `gd` (eller `imagick`), `exif`
* Apache webserver med `mod_rewrite` aktiveret (eller tilsvarende Nginx-opsætning)

Du kan kontrollere dine serverkrav ved at logge ind og besøge `https://ditdomaene.dk/check.php`.

---

## 🤝 Bidrag og videreudvikling

Pull requests, idéer og fejlmeldinger er meget velkomne! 

Hvis du ønsker at tilføje nye funktioner eller forbedre designet:
1. Fork repositoryet
2. Opret en funktionsgren (`git checkout -b feature/fantastisk-ide`)
3. Commit dine ændringer (`git commit -m 'Tilføj fantastisk idé'`)
4. Push til din gren (`git push origin feature/fantastisk-ide`)
5. Opret en Pull Request

---

## 📄 Licens

Dette projekt er udgivet under [MIT Licensen](LICENSE). Du er velkommen til at bruge, tilpasse og videreudvikle koden til dine egne projekter.
