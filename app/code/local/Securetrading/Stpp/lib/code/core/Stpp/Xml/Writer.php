<?php

class Stpp_Xml_Writer extends XmlWriter {    
    public function writeElement($name, $content = null, $allowVoid = false) {
        if ($content !== null || $allowVoid) {
            parent::writeElement($name, $content);
        }
    }
}