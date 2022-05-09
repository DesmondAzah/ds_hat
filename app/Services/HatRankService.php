<?php 

namespace App\Services;

use App\Models\HatRanks;
use App\Traits\ApiResponseHelper;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class HatRankService extends Service {

        use ApiResponseHelper;
        public function __construct() {
            // setup debug info
        }
        
        public function getHatRanks() {
            // get all hat ranks
            try{
                $hatRanks = HatRanks::all();
                if(!$hatRanks) {
                    return $this->errorResponse('Hat levels not found', Response::HTTP_NOT_FOUND);
                }
                return $this->successResponse( $hatRanks , 'Hats ranks', Response::HTTP_OK);
            } catch (Exception $e) {
                return $this->errorResponse($e->getMessage(),Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }
        
        public function getHatRank($id) {
            // get a hat rank by id
            try{
                $hatRank = HatRanks::find($id);
                if(!$hatRank) {
                    return $this->errorResponse('Hat rank not found', Response::HTTP_NOT_FOUND);
                }
                return $this->successResponse( $hatRank , 'Hat rank', Response::HTTP_OK);
            } catch (Exception $e) {
                return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }
        
        public function addHatRank($request) {
            // add a new hat rank
            try{
                $hatRank = $request;
                Validator::make($hatRank, $this->hatRankValidationRule())->validate();
                $hatRankObj = HatRanks::where('hat_rank', $hatRank['hat_rank'])->first();
                if($hatRankObj){
                    return $this->errorResponse( 'Hat rank already exists', Response::HTTP_CONFLICT);
                }
                $hatRank = HatRanks::create($hatRank);
                $hatRank->save();
                return $this->successResponse($hatRank,'Hat rank successfully added', Response::HTTP_CREATED);
            } catch (Exception $e) {
                return $this->errorResponse( $e->getMessage(), Response::HTTP_BAD_REQUEST);
            }
        }
        
        public function updateHatRank($request, $id) {
            // update a hat rank
            try{
                $hatRank = $request;
                $hatRankObj = HatRanks::find($id);
                if(!$hatRankObj) {
                    return $this->errorResponse('Hat rank not found', Response::HTTP_NOT_FOUND);
                }
                $hatRankObj->fill($hatRank);
                if($hatRankObj->isClean()) {
                    return $this->errorResponse( 'No changes to update', Response::HTTP_BAD_REQUEST);
                }
                $hatRankObj->dt_updated = date('Y-m-d H:i:s');
                $hatRankObj->update($hatRank);
                return $this->successResponse($hatRankObj,'Hat rank successfully updated', Response::HTTP_OK);
            } catch (Exception $e) {
                return $this->errorResponse( $e->getMessage(), Response::HTTP_BAD_REQUEST);
            }
        }
        
        public function deleteHatRank($id) {
            // delete a hat rank
            try{
                $hatRank = HatRanks::find($id);
                if(!$hatRank) {
                    return $this->errorResponse('Hat rank not found', Response::HTTP_NOT_FOUND);
                }
                $hatRank->dt_updated = date('Y-m-d H:i:s');
                $hatRank->hat_rank_status = false;
                $hatRank->update();
                return $this->successResponse( 'Hat rank successfully deleted', Response::HTTP_OK);
            } catch (Exception $e) {
                return $this->errorResponse( $e->getMessage(), Response::HTTP_BAD_REQUEST);
            }
        }

        private function hatRankValidationRule() {
            return [
                'hat_rank' => 'required|string|max:255',
                'hat_rank_description' => 'required|string|max:255',
            ];
        }
        
}
