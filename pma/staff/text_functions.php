<?php
/**
 * Shared text helpers used by both staff and student pages.
 */

/**
 * Repair "mojibake" - text that was UTF-8, misread as Windows-1252 and saved
 * again as UTF-8 (typical of text pasted from Word), e.g. "â€¢" -> "•",
 * "youâ€™re" -> "you’re". Only runs of suspect characters are converted, and
 * only when the result is valid UTF-8, so correctly encoded text is untouched.
 * Unrecoverable replacement characters (U+FFFD, or its mangled form "ï¿½")
 * are removed.
 */
if (!function_exists('FixMojibake')) {
function FixMojibake($text)
{
    if ($text === null || $text === '' || !mb_check_encoding($text, 'UTF-8')) {
        return $text;
    }

    // Characters that Windows-1252 bytes 0x80-0xFF turn into when misread
    $cp1252_chars = '\x{80}-\x{FF}\x{152}\x{153}\x{160}\x{161}\x{178}\x{17D}\x{17E}'
                  . '\x{192}\x{2C6}\x{2DC}\x{2013}\x{2014}\x{2018}-\x{201E}\x{2020}-\x{2022}'
                  . '\x{2026}\x{2030}\x{2039}\x{203A}\x{20AC}\x{2122}';

    // Some text was double-encoded more than once, so allow a couple of passes
    for ($pass = 0; $pass < 2; $pass++) {
        $fixed = preg_replace_callback('/[' . $cp1252_chars . ']{2,}/u', function ($m) {
            $bytes = mb_convert_encoding($m[0], 'Windows-1252', 'UTF-8');
            return mb_check_encoding($bytes, 'UTF-8') ? $bytes : $m[0];
        }, $text);
        if ($fixed === null || $fixed === $text) {
            break;
        }
        $text = $fixed;
    }

    // Drop replacement characters - the original character is lost
    return str_replace("\u{FFFD}", '', $text);
}
}

/**
 * Turn plain-text URLs (http://, https:// or www.) in an HTML fragment into
 * links that open in a new tab. Text inside tags and inside existing <a>
 * elements is left alone; existing links without a target are also set to
 * open in a new tab.
 */
if (!function_exists('LinkifyUrls')) {
function LinkifyUrls($html)
{
    if ($html === null || $html === '') {
        return $html;
    }

    // Split into tags and text, keeping the tags
    $parts = preg_split('/(<[^>]*>)/', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
    if ($parts === false) {
        return $html;
    }

    $in_link = 0;
    foreach ($parts as $i => $part) {
        if ($part === '') {
            continue;
        }

        if ($part[0] === '<') {
            if (preg_match('/^<a\b/i', $part)) {
                $in_link++;
                if (!preg_match('/\btarget\s*=/i', $part)) {
                    $parts[$i] = preg_replace('/^<a\b/i', '<a target="_blank" rel="noopener noreferrer"', $part);
                }
            } elseif (preg_match('/^<\/a\s*>/i', $part)) {
                $in_link = max(0, $in_link - 1);
            }
            continue;
        }

        if ($in_link > 0) {
            continue;
        }

        $parts[$i] = preg_replace_callback('~\b(?:https?://|www\.)[^\s<>"\']+~i', function ($m) {
            $url = $m[0];
            $trail = '';

            // Move trailing punctuation (e.g. end of sentence) out of the link
            while ($url !== '' && preg_match('/[.,;:!?)\]]$/', $url)) {
                $last = substr($url, -1);
                if ($last === ')' && substr_count($url, '(') >= substr_count($url, ')')) {
                    break; // balanced brackets, e.g. Wikipedia URLs
                }
                $trail = $last . $trail;
                $url = substr($url, 0, -1);
            }
            if ($url === '' || preg_match('~^(?:https?://|www\.)$~i', $url)) {
                return $m[0];
            }

            $href = preg_match('~^https?://~i', $url) ? $url : 'https://' . $url;
            return '<a href="' . $href . '" target="_blank" rel="noopener noreferrer">' . $url . '</a>' . $trail;
        }, $part);
    }

    return implode('', $parts);
}
}
