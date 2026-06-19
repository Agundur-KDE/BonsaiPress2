<?php

/**
 * BonsaiFormGuard — reusable contact form spam protection
 *
 * Layers:
 *   1. Honeypot field (_hp) — bots fill it, humans don't see it
 *   2. JS timing (_time)    — reject submissions faster than $minAge seconds
 *   3. Rate limiting        — max $rateLimit submissions per IP per hour (file-based)
 *   4. Header injection     — stripHeaders() removes \r\n from mail fields
 *
 * Usage in handler:
 *   require_once '/var/www/cms/include/BonsaiFormGuard.php';
 *   (new BonsaiFormGuard())->validate();
 *
 * Usage in form (static HTML):
 *   <input type="text"   name="_hp"   class="form-guard-hp" tabindex="-1" autocomplete="off" aria-hidden="true" />
 *   <input type="hidden" name="_time" id="form-time" value="" />
 *   (JS sets #form-time to Math.floor(Date.now()/1000) on load)
 */
class BonsaiFormGuard
{
    private int    $minAge    = 3;     // seconds — reject if submitted faster
    private int    $maxAge    = 3600;  // seconds — reject if token too old
    private int    $rateLimit = 3;     // max submissions per IP per hour
    private string $rlDir;

    public function __construct()
    {
        $this->rlDir = sys_get_temp_dir() . '/bonsai_rl/';
    }

    public function validate(): void
    {
        $this->checkHoneypot();
        $this->checkTiming();
        $this->checkRateLimit();
    }

    /** Strip \r\n from mail header fields (name, subject) to prevent injection */
    public static function stripHeaders(string $value): string
    {
        return preg_replace('/[\r\n\t]+/', ' ', trim($value));
    }

    private function checkHoneypot(): void
    {
        if (!empty($_POST['_hp'])) {
            $this->reject('honeypot');
        }
    }

    private function checkTiming(): void
    {
        $time = (int)($_POST['_time'] ?? 0);
        if ($time === 0) return; // JS disabled — honeypot + rate limit still active
        $age = time() - $time;
        if ($age < $this->minAge) $this->reject('too fast');
        if ($age > $this->maxAge) $this->reject('expired');
    }

    private function checkRateLimit(): void
    {
        if (!is_dir($this->rlDir)) {
            mkdir($this->rlDir, 0700, true);
        }

        $ip   = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $file = $this->rlDir . md5($ip) . '.json';
        $now  = time();

        $hits = [];
        if (file_exists($file)) {
            $hits = array_filter(
                json_decode(file_get_contents($file), true) ?? [],
                fn($t) => $now - $t < 3600
            );
        }

        if (count($hits) >= $this->rateLimit) {
            $this->reject('rate limit');
        }

        $hits[] = $now;
        file_put_contents($file, json_encode(array_values($hits)), LOCK_EX);
    }

    private function reject(string $reason): never
    {
        error_log('BonsaiFormGuard: rejected (' . $reason . ') from ' . ($_SERVER['REMOTE_ADDR'] ?? '?'));
        http_response_code(429);
        echo '<div id="form-response" class="alert alert-warning mt-3">Anfrage konnte nicht verarbeitet werden. Bitte später erneut versuchen.</div>';
        exit;
    }
}
