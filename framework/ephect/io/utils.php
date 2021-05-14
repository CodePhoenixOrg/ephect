<?php

namespace Ephect\IO;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class Utils
{
    public static function walkTreeFiltered($path, $filter = [])
    {
        $result = [];

        $l = strlen($path);

        $dir_iterator = new RecursiveDirectoryIterator($path, FilesystemIterator::FOLLOW_SYMLINKS);
        $iterator = new RecursiveIteratorIterator($dir_iterator, RecursiveIteratorIterator::CHILD_FIRST);

        foreach ($iterator as $file) {
            $fi = pathinfo($file->getPathName());

            if ($fi['basename'] ==  '.' || $fi['basename'] == '..') {
                continue;
            }

            if (!isset($fi['extension']) || $fi['extension'] === 'DS_Store') {
                continue;
            }

            if (count($filter) > 0 && in_array($fi['extension'], $filter)) {
                array_push($result, substr($file->getPathName(), $l));
            }
            if (count($filter) === 0) {
                array_push($result, substr($file->getPathName(), $l));
            }
        }

        return $result;
    }

    public static function walkTree(string $path, array &$tree = [])
    {
        $class_func = array(__CLASS__, __FUNCTION__);
        return is_file($path) ?
            @array_push($tree, $path) :
            array_map($class_func, glob($path . '/*'), $tree);
    }

    public static function delTree(string $path): bool
    {
        $class_func = array(__CLASS__, __FUNCTION__);
        return is_file($path) ?
            @unlink($path) :
            array_map($class_func, glob($path . '/*')) == @rmdir($path);
    }

    public static function safeMkDir(string $directory): bool
    {
        $result = false;

        $dir = pathinfo($directory, PATHINFO_DIRNAME);
        if (file_exists($dir)) {
            $result = mkdir($directory, 0775, true);
        }

        return $result;
    }

    public static function safeWrite(string $filename, string $contents): ?int
    {
        $result = null;

        $dir = pathinfo($filename, PATHINFO_DIRNAME);

        if (!file_exists($dir)) {
            $result = mkdir($dir, 0775, true);
        }
        $result = (false === $len = file_put_contents($filename, $contents)) ? null : $len;

        return $result;
    }

    public static function safeRead(string $filename): ?string
    {
        if (!file_exists($filename)) {
            return null;
        }
        $result = (false === $contents = file_get_contents($filename)) ? null : $contents;

        return $result;
    }
}
