<?php 

namespace App\Services;

use App\Domains\PersonnelDomain;
use App\Imports\HatsImport;
use App\Imports\ImportHats;
use App\Models\Hat;
use App\Models\HatLevelRank;
use App\Models\HatParentChild;
use App\Models\PersonnelHat;
use App\Repository\HatLevelRepository;
use App\Repository\HatRankRepository;
use App\Repository\HatRepository;
use App\Repository\HatLevelRankRepository;
use App\Repository\HatPcrRepository;
use App\Repository\PersonnelHatRepository;
use App\Repository\PersonnelHatHistoryRepository;
use App\Traits\ApiResponseHelper;
use Error;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class CompletedHatService extends Service {
    private HatRepository $hatRepository;
    private HatLevelRepository $hatLevelRepository;
    private HatRankRepository $hatRankRepository;
    private HatLevelRankRepository $hatLevelRankRepository;
    private HatPcrRepository $hatPcrRepository;
    private PersonnelHatRepository $personnelHatRepository;
    private PersonnelHatHistoryRepository $personnelHatHistoryRepository;

    use ApiResponseHelper;
    public function __construct() {
        $this->hatRepository = app()->make(HatRepository::class);
        $this->hatLevelRepository = app()->make(HatLevelRepository::class);
        $this->hatRankRepository = app()->make(HatRankRepository::class);
        $this->hatLevelRankRepository = app()->make(HatLevelRankRepository::class);
        $this->hatPcrRepository = app()->make(HatPcrRepository::class);
        $this->personnelHatRepository = app()->make(PersonnelHatRepository::class);
        $this->personnelHatHistoryRepository = app()->make(PersonnelHatHistoryRepository::class);
        parent::__construct();
    }

    public function getHatDetails($id){
        try{
            $hatDetails = $this->hatLevelRankRepository->find($id);
            if(!$hatDetails) {
                return $this->errorResponse( 'Hat not found', Response::HTTP_NOT_FOUND);
            }
            $hatObj = $this->setHatDetailsObj($hatDetails);
            return $this->successResponse( $hatObj , 'Hat details', Response::HTTP_OK);
        }catch (Exception $e){
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getAllHats() {
        // get all hat levels
        try{
            $hatObj = array();
            $hatDetails = $this->hatLevelRankRepository->findAll();

            if(!$hatDetails) {
                return $this->errorResponse( 'Hat not found', Response::HTTP_NOT_FOUND);
            }
            foreach ($hatDetails as $hatDetail) {
                $hatObj[] = $this->setHatDetailsObj($hatDetail);
            }
            return $this->successResponse( $hatObj , 'Hats ', Response::HTTP_OK);
        }  catch (Exception $e) {
            return $this->errorResponse( $e->getMessage(),Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    

    public function addHatLevelRank($request){
        try{
            $hatLevelRank = $request;
            Validator::make($hatLevelRank, $this->hatLevelRankValidationRule())->validate();
            $hatObj = $this->hatRepository->find($hatLevelRank['hat']);
            if(!isset($hatObj->id)){
                return $this->errorResponse( 'Hat not found', Response::HTTP_NOT_FOUND);
            }
            $hatLevelObj = $this->hatLevelRepository->find($hatLevelRank['level']);
            if(!isset($hatLevelObj->id)){
                return $this->errorResponse( 'Hat level not found', Response::HTTP_NOT_FOUND);
            }
            $hatRankObj = $this->hatRankRepository->find($hatLevelRank['rank']);
            if(!isset($hatRankObj->id)){
                return $this->errorResponse( 'Hat rank not found', Response::HTTP_NOT_FOUND);
            }
            $hatLevelRankObj = $this->hatLevelRankRepository->hatLevelRankExits($hatLevelRank['hat'], $hatLevelRank['level'], $hatLevelRank['rank'], $hatLevelRank['title']);
            if(isset($hatLevelRankObj->id)){
                return $this->errorResponse( 'Hat level rank already exists', Response::HTTP_CONFLICT);
            }
           $data['hat_id'] = $request['hat'];
           $data['hat_level_id'] = $hatLevelRank['level'];
           $data['hat_rank_id'] = $hatLevelRank['rank'];
           $data['name'] = $hatLevelRank['title'];
           $hatLevelRankObj = $this->hatLevelRankRepository->create($data);
            return $this->successResponse($hatLevelRankObj,'Hat level rank successfully added', Response::HTTP_CREATED);
        } catch (Exception $e) {
            return $this->errorResponse( $e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function addHatPerentChild($hatPerentChild) {
        // add new hat
        try{
             Validator::make($hatPerentChild, $this->hatPerentChildValidationRule())->validate();
             $hatLrP = $this->hatLevelRankRepository->find($hatPerentChild['parent']);
                if(!isset($hatLrP->id)){
                    return $this->errorResponse( 'Parent hat level rank not found', Response::HTTP_NOT_FOUND);
                    }
            $hatLrC = $this->hatLevelRankRepository->find($hatPerentChild['child']); 
                if(!isset($hatLrC->id)){
                    return $this->errorResponse( 'Child hat level rank not found', Response::HTTP_NOT_FOUND);
                    }

             $hpcP = $this->hatLevelRankRepository->getByColumn('hat_lr_parent', $hatPerentChild['parent'])->getByColumn('hat_lr_child', $hatPerentChild['child']);
                if(isset($hpcP->id)){
                    return $this->errorResponse( 'Hat parent child already exists', Response::HTTP_CONFLICT);
                    }
                $hatPcObj['hat_lr_parent'] = $hatPerentChild['parent'];
                $hatPcObj['hat_lr_child'] = $hatPerentChild['child'];
                $hatPerentChild = $this->hatPcrRepository->create($hatPcObj);
                return $this->successResponse($hatPerentChild,'Hat relationship added successfully', Response::HTTP_CREATED);
        } catch (Exception $e) {
            return $this->errorResponse( $e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function getOrgChart(){
        try{
            $orgChart = array();
            $hatLevelRank = $this->hatLevelRankRepository->findAll();
            $hatPerentChild = $this->hatPcrRepository->findAll();
            foreach($hatPerentChild as $hpc){
                $hasParent = $this->hasPerent($hpc->hat_lr_parent, $hatLevelRank);
                if($hasParent != false){
                    $orgChart[$hpc->hat_lr_parent][] = $hpc->hat_lr_child;
                }else{
                    if(isset($orgChart[$hpc->hat_level_rank_parent->name])){
                        $orgChart[$hpc->hat_level_rank_parent->name]['children'][] = $this->getHatChildren($hpc->hat_lr_child,$hatLevelRank);
                        $orgChart[$hpc->hat_level_rank_parent->name]['id'] = $hpc->id;
                    } else {
                        $orgChart[$hpc->hat_level_rank_parent->name] = array('children' => array($this->getHatChildren($hpc->hat_lr_child, $hatLevelRank)));
                        $orgChart[$hpc->hat_level_rank_parent->name]['id']= $hpc->id;
                    }
                }
            }
            if(empty($orgChart)){
                return $this->errorResponse( 'No hat relationship found', Response::HTTP_NOT_FOUND);
            }
            return $this->successResponse( $orgChart , 'Hating Chart ', Response::HTTP_OK);
        }  catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function setUpPersonnelToHat($request) {
        try{
            Validator::make($request,$this->personalHatValidationRule())->validate();
            $personnel_id = $request['personnel_id'];
            $personnel = PersonnelDomain::getPersonnel($personnel_id);
            if(!$personnel) {
                return $this->errorResponse("Personnel not found", Response::HTTP_NOT_FOUND);
            }
            $hrl_id = $request['hat_lr_id'];
            $hatLr =  $this->hatLevelRankRepository->find($hrl_id);
            if(!isset($hatLr->id)){
                return $this->errorResponse("Hat level rank does not exist", Response::HTTP_NOT_FOUND);
            }
            $personnelHat = $this->personnelHatRepository->create($request);
            return $this->successResponse($personnelHat, "Personnel hat relationship created successfully", Response::HTTP_OK);
        } catch (Exception $e){
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }
    
    public function completeHat() {
        try{
            $hatLevelRank = $this->hatLevelRankRepository->findAll();
           if($hatLevelRank->isEmpty()) {
                return $this->errorResponse("No hat level rank found", Response::HTTP_NOT_FOUND);
            }
            return $this->successResponse($hatLevelRank, "Personnel hat relationship created successfully", Response::HTTP_OK);
        } catch (Exception $e){
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }
    public function addCompleteHat($request) {
        try{
            Validator::make($request,$this->completeHatValidationRule())->validate();

            $hatLevelRank = $this->addHatLR($request);
            if(!isset($hatLevelRank->id)){
                return $this->errorResponse("Hat level rank not created", Response::HTTP_NOT_FOUND);
            }
            if($request['parent'] != null){
            $this->setHatPc($request['parent'], $hatLevelRank->id);
            }
            if(isset($request['personnel']) && !empty($request['personnel'])){
                $this->assignHat($request['personnel'], $hatLevelRank->id);
            }else{
                $this->assignHatToParent($request['parent'], $hatLevelRank->id);
            }
    
            return $this->successResponse($hatLevelRank, "Complete hat created successfully", Response::HTTP_OK);
        } catch (Exception $e){
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function updateCompleteHat($id, $request) {
        try{
            Validator::make($request,$this->completeHatValidationRule())->validate();

            $hatLevelRank = $this->hatLevelRankRepository->find($id);
            if(!isset($hatLevelRank->id)){
                return $this->errorResponse("Hat level rank not created", Response::HTTP_NOT_FOUND);
            }
            $this->updateHatLR($request, $hatLevelRank);
            
            if($request['parent'] != null || $request['parent'] != 'N/A'){
             $this->udateHatPc($request['parent'], $hatLevelRank->id);
            }
            
            if($request['personnel'] != null){
                $this->updateAssignment($request['personnel'], $hatLevelRank->id);
            }
            
            return $this->successResponse($hatLevelRank, "Complete hat updated successfully", Response::HTTP_OK);
        } catch (Exception $e){
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }
    public function deletePersonnelHats($id){
        try{
            $hatPerson = $this->personnelHatRepository->find($id);
            if(!isset($hatPerson->id)){
                return $this->errorResponse("Hat not assigned to this person", Response::HTTP_NOT_FOUND);
            }
            $deletedHistory['personnel_id'] = $hatPerson->personnel_id;
            $deletedHistory['hat_lr_id'] = $hatPerson->hat_lr_id;
            $deletedHistory['type'] = $hatPerson->type;
            $this->personnelHatHistoryRepository->create($deletedHistory);
            $this->personnelHatRepository->delete($id);
            return $this->successResponse('', 'Assignment deleted successfully', Response::HTTP_OK);
        }
        catch (Exception $e){
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }
    public function hattingTable() {
        try{
            $hatTable = array();
            $hatChart = array();
            $hatLevelRank = $this->hatLevelRankRepository->findAll(); 
            $hatParentChild = $this->hatPcrRepository->findAll();
            $newPersonnel = $this->setPersonnel();
            foreach($hatLevelRank as $hatLevel){
                $hasParent = $this->hasPerent($hatLevel->id, $hatParentChild);

                $hatChart['id'] = $hatLevel->id;
                $hatChart['hat'] = $this->getHatName($hatLevel->hat_id);
                $hatChart['level'] = $this->getLevel($hatLevel->hat_level_id);
                $hatChart['wearer'] =$this->setParentHatWerer($hatLevel->id,$hatLevelRank, $newPersonnel);
                $hatChart['reportsTo'] = !$hasParent? 'N/A':$this->getParent($hatLevel->id, $hatParentChild) ;
                $hatTable[] = $hatChart;
            }
            
            if(empty($hatTable)){
                return $this->errorResponse( 'No hat relationship found', Response::HTTP_NOT_FOUND);
            }
            return $this->successResponse( $hatTable , 'Hating Chart ', Response::HTTP_OK);
        }  catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function hattingChart(){
        try{
            $hatChart = array();
            $hatLevelRank = HatLevelRank::all();
            $hatParentChild = HatParentChild::all();
            $parentArray = $this->getUniqueParent($hatParentChild);
            $newPersonnel = $this->setPersonnel();
            $hasParent = $this->hasPerent($parentArray[0], $hatParentChild);
            if(!$hasParent){
                            $parentObj = $this->hatLevelRankRepository->find($parentArray[0]);
                            if($this->getHatName($parentObj->hat_id) != 'N/A'){
                            $hatChart['hat'] = $this->getHatName($parentObj->hat_id);
                            $hatChart['level'] = $this->getLevel($parentObj->hat_level_id);
                            $hatChart['wearer'] =$this->setParentHatWerer($parentObj->id,$hatLevelRank, $newPersonnel);
                            $hatChart['reportsTo'] = 'N/A';
                            $hatChart['id'] = $parentObj->id;
                            $hasChildren = $this->hasChildren($parentArray[0], $hatParentChild);
                            if($hasChildren){
                            $hatChart['children']= $this->setSubHat($parentArray[0], $hatParentChild, $newPersonnel, $hatLevelRank);
                            }
                        }else{
                            $hatChart = $this->setSubHat($parentArray[0], $hatParentChild, $newPersonnel, $hatLevelRank);
                        }
                    
            }
            if(empty($hatChart)){
                return $this->errorResponse( 'No hat relationship found', Response::HTTP_NOT_FOUND);
            }
            return $this->successResponse( $hatChart , 'Hating Chart ', Response::HTTP_OK);
        }  catch (Exception $e) {
            return $this->errorResponse( $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function deleteAssignment($id){
        try{
            $hatPerson = $this->personnelHatRepository->find($id);
            if(!isset($hatPerson->id)){
                return $this->errorResponse("Hat not assigned to this person", Response::HTTP_NOT_FOUND);
            }
            $this->personnelHatRepository->delete($hatPerson->id);
            return $this->successResponse('', "Assignment deleted successfully", Response::HTTP_OK);
        } catch (Exception $e){
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function importData($all)
    {
        try {
            $file = $all['file'];
            $imported = (new HatsImport)->toArray($file);
            foreach ($imported as $hat) {
            foreach($hat as $h){
                if(!empty($h)){
                    $hatFull = $this->findHatByName($h[3]);
                    $hatChildId = $this->getHatLevelRankBy($hatFull->id);
                    $hatParent = $this->findHatByName($h[4]);
                    if(isset($hatParent->id)){
                    $hatParentId = $this->getHatLevelRankBy($hatParent->id);
                    
                    if($this->existByParentChild($hatParentId->id, $hatChildId->id) == 0){
                        $parentChild['hat_lr_parent'] = $hatParentId->id;
                        $parentChild['hat_lr_child'] = $hatChildId->id;
                        $this->hatPcrRepository->create($parentChild);
                    }
                }else{
                    if($this->existByParentChild(221, $hatChildId->id) == 0){
                        $parentChild['hat_lr_parent'] = 221;
                        $parentChild['hat_lr_child'] = $hatChildId->id;
                        $this->hatPcrRepository->create($parentChild);
                    }
                }
                }
            }
        }
        }catch (Exception $e){
            error_log($e->getMessage());
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }
    private function findHatByName($name){
        $hat = $this->hatRepository->getByColumn('hat_name', $name)->first();
        if(!isset($hat->id)){
            return null;
        }
        return $hat;
    }
    private function hatRankLevelExistBy($hatId, $levelId, $rankId){
        $hatLevelRank = $this->hatLevelRankRepository->hatLevelRankExits($hatId, $levelId, $rankId);
        if(!isset($hatLevelRank->id)){
            return 0;
        }
        return 1;
    }
    private function getRankLevelExistBy($hatId, $levelId, $rankId){
        $hatLevelRank = $this->hatLevelRankRepository->hatLevelRankExits($hatId, $levelId, $rankId);
            return $hatLevelRank->id;
    }
    private function getHatLevelRankBy($hatId){
        $hatLevelRank = $this->hatLevelRankRepository->getByColumn('hat_id', $hatId)->first();
        return $hatLevelRank;
    }
    private function hatPersonnelExistBy($personnelId, $hatId){
        $hatPersonnel = $this->personnelHatRepository->personnelHatExits($personnelId, $hatId);
        if(!isset($hatPersonnel->id)){
            return 0;
        }
        return 1;
    }
    private function existByParentChild($parent, $child){
        $hatPc = $this->hatPcrRepository->hatPcrExists($parent, $child);
        if(!isset($hatPc->id)){
            return 0;
        }
        return 1;
    }
    public function importHat($all){
        try{
            $file = $all['file'];
            $imported = (new ImportHats)->toArray($file);
            foreach($imported as $hat){
                foreach($hat as $h){

                    if(!empty($h[0])){
                      $hats['hat_name'] = $h[0];
                      $hats['hat_description'] = $h[0];
                      $this->hatRepository->create($hats);
                        ///error_log(print_r($hats, true));
                    }
                }
            }
        }catch (Exception $e){
            error_log($e->getMessage());
        }

    }
 
    private function getUniqueParent($data){
        $parentArray = array();
        foreach($data as $hpc){
            if(!in_array($hpc->hat_lr_parent, $parentArray)){
                $parentArray[] = $hpc->hat_lr_parent;
            }
        }
        usort($parentArray, function($a, $b) {
            $hatParentChild = HatParentChild::all();
            return $this->parentCount($a,$hatParentChild) - $this->parentCount($b,$hatParentChild);
        });
        return $parentArray;
    }
    private function hasPerent($parent, $hatLevelRank){
        foreach($hatLevelRank as $hlr){
            if($hlr->hat_lr_child == $parent){
                return $hlr;
            }
        }
        return false;
    }

    private function getParent($parent, $hatLevelRank){
        foreach($hatLevelRank as $hlr){
            if($hlr->hat_lr_child == $parent){
                $hat = $this->hatLevelRankRepository->find($hlr->hat_lr_parent);
                return $this->getHatName($hat->hat_id);
            }
        }
        return false;
    }
    private function parentCount($parent, $hatLevelRank){
        $count = 0;
        foreach($hatLevelRank as $hlr){
            if($hlr->hat_lr_child == $parent){
                $count++;
            }
        }
        return $count;
    }
    private function hasChildren($parent, $hatLevelRank){
        foreach($hatLevelRank as $hlr){
            if($hlr->hat_lr_parent == $parent){
                return true;
            }
        }
        return false;
    }
    private function getHatsTree($hats){
        $hatsTree = [];
        foreach($hats as $hat){
            $hatsTree[$hat['id']] = $hat;
        }
        foreach($hats as $hat){
            if(isset($hatsTree[$hat['parent']])){
                $hatsTree[$hat['parent']]['children'][] = $hat['id'];
            }
        }
        return $hatsTree;
    }

    private function getHatChildren($child , $hatLevelRank){
        foreach($hatLevelRank as $hlr){
            if($hlr->id == $child){
                return $hlr;
            }
        }
        return null;

    }
    private function setHatWerer($child, $hatLevelRank, $personnel, $hatParentChild){
        $children = array();
        foreach($hatLevelRank as $hlr){
            if($hlr->id == $child){
                $children['hat']= $this->getHatName($hlr->hat_id);
                $children['id'] = $hlr->id;
                $children['reportsTo'] = $this->getParent($child, $hatParentChild);
                $children['level'] = $this->getLevel($hlr->hat_level_id);
                $children['wearer'] = $this->getPersonnel($personnel, $hlr->id);
            }
        }
        return $children;
    }
    private function setParentHatWerer($child, $hatLevelRank, $personnel){
        $children = array();
        foreach($hatLevelRank as $hlr){
            if($hlr->id == $child){
                $children = $this->getPersonnel($personnel, $hlr->id);
            }
        }
        return $children;
    }

    private function setSubHat($parent, $hatParentChild, $personnel, $hatLevelRank){
        $children = array();
        foreach($hatParentChild as $hpc){
            $newChild = array();
            if($hpc->hat_lr_parent == $parent){
                if($this->hasChildren($hpc->hat_lr_child, $hatParentChild)){
                    $parentObj = $this->hatLevelRankRepository->find($hpc->hat_lr_child);
                    $newChild['hat'] = $this->getHatName($parentObj->hat_id);
                    $newChild['level'] = $this->getLevel($parentObj->hat_level_id);
                    $newChild['reportsTo'] = $this->getParent($hpc->hat_lr_child, $hatParentChild);
                    $newChild['wearer'] =$this->setParentHatWerer($hpc->hat_lr_child,$hatLevelRank, $personnel);
                    $newChild['id'] = $hpc->hat_lr_child;
                   $newChild['children']= $this->setSubHat($parentObj->id, $this->unsetCollection($hatParentChild, $hpc->id), $personnel, $hatLevelRank);

                }else{
                $newChild = $this->setHatWerer($hpc->hat_lr_child, $hatLevelRank, $personnel, $hatParentChild);
                }
                array_push($children,$newChild);
            }
        }
        return $children;
       
    }

    private function unsetCollection($collection, $key){
        $selected = array();
        foreach($collection as $c => $cValue){
            if($cValue->id != $key){
                $selected[$c] = $cValue;
            }
        }
        return $selected;
    }

    private function setSubHatTable($parent, $hatParentChild, $personnel, $hatLevelRank){
        $children = array();
        foreach($hatParentChild as $hpc){
            $newChild = array();
            if($hpc->hat_lr_parent == $parent){
                if($this->hasChildren($hpc->hat_lr_child, $hatParentChild)){
                    $parentObj = HatParentChild::where('hat_lr_parent', $hpc->hat_lr_child)->first();
                    $newChild['hat'] = $parentObj->hat_level_rank_parent->name;
                    $newChild['level'] = $this->getLevel($parentObj->hat_level_rank_parent->hat_level_id);
                    $newChild['wearer'] =$this->setParentHatWerer($parentObj->hat_lr_parent,$hatLevelRank, $personnel);
                }else{
                $newChild = $this->setHatWerer($hpc->hat_lr_child, $hatLevelRank, $personnel, $hatParentChild);
                }
                array_push($children,$newChild);
            }
        }
        return $children;
       
    }
    private function getLevel($id){
                    $hatLevelName = $this->hatLevelRepository->find($id);
                    return $hatLevelName->hat_level_description;
    }

    private function getPersonnel($personnel, $id){
        $person = array();
            $personnelHat = PersonnelHat::where('hat_lr_id', $id)->get();
            foreach($personnelHat as $ph){
              if(array_key_exists($ph->personnel_id, $personnel)){
                $personnel[$ph->personnel_id]['type'] = $ph->type;
                $personnel[$ph->personnel_id]['phid'] = $ph->id;
                $person[] = $personnel[$ph->personnel_id];
              }
            }
            return $person;

    }

    private function getHatName($id){
        $hat = $this->hatRepository->find($id);
        return $hat->hat_name;
    }

    private function createPersonnelToHat($request) {
        try{
            Validator::make($request,$this->personalHatValidationRule())->validate();
            $personnel_id = $request['personnel_id'];
            $personnel = PersonnelDomain::getPersonnel($personnel_id);
            if(!$personnel) {
                return $this->errorResponse("Personnel not found", Response::HTTP_NOT_FOUND);
            }
            $hrl_id = $request['hat_lr_id'];
            $hatLr =  $this->hatLevelRankRepository->find($hrl_id);
            if(!isset($hatLr->id)){
                return $this->errorResponse("Hat level rank does not exist", Response::HTTP_NOT_FOUND);
            }
            $personnelHat = $this->personnelHatRepository->create($request);
            return $personnelHat;
        } catch (Exception $e){
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }
    private function createHatLevelRank($request){
        try{
            $hatLevelRank = $request;
            $hatObj = $this->hatRepository->find($hatLevelRank['hat']);
            if(!isset($hatObj->id)){
                return $this->errorResponse( 'Hat not found', Response::HTTP_NOT_FOUND);
            }
            $hatLevelObj = $this->hatLevelRepository->find($hatLevelRank['level']);
            if(!isset($hatLevelObj->id)){
                return $this->errorResponse( 'Hat level not found', Response::HTTP_NOT_FOUND);
            }
            $hatRankObj = $this->hatRankRepository->find($hatLevelRank['rank']);
            if(!isset($hatRankObj->id)){
                return $this->errorResponse( 'Hat rank not found', Response::HTTP_NOT_FOUND);
            }
            $hatLevelRankObj = $this->hatLevelRankRepository->hatLevelRankExits($hatLevelRank['hat'], $hatLevelRank['level'], $hatLevelRank['rank'], $hatLevelRank['title']);
            if(isset($hatLevelRankObj->id)){
                return $this->errorResponse( 'Hat level rank already exists', Response::HTTP_CONFLICT);
            }
           $data['hat_id'] = $request['hat'];
           $data['hat_level_id'] = $hatLevelRank['level'];
           $data['hat_rank_id'] = $hatLevelRank['rank'];
           $data['name'] = $hatLevelRank['title'];
           $hatLevelRankObj = $this->hatLevelRankRepository->create($data);
            return $hatLevelRankObj;
        } catch (Exception $e) {
            return $this->errorResponse( $e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    private function getAllWerers($id, $personnel){
        $personnel= array();
        foreach($personnel as $p){
            if($id == $p->id){
                $personnel[] = $p;
            }
        }
        return $personnel;
    }

    private function createHatPerentChild($hatPerentChild) {
        // add new hat
        try{
             Validator::make($hatPerentChild, $this->hatPerentChildValidationRule())->validate();
             $hatLrP = $this->hatLevelRankRepository->find($hatPerentChild['parent']);
                if(!isset($hatLrP->id)){
                    return $this->errorResponse( 'Parent hat level rank not found', Response::HTTP_NOT_FOUND);
                    }
             $hatLrC = $this->hatLevelRankRepository->find($hatPerentChild['child']); 
                if(!isset($hatLrC->id)){
                    return $this->errorResponse( 'Child hat level rank not found', Response::HTTP_NOT_FOUND);
                    }
                    
                        $hpcP = $this->hatPcrRepository->hatPcrExists($hatPerentChild['parent'], $hatPerentChild['child']);
                if($hpcP){
                    return $this->errorResponse( 'Hat parent child already exists', Response::HTTP_CONFLICT);
                    }
                $hatPcObj['hat_lr_parent'] = $hatPerentChild['parent'];
                $hatPcObj['hat_lr_child'] = $hatPerentChild['child'];
                $hatPerentChild = $this->hatPcrRepository->create($hatPcObj);
                return $hatPerentChild;
        } catch (Exception $e) {
            return $this->errorResponse( $e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    private function setHatDetailsObj($hatDetails) {
            $hatObj['id'] = $hatDetails->id;
            $hatObj['hat'] = $this->hatRepository->find($hatDetails->hat_id);
            $hatObj['level'] = $this->hatLevelRepository->find($hatDetails->hat_level_id);
            $hatObj['rank'] = $this->hatRankRepository->find($hatDetails->hat_rank_id);
            $hatObj['name'] = $hatDetails->name;
            return $hatObj;
    }

    private function setPersonnel(){
        $personnel = PersonnelDomain::getAllPersonnel();
        $ids = array_column($personnel, 'id');
        return array_combine($ids,$personnel);
    }

    private function addHatLR($hatLrData){
        $hatlr ['hat'] = $hatLrData['hat'];
        $hatlr ['level'] = $hatLrData['level'];
        $hatlr ['rank'] = $hatLrData['rank'];
        $hatlr ['title'] = $hatLrData['title'];
        return $hatLevelRank = $this->createHatLevelRank($hatlr);
}
private function updateHatLR($request, $hatLrData){
    $hat['id'] = $hatLrData->id;
    $hat['hat_id'] = $hatLrData->hat_id;
    $hat['hat_level_id'] = $request['level'];
    $hat['hat_rank_id'] = $request['rank'];
    $hat['name'] = $request['title'];
    $update =$this->hatLevelRankRepository->update($request['id'], $hat);

}

private function setHatPc($parentId, $childId){
    try{
    $parentChild['parent'] = $parentId;
    $parentChild['child'] = $childId;
    $hatPc =$this->createHatPerentChild($parentChild);
    return isset($hatPc->id) ? true : false;
    } catch (Exception $e) {
        return $this->errorResponse( $e->getMessage(), Response::HTTP_BAD_REQUEST);
    }
}

private function udateHatPc($parentId, $childId){
    try{
    $parentChild['parent'] = $parentId;
    $parentChild['child'] = $childId;
    $hatPc =$this->createHatPerentChild($parentChild);
    return isset($hatPc->id) ? true : false;
    } catch (Exception $e) {
        return $this->errorResponse( $e->getMessage(), Response::HTTP_BAD_REQUEST);
    }
}

private function assignHat($personnel, $hatId){
    $counter = 0;
    try{
        foreach($personnel as $person){
            $hatPerson = $this->personnelHatRepository->getByColumn('personnel_id', $person);
            if($this->checkIfHFA($person, $hatId) == 0){
                $hat  = $this->hatLevelRankRepository->find($hatId);
                $level = $this->hatLevelRepository->find($hat->hat_level_id);
                if($level->hat_level == 'Training'){
                    $hatPersonnel['type'] = 'Temporary';
                }else{
                    $hatPersonnel['type'] = !$hatPerson->isEmpty() ? "Secondary" : "Primary";
                }
            }else{
                $hatPersonnel['type'] = "HFA";
            }
            $hatPersonnel['personnel_id'] = $person;
            $hatPersonnel['hat_lr_id'] = $hatId;
            $hA = $this->createPersonnelToHat($hatPersonnel);
            $counter += isset($hA->id) ? 1 : 0;
        }
        return $counter;
    } catch (Exception $e) {
        return $this->errorResponse( $e->getMessage(), Response::HTTP_BAD_REQUEST);
    }
}

private function checkIfHFA($personnel, $hatId){
    try{
        $counter = 0;
        $hatRel = $this->hatPcrRepository->getByColumn('hat_lr_child', $hatId)->first();
        $parentHat = $this->personnelHatRepository->personnelHatExits($personnel,$hatRel->hat_lr_parent);
        if(!empty($parentHat->id)){
            $counter = 1;
        }else{
            $counter = 0;
        }
        return $counter;
    }
    catch (Exception $e) {
        return $this->errorResponse( $e->getMessage(), Response::HTTP_BAD_REQUEST);
    }
}


private function assignHatToParent($parentId, $hatId){
    $hatPersonnel = $this->personnelHatRepository->getByColumn('hat_lr_id',$parentId);
    $hatPA['type'] = "HFA";
    $hatPA['personnel_id'] = $hatPersonnel[0]->personnel_id;
    $hatPA['hat_lr_id'] = $hatId;
    $this->createPersonnelToHat($hatPA);
}
private function updateAssignment($personnel, $hatId){
   
    try{
        $counter = 0;
        $hA  = null;
        foreach($personnel as $person){
            $test = $this->personnelHatRepository->personnelHatExits($person, $hatId);
            if(!isset($test->id)){
                $hatPerson = $this->personnelHatRepository->getByColumn('personnel_id', $person);
                $hatPersonnel['type'] = !$hatPerson->isEmpty() ? "Secondary" : "Primary";
                $hatPersonnel['personnel_id'] = $person;
                $hatPersonnel['hat_lr_id'] = $hatId;
                $hA = $this->createPersonnelToHat($hatPersonnel);
            }
            $counter += isset($hA->id) ? 1 : 0;
        }
        return $counter;
    } catch (Exception $e) {
        return $this->errorResponse( $e->getMessage(), Response::HTTP_BAD_REQUEST);
    }
}
  

    private function hatPerentChildValidationRule() {
        // validate hat level
        return [
            'parent' => 'required|integer',
            'child' => 'required|integer',
        ];
    }
    private function hatLevelRankValidationRule() {
        // validate hat level
        return [
            'hat' => 'required|integer',
            'level' => 'required|integer',
            'rank' => 'required|integer',
        ];
    }
    private function personalHatValidationRule () {
        return [
            'personnel_id' => 'required',
            'hat_lr_id' => 'required'
        ];
    }

    private function completeHatValidationRule() {
        return [
            'parent' => 'required',
            'title' => 'required',
            'level' => 'required',
            'rank' => 'required',
        ];
    }
}