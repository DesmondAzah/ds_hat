<?php 

namespace App\Services;

use App\Imports\HatsImport;
use App\Models\Hat;
use App\Repository\HatLevelRepository;
use App\Repository\HatRankRepository;
use App\Repository\HatRepository;
use App\Repository\HatLevelRankRepository;
use App\Traits\ApiResponseHelper;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;


class HatService extends Service {
    private HatRepository $hatRepository;
    private HatLevelRepository $hatLevelRepository;
    private HatRankRepository $hatRankRepository;
    private HatLevelRankRepository $hatLevelRankRepository;
    use ApiResponseHelper;
    public function __construct() {
        $this->hatRepository = app()->make(HatRepository::class);
        $this->hatLevelRepository = app()->make(HatLevelRepository::class);
        $this->hatRankRepository = app()->make(HatRankRepository::class);
        $this->hatLevelRankRepository = app()->make(HatLevelRankRepository::class);
        parent::__construct();
    }
    
    public function getHats() {
        // get all hat levels
        try{
            $hats = $this->hatRepository->findAllActive();
            return $this->successResponse( $hats , 'Hats', Response::HTTP_OK);
        }  catch (Exception $e) {
            return $this->errorResponse( $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function getHat($id) {
        // get hat level by id
        try{
            $hat = $this->hatRepository->find($id);
            if(!$hat) {
                return $this->errorResponse('Hat not found', Response::HTTP_NOT_FOUND);
            }
            return $this->successResponse( $hat , 'Hat', Response::HTTP_OK);
        }  catch (Exception $e) {
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

    private function setHatDetailsObj($hatDetails) {
            $hatObj['id'] = $hatDetails->id;
            $hatObj['hat'] = $this->hatRepository->find($hatDetails->hat_id);
            $hatObj['level'] = $this->hatLevelRepository->find($hatDetails->hat_level_id);
            $hatObj['rank'] = $this->hatRankRepository->find($hatDetails->hat_rank_id);
            $hatObj['name'] = $hatDetails->name;
            return $hatObj;
    }

    private function hatValidationRule() {
        // validate hat level
        return [
            'hat_name' => 'required|string|max:255',
            'hat_description' => 'required|string|max:255',
        ];
    }



}