<?php

namespace App\Domains;

class PersonnelDomain extends Domain {
    public function __construct() {
        parent::__construct("ds_personnel");
    }

    public static function getPersonnel($id){
        return (new PersonnelDomain)->internalRequest('GET', "/$id/personnel");
    }

    public static function getAllPersonnel(){
        return (new PersonnelDomain)->internalRequest('GET', "/allPersonnel");
    }
}