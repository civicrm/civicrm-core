<?php

namespace MimeType;

class MimeType
{
    const DEFAULT_MIME_TYPE = 'application/octet-stream';

    /**
     * @param $filename
     * @return string
     * @throws \Exception
     */
    public static function getType($filename)
    {
        self::validateFilename($filename);

        $pathInfo = pathinfo($filename);

        $extension = isset($pathInfo['extension']) ? strtolower($pathInfo['extension']) : '';

        return self::findType($extension);
    }

    /**
     * @param $filename
     * @throws \Exception
     */
    private static function validateFilename($filename)
    {
        if (!is_string($filename)) {
            throw new \Exception('Filename not a string');
        }

        if ($filename == '') {
            throw new \Exception('No filename given');
        }
    }

    /**
     * @param $extension
     * @return string
     */
    private static function findType($extension)
    {
        return isset(Mapping::$types[$extension]) ? Mapping::$types[$extension] : self::DEFAULT_MIME_TYPE;
    }


}

