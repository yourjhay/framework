<?php

namespace Simple;

use finfo;

class FileUpload 
{
    protected $filename;
    protected $tempName;
    protected $filesize;
    protected $extension;
    protected  $fileType;
    protected  $error;
    public function __construct($name)
    {
        $this->filename = $_FILES[$name]['name'];
        $this->tempName = $_FILES[$name]['tmp_name'];
        $this->filesize = $_FILES[$name]['size'];
        $this->extension = pathinfo($_FILES[$name]['name'], PATHINFO_EXTENSION);
        $this->fileType = $_FILES[$name]['type'];
        $this->error = $_FILES[$name]['error'];
    }
    

    public function getFileName()
    {
        return $this->filename;
    }

    public function getFileSize()
    {
        return $this->filesize;
    }

    public function getFileExtension()
    {
        return $this->extension;
    }

    public function getFileType()
    {
        return $this->fileType;
    }

    public function upload($path)
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
                    throw new \Exception('No file sent.');
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    throw new \Exception('Exceeded filesize limit.');
                default:
                    throw new \Exception('Unknown errors.');
            }

            $finfo = new finfo(FILEINFO_MIME_TYPE);
            if (false === $ext = array_search(
                    $finfo->file($_FILES['upfile']['tmp_name']),
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

            if (!move_uploaded_file(
                $this->tempName,
                sprintf("./$path/%s.%s",
                    sha1_file($this->tempName),
                    $ext
                )
            )) {
                throw new \Exception('Failed to move uploaded file.');
            }
        }
        catch (\RuntimeException $e)
        {
            throw new \Exception('Failed to move uploaded file. '.$e->getMessage());
        }
    }
}