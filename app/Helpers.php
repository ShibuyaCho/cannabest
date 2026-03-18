<?php

if (!function_exists('parseEmojis')) {
    /**
     * Replace the shortcode ":gls:" with an emoji image,
     * but ignore text that is already part of an HTML tag.
     *
     * @param string $text
     * @return string
     */
    function parseEmojis($text)
    {
        // Split text by HTML tags. Tags will be in separate elements.
        $parts = preg_split('/(<[^>]+>)/', $text, -1, PREG_SPLIT_DELIM_CAPTURE);

        // Process only parts that are not HTML tags.
        foreach ($parts as &$part) {
            if (!preg_match('/^<[^>]+>$/', $part)) {
                // Replace :gls: only in non-tag text.
                $part = str_replace(
                    ':gls:',
                    '<img src="' . asset('assets/img/weed-leaf.png') . '" alt="weed" class="weed-emoji" style="width:16px;height:16px;vertical-align:top;">',
                    $part
                );
            }
        }
        return implode('', $parts);
    }
}