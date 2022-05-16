<?php

namespace App\Services;

use App\Domains\PersonnelDomain;
use App\Traits\ApiResponseHelper;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use App\Helpers\ValidationHelper;

use App\Repository\HatLevelRepository;
use App\Repository\HatRankRepository;
use App\Repository\HatRepository;
use App\Repository\HatLevelRankRepository;
use App\Repository\HatPcrRepository;
use App\Repository\PersonnelHatRepository;
use App\Repository\PersonnelHatHistoryRepository;
use stdClass;

Class HatSetupService extends Service {

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
        // setup debug info
    }


    public function setupHat($request){
        try{
            Validator::make($request, ValidationHelper::completeHatValidationRule())->validate();
            $hat = $this->setupHatTitle($request->hat_id, $request->unique);
            $hatLevelRank = $this->setupHatLevelRank($hat->id, $request->hat_level, $request->hat_rank);
            if($request->unique && sizeof($request->personnel) > 1){
               return $this->errorResponse('Unique hat cannot be assigned to multiple personnel', Response::HTTP_BAD_REQUEST);
            }
            $this->setupHatPsa($hatLevelRank->id, $request->personnel);
            $this->setupHatPcr($hatLevelRank->id, $request->parentId);
            
            return $this->successResponse( $hatLevelRank , 'Hat created successfully', Response::HTTP_OK);
        }catch(Exception $e){
            return $this->errorResponse( $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function updateHatSetup($id, $request){
        try{
            Validator::make($request, ValidationHelper::completeHatValidationRule())->validate();
            $hatLevelRank = $this->hatLevelRankRepository->find($id);
            if(empty($hatLevelRank->id)){
                return $this->errorResponse('Hat level rank not found', Response::HTTP_NOT_FOUND);
            }
            $newHatLevelRank = $this->hatLevelRankSetupUpdate($hatLevelRank, $request);

            $this->setupHatPsa($newHatLevelRank->id, $request->personnel);
            $this->setupHatPcr($newHatLevelRank->id, $request->parentId);
            

            return $this->successResponse( $newHatLevelRank , 'Hat updated successfully', Response::HTTP_OK);
        }catch(Exception $e){
            return $this->errorResponse( $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
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

    public function deletePersonnelHats($id){
        try{
            $hatPerson = $this->personnelHatRepository->find($id);
            $hatPcr = $this->hatPcrRepository->getByColumn('hat_lr_parent', $hatPerson->id);
            if(isset($hatPcr->id)){
                return $this->errorResponse("Person cannot be deleted as it is a parent to a hat", Response::HTTP_BAD_REQUEST);
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

    public function getHatTable() {
        try{
            $hatTable = array();
            $hatLevelRank = $this->hatLevelRankRepository->findAll(); 
            $newPersonnel = $this->setPersonnel();
            foreach($hatLevelRank as $hlr){
                $hatPcr = $this->hatPcrRepository->hasParent($hlr->id);
                $obj = new stdClass();
                $obj->id = $hlr->id;
                $obj->hat = $this->hatRepository->find($hlr->hat_id)->hat_name;
                $obj->level = $this->hatLevelRepository->find($hlr->hat_level_id)->hat_level_description;
                $obj->wearer =$this->setParentHatWerer($hlr->id, $newPersonnel);
                $obj->reportsTo = empty($hatPcr->hat_lr_parent) || $hatPcr->hat_lr_parent == 0? 'N/A':$this->getParent($hatPcr, $newPersonnel) ;
                $hatTable[] = $obj;
            }
            
            if(empty($hatTable)){
                return $this->errorResponse( 'No hat relationship found', Response::HTTP_NOT_FOUND);
            }
            return $this->successResponse( $hatTable , 'Hating Chart ', Response::HTTP_OK);
        }  catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getHatChart() {
        try{
            $hatChart = new StdClass();
            $obj = new stdClass();
            $newPersonnel = $this->setPersonnel();
            $hat = $this->hatRepository->getByColumn('hat_name', 'Board of Directors');
            if(isset($hat[0]->id)){
                $hatLevelRank = $this->hatLevelRankRepository->getByColumn('hat_id',$hat[0]->id);
                if(isset($hatLevelRank[0]->id)){
                    $hatChart->id = $hatLevelRank[0]->id;
                    $hatChart->hat = "Board of Directors";
                    $obj->id = "N/A";
                    $obj->name = "N/A";
                    $obj->img = "N/A";
                    $hatChart->person = $obj;
                    $hatChart->children = $this->getChildren(0, $newPersonnel); 
                }
            }
            return $this->successResponse( $hatChart , 'Hating Chart ', Response::HTTP_OK);
        }  catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function switchHatParent(){
        $hatParent = $this->hatPcrRepository->findAll();
        foreach($hatParent as $hat){
            $personHat = $this->personnelHatRepository->getByColumn('hat_lr_id',$hat->hat_lr_parent);
            if(isset($personHat[0]->id)){
                $this->hatPcrRepository->update($hat->id, ['hat_lr_parent' => $personHat[0]->id]);
            }
        }
    }
    
    private function setupHatTitle($hatTitle, $uniqueValue){
        try{
            $hat = $this->hatRepository->getByColumn('hat_name',$hatTitle);
            if(!$hat){
                $hat = $this->hatRepository->create([
                    'hat_name' => $hatTitle,
                    'hat_description' => $hatTitle,
                    'hat_status' => 1,
                    'unique' => $uniqueValue
                ]);
            }
            return $hat;
        }catch(Exception $e){
            return $this->errorResponse( $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function setupHatLevelRank($hatId, $levelId, $rankId){
        try{
            $hatLevelRank = $this->hatLevelRankRepository->getByColumns(['hat_id', 'hat_level_id', 'hat_rank_id'], [$hatId, $levelId, $rankId]);
            if(empty($hatLevelRank->id)){
                $hatLevelRank = $this->hatLevelRankRepository->create([
                    'hat_id' => $hatId,
                    'hat_level_id' => $levelId,
                    'hat_rank_id' => $rankId
                ]);
            }
            return $hatLevelRank;
            }catch(Exception $e){
                return $this->errorResponse( $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
            }
    }

    private function setupHatPsa($hatLevelRankId, $personnelIds){
        try{
            $hatPsaIdsArray = [];
            foreach($personnelIds as $personnelId){
                $hatPsaObj = $this->personnelHatRepository->personnelHatExits($personnelId->personnel, $hatLevelRankId);
                if(empty($hatPsaObj->id)){
                    $hatPsaIdsArray[] = $this->personnelHatRepository->create([
                        'personnel_id' => $personnelId->personnel,
                        'hat_level_rank_id' => $hatLevelRankId,
                        'type' => $personnelId->type
                    ]);
                }else{
                    if($hatPsaObj->type != $personnelId->type){
                        $hatPsaObj->type = $personnelId->type;
                        $hatPsaObj->save();
                    }
                }
                $hatPsaIdsArray[] = $hatPsaObj;
            }
            return $hatPsaIdsArray;
        }catch(Exception $e){
            return $this->errorResponse( $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function setupHatPcr($hatLevelRankId, $hatPersonId){
        try{
            $hatPcr = $this->hatPcrRepository->getByColumn('hat_lr_child', $hatLevelRankId);
            if(empty($hatPcr->id)){
                $hatPcr = $this->hatPcrRepository->create([
                    'hat_lr_child' => $hatLevelRankId,
                    'hat_lr_parent' => $hatPersonId
                ]);
            }else{
                if($hatPcr->hat_lr_parent != $hatPersonId){
                    $hatPcr->hat_lr_parent = $hatPersonId;
                    $hatPcr->save();
                }
            }
            return $hatPcr;
        }catch(Exception $e){
            return $this->errorResponse( $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

    }

    private function hatLevelRankSetupUpdate($hatLevelRank, $request){
        try{
            $hlr['id'] = $hatLevelRank->id;
            $hlr['hat_level_id'] = $request->hat_level;
            $hlr['hat_rank_id'] = $request->hat_rank;
            $hrl['hat_id'] = $hatLevelRank->hat_id;
            $newHatLevelRank = $this->hatLevelRankRepository->getByColumns(['hat_id', 'hat_level_id', 'hat_rank_id'], [$request->hat_id, $request->level_id, $request->rank_id]);
            if(!empty($newHatLevelRank->id)){
                $newHatLevelRank = $this->hatLevelRankRepository->update($hatLevelRank->id,$hrl);
            }
            return $newHatLevelRank;
        }catch(Exception $e){
            return $this->errorResponse( $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function setHatDetailsObj($hatDetails) {
        $hatObj['id'] = $hatDetails->id;
        $hatObj['hat'] = $this->hatRepository->find($hatDetails->hat_id);
        $hatObj['level'] = $this->hatLevelRepository->find($hatDetails->hat_level_id);
        $hatObj['rank'] = $this->hatRankRepository->find($hatDetails->hat_rank_id);
        return $hatObj;
    }

    private function setPersonnel(){
        $personnel = PersonnelDomain::getAllPersonnel();
        $ids = array_column($personnel, 'id');
        return array_combine($ids,$personnel);
    }
    private function setParentHatWerer($hlrId, $personnel){
        $children = array();
        $wearers = $this->personnelHatRepository->getByColumn('hat_lr_id', $hlrId);
        foreach($wearers as $wearer){
            $children[] = $this->getPersonnel($wearer->personnel_id, $personnel);
        }
        return $children;
    }
    private function getParent($hPcr, $personnel){
        $parent = $this->personnelHatRepository->find($hPcr->hat_lr_parent);
        $hLr = $this->hatLevelRankRepository->find($parent->hat_lr_id);
        $obj = new \stdClass();
        $obj->hat = $this->hatRepository->find($hLr->hat_id)->hat_name;
        $obj->wearer= $this->getPersonnel($parent->personnel_id, $personnel);
        return $obj;
    }
    private function getPersonnel($personnelId, $personnel){
        foreach($personnel as $person){
            if($person['id'] == $personnelId){
                $obj = new stdClass();
                $obj->id = $person['id'];
                $obj->name = $person['full_name'];
                $obj->img = $person['picture_url'];
                return $obj;
            }
        }
    }
    private function getChildren($parentId,$personnel){
        $childrenArray = array();
        $children = $this->hatPcrRepository->getByColumn('hat_lr_parent', $parentId);
        foreach($children as $child){
            $hlr = $this->hatLevelRankRepository->find($child->hat_lr_child);
            $hatPersons = $this->personnelHatRepository->getByColumn('hat_lr_id', $hlr->id);
            foreach($hatPersons as $hatPerson){
                $obj = new stdClass();

                  error_log("this is the parent id: ".$parentId." this is the child id: ". $hatPerson->id);
                  if($parentId != $hatPerson->id){
                    $obj->id = $hlr->id;
                    $obj->hat = $this->hatRepository->find($hlr->hat_id)->hat_name;
                    $obj->personId = $hatPerson->personnel_id;
                    $personObj = $this->getPersonnel($hatPerson->personnel_id, $personnel);
                    $obj->person =$personObj;
                    $getChildren = $this->hatPcrRepository->getByColumn('hat_lr_parent', $hatPerson->id);
                    if(!empty($getChildren[0]->id)){
                        $obj->children = $this->getChildren($hatPerson->id, $personnel);
                    }
                    array_push($childrenArray, $obj);
                }

            }
        }
        return $childrenArray;
    }

}