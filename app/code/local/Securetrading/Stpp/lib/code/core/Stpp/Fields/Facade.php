<?php

class Stpp_Fields_Facade extends Stpp_Facade {
    public function newAdminFields() {
        return new Stpp_Fields_Admin();
    }
    
    public function newFrontendFields() {
        return new Stpp_Fields_Frontend();
    }
}