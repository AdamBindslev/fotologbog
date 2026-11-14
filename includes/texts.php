<?php
/**
 * Standardtekster til den offentlige del af Logbog.
 * Enhver nøgle kan overskrives i config.local.php via arrayet $SITE_TEXTS, f.eks.:
 *
 * $SITE_TEXTS = [
 *     'postcard_nod_btn' => '[ ✋ SEND ET HIGH-FIVE ]',
 * ];
 */

function logbog_default_texts(): array {
    return [
        // Navigation & Header
        'nav_latest'        => '[ SENESTE ]',
        'nav_archive'       => '[ ARKIV ]',
        'nav_topics'        => '[ EMNER ]',
        'nav_overview'      => '[ OVERSIGT ]',
        'nav_newer'         => '← [ NYERE ]',
        'nav_older'         => '[ TIDLIGERE ] →',
        'nav_prev_post'     => '← [ FORRIGE OPTEGNELSE ]',
        'nav_next_post'     => '[ NÆSTE OPTEGNELSE ] →',

        // Indlæg & Lysboks
        'post_loupe'        => '[ ⤢ LUP ]',
        'post_loupe_close'  => '[ ✕ LUK // ESC ]',
        'post_no_exif'      => 'Ingen Exif',
        'post_postcard_link'=> '[ ✉ POSTKORT ]',
        'feed_page_of'      => 'SIDE {page} AF {total}',

        // Privat postkort & stille nik
        'postcard_title'            => defined('POSTCARD_TITLE') ? (string)POSTCARD_TITLE : '[ PRIVAT POSTKORT ]',
        'postcard_intro'            => defined('POSTCARD_INTRO') ? (string)POSTCARD_INTRO : 'Ingen offentlige likes eller kommentarspor — men hvis billedet ramte noget i dig, kan du sende et stille nik eller en kort hilsen direkte til fotografens private indbakke.',
        'postcard_nod_btn'          => '[ ♡ SEND ET STILLE NIK ]',
        'postcard_write_btn'        => '[ ✉ SKRIV ET POSTKORT ]',
        'postcard_name_placeholder' => 'Dit navn (valgfrit)',
        'postcard_contact_placeholder' => 'E-mail / @ / site (valgfrit, hvis du vil have svar)',
        'postcard_msg_placeholder'  => 'Skriv en kort hilsen om billedet eller dagen...',
        'postcard_privacy_note'     => 'Kun synligt for fotografen.',
        'postcard_submit_btn'       => '[ SEND PRIVAT POSTKORT ]',
        'postcard_sending'          => '[ SENDER... ]',
        'postcard_already_sent'     => '✓ Du har allerede sendt en hilsen til dette indlæg i denne session.',
        'postcard_empty_msg_error'  => 'Skriv en kort hilsen, eller brug knappen for et stille nik.',
        'postcard_sent_nod'         => 'Tak — dit stille nik er sendt privat til fotografen.',
        'postcard_sent_note'        => 'Tak — dit postkort er sendt privat til fotografen.',
        'postcard_rate_post_error'  => 'Du har allerede sendt en hilsen til dette indlæg for nylig.',
        'postcard_rate_hour_error'  => 'Der er sendt lidt mange postkort herfra i timen. Prøv igen lidt senere.',

        // Kolofon
        'colophon_ethos'      => defined('COLOPHON_ETHOS') ? (string)COLOPHON_ETHOS : 'Udgivet uden offentlige likes, algoritmer eller følgere — for glæden ved at se og skabe hver dag.',
        'colophon_rss_label'  => '[ RSS FEED ]',
        'colophon_repo_label' => defined('COLOPHON_REPO_LABEL') ? (string)COLOPHON_REPO_LABEL : 'Bygget på Logbog — start din egen →',

        // Arkiv & Emner
        'archive_title'     => '[ ARKIV & KONTAKTARK ]',
        'archive_empty'     => '[ INGEN INDLÆG ARKIVERET ENDNU ]',
        'archive_months'    => '[ MÅNEDSOVERSIGT ]',
        'topics_empty'      => '[ INGEN EMNER ELLER LOKATIONER ARKIVERET ENDNU ]',
    ];
}

/**
 * Henter en tekstnøgle (med mulighed for overskrivning via $SITE_TEXTS i config.local.php)
 */
function t(string $key, array $vars = []): string {
    global $SITE_TEXTS;
    static $defaults = null;
    if ($defaults === null) {
        $defaults = logbog_default_texts();
    }

    if (is_array($SITE_TEXTS) && array_key_exists($key, $SITE_TEXTS)) {
        $text = (string)$SITE_TEXTS[$key];
    } else {
        $text = $defaults[$key] ?? $key;
    }

    foreach ($vars as $k => $v) {
        $text = str_replace('{' . $k . '}', (string)$v, $text);
    }
    return $text;
}
