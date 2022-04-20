<?php 

namespace App\Services;

use App\Domains\PersonnelDomain;
use App\Models\Hat;
use App\Models\HatLevel;
use App\Models\HatLevelRank;
use App\Models\HatParentChild;
use App\Models\HatRanks;
use App\Models\PersonnelHat;
use App\Repository\HatLevelRepository;
use App\Repository\HatRankRepository;
use App\Repository\HatRepository;
use App\Repository\HatLevelRankRepository;
use App\Repository\HatPcrRepository;
use App\Repository\PersonnelHatRepository;
use App\Traits\ApiResponseHelper;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class HatService extends Service {
    private HatRepository $hatRepository;
    private HatLevelRepository $hatLevelRepository;
    private HatRankRepository $hatRankRepository;
    private HatLevelRankRepository $hatLevelRankRepository;
    private HatPcrRepository $hatPcrRepository;
    private PersonnelHatRepository $personnelHatRepository;
    use ApiResponseHelper;
    public function __construct() {
        $this->hatRepository = app()->make(HatRepository::class);
        $this->hatLevelRepository = app()->make(HatLevelRepository::class);
        $this->hatRankRepository = app()->make(HatRankRepository::class);
        $this->hatLevelRankRepository = app()->make(HatLevelRankRepository::class);
        $this->hatPcrRepository = app()->make(HatPcrRepository::class);
        $this->personnelHatRepository = app()->make(PersonnelHatRepository::class);
        parent::__construct();
    }
    
    public function getHats() {
        // get all hat levels
        try{
            $hats = $this->hatRepository->findAllActive();
            return $this->successResponse( $hats , 'Hats', Response::HTTP_OK);
        }  catch (Exception $e) {
            return $this->errorResponse(Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }
    
    public function getHat($id) {
        // get hat level by id
        try{
            $hat = $this->hatRepository->find($id);
            if(!$hat) {
                return $this->errorResponse(Response::HTTP_NOT_FOUND, 'Hat not found');
            }
            return $this->successResponse( $hat , 'Hat', Response::HTTP_OK);
        }  catch (Exception $e) {
            return $this->errorResponse(Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }
    
    public function addHat($hat) {
        // add new hat
        try{
             Validator::make($hat, $this->hatValidationRule())->validate();
             $hatObj = $this->hatRepository->getByColumn('hat_name', $hat['hat_name']);
             if($hatObj){
                 return $this->errorResponse( 'Hat name already exists', Response::HTTP_CONFLICT);
                }
            $hat = $this->hatRepository->create($hat);
            return $this->successResponse($hat,'Hat successfully added', Response::HTTP_CREATED);
        } catch (Exception $e) {
            return $this->errorResponse( $e->getMessage(), Response::HTTP_BAD_REQUEST);
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
                    error_log(print_r($hasParent, true));exit;
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
            error_log("error: ".print_r($e->getMessage(), true));
            return $this->errorResponse(Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }
    
    public function updateHat($request, $id) {
        // update hat level
        try{
            $hatObj = $this->hatRepository->find($id);
            if(!$hatObj){
                return $this->errorResponse('Hat not found', Response::HTTP_NOT_FOUND);
            }
            $hatObj->fill($request);
            if($hatObj->isClean()){
                return $this->errorResponse('Nothing to update', Response::HTTP_BAD_REQUEST);
            }
            $request['dt_updated'] = date('Y-m-d H:i:s');
            $hat = $this->hatRepository->update($id,$request);
            return $this->successResponse($hat,'Hat successfully updated', Response::HTTP_OK);
        } catch (Exception $e) {
            return $this->errorResponse( $e->getMessage(), Response::HTTP_BAD_REQUEST);
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
            $hatlr ['hat'] = $request['hat'];
            $hatlr ['level'] = $request['level'];
            $hatlr ['rank'] = $request['rank'];
            $hatlr ['title'] = $request['title'];
            $hatLevelRank = $this->createHatLevelRank($hatlr);
            if(!isset($hatLevelRank->id)){
                return $this->errorResponse("Hat level rank not created", Response::HTTP_NOT_FOUND);
            }
            $parentChild['parent'] = $request['parent'];
            $parentChild['child'] = $hatLevelRank->id;
            $hatPerson = $this->personnelHatRepository->getByColumn('personnel_id', $request['personnel']);
            $hatPersonnel['personnel_id'] = $request['personnel'];
            $hatPersonnel['hat_lr_id'] = $hatLevelRank->id;
            $hatPerson = $this->personnelHatRepository->getByColumn('personnel_id', $request['personnel']);
            if(!$hatPerson->isEmpty()){
                $hatPersonnel['type'] = "Secondary";
            }else{
                $hatPersonnel['type'] = "Primary";
            }
            $this->createPersonnelToHat($hatPersonnel);
            $this->createHatPerentChild($parentChild);
            return $this->successResponse($hatLevelRank, "Complete hat created successfully", Response::HTTP_OK);
        } catch (Exception $e){
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
                $hatChart['hat'] = $hatLevel->name;
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
            //error_log("error: ".print_r($e->getMessage(), true));
            return $this->errorResponse(Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }
    public function hattingChart(){
        try{
            $hatChart = array();
            $hatLevelRank = HatLevelRank::all();
            $hatParentChild = HatParentChild::all();
            $parentArray = $this->getUniqueParent($hatParentChild);
            $newPersonnel = $this->setPersonnel();
            foreach($parentArray as $parent){
                $hasParent = $this->hasPerent($parent, $hatParentChild);
                if($hasParent == false){
                    $parentObj = HatParentChild::where('hat_lr_parent', $parent)->first();
                    $hatChart['hat'] = $parentObj->hat_level_rank_parent->name;
                    $hatChart['level'] = $this->getLevel($parentObj->hat_level_rank_parent->hat_level_id);
                    $hatChart['wearer'] =$this->setParentHatWerer($parentObj->hat_lr_parent,$hatLevelRank, $newPersonnel);
                    $hatChart['reportsTo'] = 'N/A';
                    $hatChart['id'] = $parentObj->hat_level_rank_parent->hat_level_id;
                    $hatChart['children']= $this->setSubHat($parent, $hatParentChild, $newPersonnel, $hatLevelRank);
                    // $hatChart[$parentObj->hat_level_rank_parent->name]['id'] = $parentObj->id;
                }else{
                    $parentObj = HatParentChild::where('hat_lr_parent', $parent)->first();
                    //$hatChart[$parentObj->hat_level_rank_parent->name]['wearers'] =$this->setHatWerer($parentObj->hat_lr_parent,$hatLevelRank, $newPersonnel);
                    // $hatChart[$parentObj->hat_level_rank_parent->name]['children'][]= $this->setSubHat($parent, $hatParentChild, $newPersonnel, $hatLevelRank);
                                    }
            }
            
            if(empty($hatChart)){
                return $this->errorResponse( 'No hat relationship found', Response::HTTP_NOT_FOUND);
            }
            return $this->successResponse( $hatChart , 'Hating Chart ', Response::HTTP_OK);
        }  catch (Exception $e) {
            error_log("error: ".print_r($e->getMessage(), true));
            // return $this->errorResponse(Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }
    
    public function deleteHat($id) {
        // delete hat level
        try{
            $hatObj = Hat::find($id);
            if(!$hatObj){
                return $this->errorResponse('Hat not found', Response::HTTP_NOT_FOUND);
            }
            $hatObj->hat_status = false;
            $hatObj->update();
            return $this->successResponse($hatObj,'Hat successfully deleted', Response::HTTP_OK);
        } catch (Exception $e) {
            return $this->errorResponse( $e->getMessage(), Response::HTTP_BAD_REQUEST);
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
                return $hat->name;
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
                $children['hat']= $hlr->name;
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
                    $parentObj = HatParentChild::where('hat_lr_parent', $hpc->hat_lr_child)->first();
                    $newChild['hat'] = $parentObj->hat_level_rank_parent->name;
                    $newChild['level'] = $this->getLevel($parentObj->hat_level_rank_parent->hat_level_id);
                    $newChild['reportsTo'] = $this->getParent($hpc->hat_lr_child, $hatParentChild);
                    $newChild['wearer'] =$this->setParentHatWerer($hpc->hat_lr_child,$hatLevelRank, $personnel);
                    $newChild['id'] = $hpc->hat_lr_child;
                    $newChild["children"] = $this->setSubHat($hpc->hat_lr_child, $hatParentChild, $personnel, $hatLevelRank);
                }else{
                $newChild = $this->setHatWerer($hpc->hat_lr_child, $hatLevelRank, $personnel, $hatParentChild);
                // $children[$hpc->hat_lr_child]['id'] = $hpc->id;
                // $children[$hpc->hat_lr_child]['owner'] =$this->setChildren($hpc->hat_lr_parent,$hatLevelRank, $personnel);
                }
                array_push($children,$newChild);
            }
        }
        return $children;
       
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
                // $children[$hpc->hat_lr_child]['id'] = $hpc->id;
                // $children[$hpc->hat_lr_child]['owner'] =$this->setChildren($hpc->hat_lr_parent,$hatLevelRank, $personnel);
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
                $person[] = $personnel[$ph->personnel_id];
              }
            }
            return $person;

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

             $hpcP = $this->hatLevelRankRepository->getByColumn('hat_lr_parent', $hatPerentChild['parent'])->getByColumn('hat_lr_child', $hatPerentChild['child']);
                if(isset($hpcP->id)){
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

    private function setPersonnel(){
        $personnel = PersonnelDomain::getAllPersonnel();
        $ids = array_column($personnel, 'id');
        return array_combine($ids,$personnel);
    }
    private function hatValidationRule() {
        // validate hat level
        return [
            'hat_name' => 'required|string|max:255',
            'hat_description' => 'required|string|max:255',
        ];
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
            'hat' => 'required',
            'level' => 'required',
            'rank' => 'required',
        ];
    }
}