<?php

namespace Kiri\Config;

use Kiri\Core\HashMap;

class ConfigProvider
{

    private HashMap $hashMap;


    /**
     *
     */
    public function __construct()
    {
        $this->hashMap = new HashMap();
        $this->load(sweep(APP_PATH . '/config'));

        $this->enableEnvConfig(APP_PATH . '.env');
    }


    /**
     * @param string $key
     * @param int|string|bool|array|null $default
     * @return int|string|bool|array|null
     */
    public function get(string $key, int|string|bool|null|array $default = null): int|string|bool|null|array
    {
        $keys = explode($key, '.');

        $hashMap = $this->hashMap->get(array_unshift($keys));
        if (is_null($hashMap)) {
            return $default;
        }
        if (count($keys) < 1 || !is_array($hashMap)) {
            return $hashMap;
        }
        foreach ($keys as $string) {
            if (!isset($hashMap[$string])) {
                return $default;
            }
            $hashMap = $hashMap[$string];
        }
        return $hashMap;
    }


    /**
     * @param array $config
     * @return void
     */
    private function load(array $config): void
    {
        foreach ($config as $key => $value) {
            $this->hashMap->put($key, $value);
        }
    }


    /**
     * @param $envPath
     * @return void
     */
    private function enableEnvConfig($envPath): void
    {
        if (!file_exists($envPath)) {
            return;
        }
        $lines = $this->readLinesFromFile($envPath);
        foreach ($lines as $line) {
            if (!$this->isComment($line) && $this->looksLikeSetter($line)) {
                [$key, $value] = explode('=', $line);
                putenv(trim($key) . '=' . trim($value));
            }
        }
    }


    /**
     * Read lines from the file, auto detecting line endings.
     *
     * @param string $filePath
     *
     * @return array
     */
    protected function readLinesFromFile(string $filePath): array
    {
        // Read file into an array of lines with auto-detected line endings
        return file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    }

    /**
     * Determine if the line in the file is a comment, e.g. begins with a #.
     *
     * @param string $line
     *
     * @return bool
     */
    protected function isComment(string $line): bool
    {
        $line = ltrim($line);

        return isset($line[0]) && $line[0] === '#';
    }

    /**
     * Determine if the given line looks like it's setting a variable.
     *
     * @param string $line
     *
     * @return bool
     */
    protected function looksLikeSetter(string $line): bool
    {
        return str_contains($line, '=');
    }

}