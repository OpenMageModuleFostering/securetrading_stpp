<?php

class Stpp_Utility_Translator_Base implements Stpp_Utility_Translator_BaseInterface {
    const METHOD_STPP_CORE = 1;
    const METHOD_USER_FUNC = 2;
    
    protected $_fileUtility;
    
    protected $_config = array();
    
    protected $_translationMethod = self::METHOD_STPP_CORE;
    
    protected $_targetLanguage;
    
    protected $_translations = array();
    
    public function __construct($sourcePhpDirectory, $translationsDirectory) {
        if (!file_exists($translationsDirectory)) {
            mkdir($translationsDirectory);
        }
        
        $baseTranslationFile = $translationsDirectory . 'core.php';
        
        if (!file_exists($baseTranslationFile)) {
            $directory = new RecursiveDirectoryIterator($sourcePhpDirectory);
            $iterator = new RecursiveIteratorIterator($directory);
            $regex = new RegexIterator($iterator, '/^.+\.php$/i', RecursiveRegexIterator::GET_MATCH);
            
            foreach($regex as $file) {
                $files[] = $file[0];
            }
            
            $matches = null;
            $translatableStrings = array();
            
            foreach($files as $file) {
                $fileContents = file_get_contents($file);
                preg_match_all('!\$this->__\((\'|")(.+)(?:\1)\)!', $fileContents, $matches);
                $translatableStrings = array_merge($matches[2], $translatableStrings);
            }
            
            $finalTranslatableStrings = array_unique($translatableStrings); // Remove duplicate text strings.
            
            $code = "<?php" . PHP_EOL . PHP_EOL . '$translations = array(' . PHP_EOL;
            
            foreach($finalTranslatableStrings as $string) {
				$string = str_replace("\'", "'", $string);
				$replacedString = str_replace("'", "\'", $string);
                $code .= sprintf("\t'%s' => '',", $replacedString) . PHP_EOL;
            }
            
            $code .= ');';
			
            file_put_contents($baseTranslationFile, $code);
        }
    }
    
    public function setConfig(array $config = array()) {
        $this->_config = $config;
    }
    
    public function setTranslationMethod($method) {
        $this->_translationMethod = $method;
        return $this;
    }
    
    public function setTargetLanguage($string) {
        $this->_targetLanguage = $string;
    }
    
    public function translate($message) {
        if ($this->_translationMethod === self::METHOD_USER_FUNC) {
            if (!isset($this->_config["method"])) {
                throw new Stpp_Exception("Method name was not set.");
            }
            
            if (!is_string($this->_config["method"])) {
                throw new Stpp_Exception("The method name must be a string.");
            }
            
            // validate params
            if (!isset($this->_config["params"])) {
                throw new Stpp_Exception("Parameters not set.");
            }
            
            if (!is_array($this->_config["params"])) {
                throw new Stpp_Exception("The parameters must be an array.");
            }
            
            // validate params message (we put $message var into this param)
            if (!array_key_exists("message", $this->_config["params"])) {
                throw new Stpp_Exception("The message index does not exist in the parameters array.");
            }
            
            $this->_config["params"]["message"] = $message;
            
            if (isset($this->_config["class"])) {
                if (is_string($this->_config["class"])) {
                    if (!class_exists($this->_config["class"])) {
                        throw new Stpp_Exception(sprintf('Class name "%s" does not exist.'));
                    }
                }
                else {
                    if (!is_object($this->_config["class"])) {
                        throw new Stpp_Exception('The class is not a string or an object reference.');
                    }
                }
                $func = array($this->_config["class"], $this->_config["method"]);
            }
            else {
                $func = $this->_config["method"];
            }
            
            return call_user_func_array($func, $this->_config["params"]);
        }
        elseif ($this->_translationMethod === self::METHOD_STPP_CORE) {
            return $this->_translate($message);
        }
        else {
            throw new Stpp_Exception('Invalid translation method specified.');
        }
    }
    
    protected function _translate($message) {
        $targetLanguage = $this->_targetLanguage;
        
        if (!isset($targetLanguage) || empty($targetLanguage) || !is_string($targetLanguage)) {
            return $message;
        }
        
        $frameworkDirectory = realpath(dirname(dirname(__FILE__)));
        $targetLanguageTranslationFile = $frameworkDirectory . DIRECTORY_SEPARATOR . 'translations' . DIRECTORY_SEPARATOR . $this->_targetLanguage . '.php';
        
        if (!isset($this->_translations[$targetLanguage])) {
            if (file_exists($targetLanguageTranslationFile)) {// load the file and put its contents into $this->_translations.
                $translations = array();
                require_once($targetLanguageTranslationFile);
                
                if (!empty($translations)) { // $translations should be populated by $targetLanguageTranslationFile.
                    $this->_translations[$targetLanguage] = $translations;
                }
            }
        }

        if (isset($this->_translations[$targetLanguage][$message])) {;
            return $this->_translations[$targetLanguage][$message];
        }
        return $message;
    }
}