<?php
class Logger {
    private $logFile;
    private $logLevel;
    
    const DEBUG = 0;
    const INFO = 1;
    const WARNING = 2;
    const ERROR = 3;
    
    public function __construct($logFile = 'app.log', $logLevel = self::INFO) {
        $this->logFile = dirname(__FILE__) . '/' . $logFile;
        $this->logLevel = $logLevel;
        
        // Erstelle Log-Datei, falls sie nicht existiert
        if (!file_exists($this->logFile)) {
            touch($this->logFile);
            chmod($this->logFile, 0666);
        }
    }
    
    private function log($level, $message, $context = []) {
        if ($level < $this->logLevel) {
            return;
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $levelName = $this->getLevelName($level);
        
        // Kontext in JSON formatieren
        $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
        
        // Log-Nachricht erstellen
        $logMessage = sprintf(
            "[%s] [%s] %s%s\n",
            $timestamp,
            $levelName,
            $message,
            $contextStr
        );
        
        // In Datei schreiben
        file_put_contents($this->logFile, $logMessage, FILE_APPEND);
    }
    
    private function getLevelName($level) {
        switch ($level) {
            case self::DEBUG:
                return 'DEBUG';
            case self::INFO:
                return 'INFO';
            case self::WARNING:
                return 'WARNING';
            case self::ERROR:
                return 'ERROR';
            default:
                return 'UNKNOWN';
        }
    }
    
    public function debug($message, $context = []) {
        $this->log(self::DEBUG, $message, $context);
    }
    
    public function info($message, $context = []) {
        $this->log(self::INFO, $message, $context);
    }
    
    public function warning($message, $context = []) {
        $this->log(self::WARNING, $message, $context);
    }
    
    public function error($message, $context = []) {
        $this->log(self::ERROR, $message, $context);
    }
    
    public function logException($exception, $context = []) {
        $this->error(
            sprintf(
                "Exception: %s in %s:%d\nStack trace: %s",
                $exception->getMessage(),
                $exception->getFile(),
                $exception->getLine(),
                $exception->getTraceAsString()
            ),
            $context
        );
    }
}

// Logger-Instanz erstellen
$logger = new Logger();

// Beispiel für die Verwendung:
// $logger->info("Benutzer hat sich angemeldet", ["username" => "testuser"]);
// $logger->error("Datenbankfehler", ["error" => "Connection failed"]);
?> 