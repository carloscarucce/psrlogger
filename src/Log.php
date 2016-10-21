<?php

namespace PsrLogger;

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

class Log extends AbstractLogger
{
    /**
     * @var array
     */
    private $options = [
        'dateFormat' => 'Y.m.d H:i:s'
    ];

    /**
     * @var bool
     */
    private $savedAsFile = false;

    /**
     * @var string
     */
    private $file = null;

    /**
     * Get fil
     *
     * @return null|string
     */
    public function getContents()
    {
        return $this->file ? file_get_contents($this->file) : null;
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed  $level
     * @param string $message
     * @param array  $context
     *
     * @return void
     */
    public function log($level, $message, array $context = array())
    {
        if (is_null($this->file)) {
            $this->createTempFile();
        }

        //Transform objects into arrays
        if (is_object($message)) {
            $message = (array) $message;
        }

        //Transform arrays into JSON
        if (is_array($message)) {
            $message = json_encode($message, JSON_PRETTY_PRINT);
        }

        $currentTime = date($this->options['dateFormat']);
        $levelName = $this->getLogLevelAsString($level);

        $message = "$currentTime ][$levelName] $message".PHP_EOL;
        file_put_contents($this->file, $message, FILE_APPEND);
    }

    /**
     * Save the log contents to a file.
     *
     * @param $filePath
     *
     * @return bool|null
     *
     * @throws \Exception
     */
    public function saveTo($filePath)
    {
        $success = null;

        //Check if was saved already
        if ($this->savedAsFile) {
            throw new \Exception('Log was saved before.');
        }

        //Attempt to move file
        if ($success = rename($this->file, $filePath)) {
            $this->savedAsFile = true;
        }

        return $success;
    }

    /**
     * @param $option
     * @param $value
     */
    public function setOption($option, $value) {
        $this->options[$option] = $value;
    }

    /**
     * @param $logLevel
     *
     * @return string
     */
    public function getLogLevelAsString($logLevel)
    {
        $levelName = null;

        switch ($logLevel) {
            case LogLevel::EMERGENCY:
                $levelName = 'EMERGENCY';
            break;
            case LogLevel::ALERT:
                $levelName = 'ALERT';
            break;
            case LogLevel::CRITICAL:
                $levelName = 'CRITICAL';
            break;
            case LogLevel::ERROR:
                $levelName = 'ERROR';
            break;
            case LogLevel::WARNING:
                $levelName = 'WARNING';
            break;
            case LogLevel::NOTICE:
                $levelName = 'NOTICE';
            break;
            case LogLevel::DEBUG:
                $levelName = 'DEBUG';
            break;
            case LogLevel::INFO:
            default:
                $levelName = 'INFO';
            break;
        }

        return '['.$levelName.']';
    }

    /**
     * Create temporary log file.
     */
    private function createTempFile()
    {
        // Create a temporary file in the temporary
        // files directory using sys_get_temp_dir()
        $this->file = tempnam(sys_get_temp_dir(), 'LOG');
        $this->info('Log created at '.date($this->options['dateFormat']).PHP_EOL);
    }

    /**
     * Remove the temporary log file.
     *
     * @return bool
     */
    private function removeTempFile()
    {
        //Isn't a temp file
        if ($this->savedAsFile)
            return true;

        return unlink($this->file);
    }

    /**
     * Log constructor.
     *
     * @param $fileName
     *
     * @throws \Exception
     */
    public function __construct($fileName = null)
    {
        if (!is_null($fileName)) {
            //Set a filename
            if (!is_string($fileName) || !is_writeable($fileName)) {
                throw new \Exception('Invalid filename');
            }
            $this->savedAsFile = true;
        }
    }

    /**
     * Log destructor.
     */
    public function __destruct()
    {
        $this->removeTempFile();
    }
}