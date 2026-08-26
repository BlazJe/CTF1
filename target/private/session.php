<?php
/**
 * Skupna obravnava piškotka "session".
 *
 * Piškotek je zgolj base64 zapis JSON-a. Strežnik ga nikoli ne podpiše in mu
 * brez preverjanja verjame - to je namerna ranljivost tega okolja.
 *
 * Datoteko vključi na vrhu vsake strani, še preden se izpiše kakršenkoli
 * HTML, sicer setcookie() ne more več nastaviti glave.
 */

const NORDVEL_COOKIE = 'session';
const NORDVEL_COOKIE_TTL = 3600;

/** Vrednosti, ki jih razumemo kot "da". Namerno prizanesljivo. */
function nordvel_truthy($value): bool
{
    if (is_bool($value)) {
        return $value;
    }
    if (is_int($value)) {
        return $value === 1;
    }
    if (is_string($value)) {
        return in_array(strtolower(trim($value)), ['1', 'true', 'yes'], true);
    }
    return false;
}

/**
 * Prebere piškotek in ga razčleni v polje.
 *
 * Razčlenjevanje je namenoma odporno: urejevalniki piškotkov v brskalnikih
 * radi dodajo presledek ali novo vrstico, včasih pa vrednost tudi
 * url-kodirajo. Strogi base64_decode() bi v vseh teh primerih tiho vrnil
 * false in uporabnik bi videl le "dostop zavrnjen", ne da bi vedel, zakaj.
 */
function nordvel_read_session(): array
{
    $raw = $_COOKIE[NORDVEL_COOKIE] ?? '';
    if ($raw === '') {
        return [];
    }

    $raw = trim($raw);
    if (strpos($raw, '%') !== false) {
        $raw = urldecode($raw);
    }
    $raw = preg_replace('/\s+/', '', $raw);

    $json = base64_decode($raw, true);
    if ($json === false) {
        $json = base64_decode($raw);
    }
    if ($json === false || $json === '') {
        return [];
    }

    $data = json_decode($json, true);
    return is_array($data) ? $data : [];
}

/** Ime uporabnika iz piškotka, počiščeno za primerjavo. */
function nordvel_user(array $session): string
{
    return strtolower(trim((string)($session['user'] ?? '')));
}

/** Vloga iz piškotka, počiščena za primerjavo. */
function nordvel_role(array $session): string
{
    return strtolower(trim((string)($session['role'] ?? '')));
}

/** Ali piškotek trdi, da je obiskovalec prijavljen. */
function nordvel_logged_in(array $session): bool
{
    return nordvel_truthy($session['logged_in'] ?? false);
}

/** Zapiše nov piškotek s podanimi podatki. */
function nordvel_set_session(string $user, bool $loggedIn, string $role): void
{
    $payload = json_encode([
        'user' => $user,
        'logged_in' => $loggedIn,
        'role' => $role,
    ], JSON_UNESCAPED_SLASHES);

    $encoded = base64_encode($payload);
    setcookie(NORDVEL_COOKIE, $encoded, time() + NORDVEL_COOKIE_TTL, '/');
    $_COOKIE[NORDVEL_COOKIE] = $encoded;
}

/** Vsak obiskovalec dobi piškotek že ob prvem obisku katerekoli strani. */
function nordvel_ensure_cookie(): void
{
    if (!isset($_COOKIE[NORDVEL_COOKIE]) || trim((string)$_COOKIE[NORDVEL_COOKIE]) === '') {
        nordvel_set_session('guest', false, 'user');
    }
}

nordvel_ensure_cookie();

/** Na voljo vsem stranem, ki vključijo to datoteko. */
$SESSION = nordvel_read_session();
$SESSION_USER = nordvel_user($SESSION);
$SESSION_ROLE = nordvel_role($SESSION);
$SESSION_LOGGED_IN = nordvel_logged_in($SESSION);
