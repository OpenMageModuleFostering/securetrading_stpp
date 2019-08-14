<?php

class Stpp_Utility_Log_Writer_File implements Stpp_Utility_Log_WriterInterface {
    protected $_logFilePath;
    
    protected $_logFileDirectory;
    
    protected $_archiveDirectory;
    
    public function __construct($logFileName, $logFileDirectory, $archiveDirectory = null) {
        $this->_logFilePath = $logFileDirectory . $logFileName . '.txt';
        $this->_logFileDirectory = $logFileDirectory;
        $this->_archiveDirectory = ($archiveDirectory ?: $logFileDirectory . 'archive' . DIRECTORY_SEPARATOR);
        
        if (!file_exists($this->_logFileDirectory)) {
            mkdir($this->_logFileDirectory);
        }
        
        if (!file_exists($this->_logFilePath)) {
            file_put_contents($this->_logFilePath, '');
        }
		
        if (!file_exists($this->_archiveDirectory)) {
            mkdir($this->_archiveDirectory);
        }
        
        if ($this->_mustMoveToArchive($this->_logFilePath)) {
            $this->_moveToArchive($this->_logFilePath);
        }
    }
        
    protected function _mustMoveToArchive($filePath) {
        $mTime = filemtime($filePath);
        $currentMonthAndYear = date('m_Y');
        $logFileMonthAndYear = date('m_Y', $mTime);
        $fileSize = filesize($filePath);
        return ($logFileMonthAndYear !== $currentMonthAndYear) && $fileSize > 0;
    }
    
    protected function _moveToArchive($filePath) {
        // Get the filename (the text between the last DIRECTORY_SEPARATOR and the first '.').
        $fileNameWithExtension = array_pop(explode(DIRECTORY_SEPARATOR, $filePath));
        $fileName = substr($fileNameWithExtension, 0, strrpos($fileNameWithExtension, '.'));

        // Calculate the full filepath to the log file:
        $mTime = filemtime($filePath);
        $logFileMonthAndYear = date('m_Y', $mTime);
        $newFilePath = $this->_archiveDirectory . $fileName . '_' . $logFileMonthAndYear . '.txt';
        
        // Ensure we do not overwrite an archive entry that already exists:
        if (file_exists($newFilePath)) {
            throw new Stpp_Exception(sprintf('The file "%s" already exists.', $newFilePath));
        }
        
        // Copy the log file to the archive and truncate the main log file:
        copy($filePath, $newFilePath);
        file_put_contents($filePath, '');
        return $this;
    }
        
    protected function _formatMessage($message) {
        if (is_array($message) || is_object($message)) {
            $message = print_r($message,1);
        }
        return $this->_getDate() .':' . trim($message) . PHP_EOL;
    }

    protected function _getDate() {
        return date('d-m-Y H:i:s T');
    }
    
    public function log($message) {
        $message = $this->_formatMessage($message);
        $file = fopen($this->_logFilePath, 'a');
        fwrite($file, $message);
        fclose($file);
        return $this;
    }
}