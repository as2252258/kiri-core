<?php


namespace Annotation;


class FileTree
{

    private array $files = [];


    private array $childes = [];


    /**
     * @param $path
     * @return $this|null
     */
    public function getChild($path): ?static
    {
        if (!isset($this->childes[$path])) {
            $this->addChild($path, new FileTree());
        }
        return $this->childes[$path];
    }


    /**
     * @param string $path
     * @param FileTree $fileTree
     */
    public function addChild(string $path, FileTree $fileTree)
    {
        $this->childes[$path] = $fileTree;
    }


    /**
     * @param string $className
     */
    public function addFile(string $className)
    {
        $this->files[] = $className;
    }


    /**
     * @return array
     */
    public function getFiles(): array
    {
        return $this->files;
    }


    /**
     * @return array
     */
    public function getChildes(): array
    {
        return $this->childes;
    }


}
