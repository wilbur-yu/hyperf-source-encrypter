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

namespace WilburYu\HyperfSourceEncrypter\Command;

use Hyperf\Command\Command;
use Hyperf\Utils\Filesystem\FileNotFoundException;
use Hyperf\Utils\Filesystem\Filesystem;
use Hyperf\Utils\Str;
use Obfuscator;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\InputOption;
use function config;

/**
 * Class SourceEncryptCommand.
 *
 * @\Hyperf\Command\Annotation\Command
 */
class SourceEncryptCommand extends Command
{
    protected $name = 'encrypt:source';

    protected Filesystem $filesystem;

    protected string $basePath;

    protected array $sources;

    protected string $destination;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct();
        $this->filesystem = $container->get(Filesystem::class);
    }

    public function handle(): void
    {
        if (! defined('BASE_PATH')) {
            $this->output->error('constant BASE_PATH undefined');

            return;
        }

        $this->basePath = BASE_PATH . DIRECTORY_SEPARATOR;

        $force             = (bool) $this->input->getOption('force');
        $this->destination = (string) $this->input->getOption('destination');
        if (! $force && $this->filesystem->exists($this->basePath . $this->destination)
            &&
            ! $this->confirm("The directory {$this->basePath}{$this->destination} already exists. Delete directory?")) {
            $this->output->success('Command canceled.');

            return;
        }

        $keyLength = $this->input->getOption('key_length');

        $this->sources = (array) $this->input->getOption('source');
        if (is_string($this->sources)) {
            $this->sources = explode(',', $this->sources);
        }

        $this->filesystem->deleteDirectory($this->basePath . $this->destination);
        $this->filesystem->makeDirectory($this->basePath . $this->destination);

        foreach ($this->sources as $source) {
            $this->filesystem->makeDirectory($this->basePath . $this->destination . DIRECTORY_SEPARATOR . $source, 493,
                true);

            if ($this->filesystem->isFile($this->basePath . $source)) {
                $this->encryptFile($source, $this->destination, $keyLength);
                continue;
            }

            $files = $this->filesystem->allFiles($this->basePath . $source);

            foreach ($files as $file) {
                $filePath = Str::replaceFirst($this->basePath, '', $file->getRealPath());
                $this->encryptFile($filePath, $this->destination, $keyLength);
            }
        }

        $this->copyComplete();

        $this->output->success('Encrypting Completed Successfully!');
        $this->output->success("this->destination directory: {$this->basePath}{$this->destination}");
    }

    protected function copyComplete(): void
    {
        if (! config('source_encrypter.is_output_complete', false)) {
            return;
        }

        $exportDirs = array_merge($this->sources, [$this->destination]);
        $allDirs    = $this->filesystem->directories($this->basePath);
        foreach ($allDirs as $copyDir) {
            $relativePath = $this->filesystem->basename($copyDir);

            if (in_array($relativePath, $exportDirs, true)) {
                continue;
            }

            $this->filesystem->copyDirectory($copyDir, $this->destination . DIRECTORY_SEPARATOR . $relativePath);
        }

        $copyFiles = $this->filesystem->files($this->basePath, true);
        foreach ($copyFiles as $copyFile) {
            $copyFilePathname = $copyFile->getRelativePathname();
            $this->filesystem->copy($copyFilePathname, $this->destination . DIRECTORY_SEPARATOR . $copyFilePathname);
        }
    }

    public function configure(): void
    {
        parent::configure();
        $this->setDescription('Encrypts PHP files');
        $this->addOption(
            'source',
            's',
            InputOption::VALUE_REQUIRED,
            'Path(s) to encrypt',
            config('source_encrypter.source', ['app', 'config'])
        );
        $this->addOption(
            'destination',
            'd',
            InputOption::VALUE_REQUIRED,
            'destination directory',
            config('source_encrypter.destination', 'encrypted')
        );
        $this->addOption(
            'force',
            'f',
            InputOption::VALUE_REQUIRED,
            'Force the operation to run when destination directory already exists',
            false
        );
        $this->addOption(
            'key_length',
            'k',
            InputOption::VALUE_REQUIRED,
            'Encryption key length',
            config('source_encrypter.key_length', 32)
        );
    }

    private function encryptFile($filePath, $destination, $keyLength): void
    {
        $key = Str::random($keyLength);
        if ($this->filesystem->isDirectory($this->basePath . $filePath) &&
            ! $this->filesystem->exists($this->basePath . $destination . $filePath)) {
            $this->filesystem->makeDirectory(
                $destination . DIRECTORY_SEPARATOR . $filePath,
                493,
                true
            );
        }

        if ($this->filesystem->extension($filePath) !== 'php') {
            $this->filesystem->copy($filePath, $destination . DIRECTORY_SEPARATOR . $filePath);

            return;
        }

        try {
            $fileContents = $this->filesystem->get($this->basePath . $filePath);
        } catch (FileNotFoundException $e) {
            $this->output->error($e->getMessage());

            return;
        }

        $prepend = '<?php ' . "\r\n";
        $pattern = '/\<\?php/m';
        preg_match($pattern, $fileContents, $matches);
        if (! empty($matches[0])) {
            $fileContents = preg_replace($pattern, '', $fileContents);
        }
        $cipher = new Obfuscator($fileContents, $key);
        $this->filesystem->isDirectory(
            $this->basePath . $this->filesystem->dirname($destination . DIRECTORY_SEPARATOR . $filePath)
        ) || $this->filesystem->makeDirectory(
            $this->basePath . $this->filesystem->dirname($destination . DIRECTORY_SEPARATOR . $filePath),
            0755, true, true
        );
        $this->filesystem->put(
            $this->basePath . $destination . DIRECTORY_SEPARATOR . $filePath,
            $prepend . $cipher
        );

        unset($cipher, $fileContents);
    }
}
