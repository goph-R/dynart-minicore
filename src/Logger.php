<?php

namespace Dynart\Minicore;

class Logger {

    const CONFIG_LEVEL = 'logger.level';
    const CONFIG_PATH = 'logger.path';
    const CONFIG_DATE_FORMAT = 'logger.date_format';
    const DEFAULT_DATE_FORMAT = 'Y-m-d H:i:s';

    const INFO = 10;
    const WARNING = 20;
    const ERROR = 30;

    protected static $levelMap = [
        'info'    => self::INFO,
        'warning' => self::WARNING,
        'error'   => self::ERROR
    ];

    protected $config;
    protected $level;
    protected $path;
    protected $dateFormat;

    public function __construct() {
        $framework = Framework::instance();
        $config = $framework->get('config');
        $this->level = @self::$levelMap[$config->get(self::CONFIG_LEVEL)];
        $this->path = $config->get(self::CONFIG_PATH);
        $this->dateFormat = $config->get(self::CONFIG_DATE_FORMAT, self::DEFAULT_DATE_FORMAT);
    }

    public function getLevel() {
        return $this->level;
    }

    public function info($message) {
        if ($this->level <= Logger::INFO) {
            $this->log('INFO', $message);
        }
    }

    public function warning($message) {
        if ($this->level <= Logger::WARNING) {
            $this->log('WARNING', $message);
        }
    }

    public function error($message) {
        if ($this->level <= Logger::ERROR) {
            $this->log('ERROR', $message);
        }
    }

    protected function log($label, $message) {
        $text = date($this->dateFormat).' ['.$label.'] '.$message."\n\n";
        $dir = dirname($this->path);
        if (!file_exists($dir)) {
            mkdir($dir, 0x755, true);
        }
        $result = file_put_contents($this->path, $text, FILE_APPEND | LOCK_EX);
        if ($result === false) {
            throw new FrameworkException("Can't write to ".$this->path);
        }
    }

}