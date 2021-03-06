<?php

namespace Simple;

use finfo;

class FileUpload
{
    protected $filename;
    protected $tempName;
    protected $filesize;
    protected $extension;
    protected $fileType;
    protected $error;
    protected $storage;

    public function __construct($name)
    {
        $this->filename = $_FILES[$name]['name'];
        $this->tempName = $_FILES[$name]['tmp_name'];
        $this->filesize = $_FILES[$name]['size'];
        $this->extension = pathinfo($_FILES[$name]['name'], PATHINFO_EXTENSION);
        $this->fileType = $_FILES[$name]['type'];
        $this->error = $_FILES[$name]['error'];

        $this->storage = '../public/storage/';
    }


    /**
     *  Return the filename of the uploaded file
     * @return string
     */
    public function getFileName()
    {
        return $this->filename;
    }

    /**
     * Return the uploaded file size
     * @return mixed
     */
    public function getFileSize()
    {
        return $this->filesize;
    }

    /**
     * Return the file extension
     * @return mixed
     */
    public function getFileExtension()
    {
        return $this->extension;
    }

    /**
     * return the filetype
     * @return mixed
     */
    public function getFileType()
    {
        return $this->fileType;
    }

    /**
     * Return the  uploaded filename
     * @return string
     */
    public function getUploadedFileName()
    {
        $filename = sprintf("%s.%s",
            sha1_file($this->tempName),
            $this->extension
        );
        return $filename;
    }

    /**
     * @param string $path: The folder where the file is going to be uploaded.
     * @return bool
     * @throws \Exception
     */
    public function upload($path = 'null')
    {
        try
        {
            if (
                !isset($this->error) ||
                is_array($this->error)
            ) {
                throw new \Exception('Invalid parameters.');
            }

            switch ($this->error) {
                case UPLOAD_ERR_OK:
                    break;
                case UPLOAD_ERR_NO_FILE:
                    throw new \Exception('You are uploading an empty file.');
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    throw new \Exception('Uploaded file exceeded filesize limit.');
                default:
                    throw new \Exception('Unknown errors.');
            }

            $finfo = new finfo(FILEINFO_MIME_TYPE);
            if (false === $ext = array_search(
                    $finfo->file($this->tempName),
                    array(
                        'jpg' => 'image/jpeg',
                        'png' => 'image/png',
                        'gif' => 'image/gif',
                        'csv' => 'text/csv',
                        'txt' => 'text/plain',
                        'json' => 'application/json',
                        'xml' => 'application/xml',
                        'zip' => 'application/zip',
                        'pdf' => 'application/pdf',
                        'sql' => 'application/sql',
                        'doc' => 'application/msword',
                        'xls' => 'application/vnd.ms-excel',
                        'ppt' => 'application/vnd.ms-powerpoint',
                        'odt' => 'application/vnd.oasis.opendocument.text',
                        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                        'avi' => 'video/x-msvideo',
                        'jpm' => 'video/jpm',
                        'jpgv' => 'video/jpeg',
                        'mpeg' => 'video/mpeg',
                        'mp4' => 'video/mp4',
                        'mp3' => 'audio/mpeg'
                    ),
                    true
                )) {
                throw new RuntimeException('Invalid file format.');
            }
            if ($path!=null){
                $this->storage = $this->storage.$path;
            }
            if ( ! is_dir($this->storage)) {
                mkdir($this->storage,666,true);
            }
            $filename = sprintf("./$this->storage/%s.%s",
                sha1_file($this->tempName),
                $ext
            );
            if (!move_uploaded_file(
                $this->tempName,
                $filename
            )) {
                if (SHOW_ERRORS==true)
                    throw new \Exception('Failed to move uploaded file.');
                return false;
            } else {
                return true;
            }
        }
        catch (\RuntimeException $e)
        {
            throw new \Exception('Failed to move uploaded file. '.$e->getMessage());
        }
    }
}