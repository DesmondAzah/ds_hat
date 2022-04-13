<?php 

namespace App\Services;

use App\Models\Hat;
use App\Models\HatParentChild;
use App\Models\HatLevel;
use App\Traits\ApiResponseHelper;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
class HatLevelService extends Service {

    use ApiResponseHelper;
    public function __construct() {
        // setup debug info
    }
    
    public function getHatLevels() {
        // get all hat levels
        try{
            $hats = HatLevel::all();
            if(!$hats) {
                return $this->errorResponse(Response::HTTP_NOT_FOUND, 'Hat levels not found');
            }
            return $this->successResponse( $hats , 'Hats levels', Response::HTTP_OK);
        }  catch (Exception $e) {
            return $this->errorResponse(Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }
    
    public function getHatLevel($id) {
        // get a hat level by id
        try{
            $hatLevel = HatLevel::find($id);
            if(!$hatLevel) {
                return $this->errorResponse(Response::HTTP_NOT_FOUND, 'Hat level not found');
            }
            return $this->successResponse( $hatLevel , 'Hat level', Response::HTTP_OK);
        }  catch (Exception $e) {
            return $this->errorResponse(Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }
    
    public function addHatLevel( $request) {
        // add a new hat level
        try{
            $hatLevel = $request;
            Validator::make($hatLevel, $this->hatLevelValidationRule())->validate();
            $hatLevelObj = HatLevel::where('hat_level', $hatLevel['hat_level'])->first();
            if($hatLevelObj){
                return $this->errorResponse( 'Hat level already exists', Response::HTTP_CONFLICT);
            }
            $hatLevel = HatLevel::create($hatLevel);
            $hatLevel->save();
            return $this->successResponse($hatLevel,'Hat level successfully added', Response::HTTP_CREATED);
        } catch (Exception $e) {
            return $this->errorResponse( $e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }
    
    public function updateHatLevel($request, $id) {
        // update a hat level
        try{
            $hatLevel = $request;
            $hatLevelObj = HatLevel::find($id);
            if(!$hatLevelObj) {
                return $this->errorResponse(Response::HTTP_NOT_FOUND, 'Hat level not found');
            }
            $hatLevelObj->fill($hatLevel);
            if($hatLevelObj->isClean()) {
                return $this->errorResponse( 'Nothing to update', Response::HTTP_BAD_REQUEST);
            }
            $hatLevelObj->dt_updated = date('Y-m-d H:i:s');
            $hatLevelObj->update($hatLevel);
            return $this->successResponse($hatLevel,'Hat level successfully updated', Response::HTTP_OK);
        } catch (Exception $e) {
            return $this->errorResponse( $e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }
    
    public function deleteHatLevel($id) {
        // delete a hat level
        try{
            $hatLevel = HatLevel::find($id);
            if(!$hatLevel) {
                return $this->errorResponse(Response::HTTP_NOT_FOUND, 'Hat level not found');
            }
            $hatLevel->hat_level_status = false;
            $hatLevel->dt_updated = date('Y-m-d H:i:s');
            $hatLevel->update();
            return $this->successResponse( 'Hat level successfully deleted', Response::HTTP_OK);
        } catch (Exception $e) {
            return $this->errorResponse( $e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }
    private function hatLevelValidationRule() {
        // validate hat level
        return [
            'hat_level' => 'required|string|max:255',
            'hat_level_description' => 'required|string|max:255',
        ];
    }
}