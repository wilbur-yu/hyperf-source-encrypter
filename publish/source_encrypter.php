<?php

declare(strict_types = 1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  wenber.yu@creative-life.club
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
return [
    'source'             => ['app', 'config'], // Path(s) to encrypt
    'destination'        => 'encrypted', // Destination path
    'key_length'         => 32, // Encryption key length
    'is_output_complete' => true, // Whether to enter the complete project directory
];
