<?php

namespace Simple;

class FileUpload 
{
    protected $filename;
    protected $filesize;
    protected $type;
    public function __construct($name)
    {
        $this->filename = $_FILES[$name]['name'];
        $this->filesize = $_FILES[$name]['size'];
        $this->type = pathinfo($_FILES[$name]['name'], PATHINFO_EXTENSION);
    }
    

    public function getFileName()
    {
        return $this->filename;
    }

    public function getFileSize()
    {
        return $this->filesize;
    }

    public function getFileType()
    {
        return $this->type;
    }
}