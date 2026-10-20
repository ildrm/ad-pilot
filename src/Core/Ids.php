<?php
namespace AdPilot\Core;

final class Ids
{
    /** 128 random bits encoded in 26 Crockford base32 characters, not a timestamp. */
    public static function make(): string
    {
        $alphabet = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';
        $bits = '00';
        foreach (str_split(random_bytes(16)) as $byte) {
            $bits .= str_pad(decbin(ord($byte)), 8, '0', STR_PAD_LEFT);
        }
        $id = '';
        for ($i = 0; $i < 130; $i += 5) {
            $id .= $alphabet[bindec(substr($bits, $i, 5))];
        }
        return $id;
    }
}
