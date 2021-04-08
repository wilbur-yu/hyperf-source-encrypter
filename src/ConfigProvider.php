<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  wenber.yu@creative-life.club
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
namespace WilburYu\HyperfSourceEncrypter;

use WilburYu\HyperfSourceEncrypter\Command\SourceEncryptCommand;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
            ],
            'commands' => [
                SourceEncryptCommand::class,
            ],
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                ],
            ],
            'publish' => [
                [
                    'id' => 'config',
                    'description' => 'The config for wilbur-yu/hyperf-source-encrypter.',
                    'source' => __DIR__ . '/../publish/source_encrypter.php',
                    'destination' => BASE_PATH . '/config/autoload/source_encrypter.php',
                ],
            ],
        ];
    }
}
