<?php 

namespace App\Helpers;

class ValidationHelper {
    
    public static function hatValidationRule() {
        // validate hat level
        return [
            'hat_name' => 'required|string|max:255',
            'hat_description' => 'required|string|max:255',
        ];
    }
    public static function hatPerentChildValidationRule() {
        // validate hat level
        return [
            'parent' => 'required|integer',
            'child' => 'required|integer',
        ];
    }
    public static function hatLevelRankValidationRule() {
        // validate hat level
        return [
            'hat' => 'required|integer',
            'level' => 'required|integer',
            'rank' => 'required|integer',
        ];
    }
    public static function personalHatValidationRule () {
        return [
            'personnel_id' => 'required',
            'hat_lr_id' => 'required'
        ];
    }

    public static function completeHatValidationRule() {
        return [
            'parent' => 'required',
            'title' => 'required',
            'level' => 'required',
            'rank' => 'required',
        ];
    }

}
