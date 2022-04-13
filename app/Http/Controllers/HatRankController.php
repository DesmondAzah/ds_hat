<?php 
 
 namespace App\Http\Controllers;

use App\Services\HatRankService;
use Illuminate\Http\Client\Request;

 class HatRankController extends Controller {

     public function __construct() {
     }

        /**
        * PROCESS a request to get all hat ranks
        * @param HatRankService $hatRankService
        * @return Illuminate\Http\Response
        */
     public function getHatRanks(HatRankService $hatRankService){
         return $hatRankService->getHatRanks();
     }

        /**
        * PROCESS a request to get a hat rank
        * @param HatRankService $hatRankService
        * @param Request $request
        * @param $id
        * @return Illuminate\Http\Response
        */

     public function getHatRank($id, HatRankService $hatRankService) {
       return $hatRankService->getHatRank($id);
     }

        /**
        * PROCESS a request to add a new hat rank
        * @param HatRankService $hatRankService
        * @param Request $request
        * @return Illuminate\Http\Response
        */
     public function addHatRank( HatRankService $hatsRankService) {
         return $hatsRankService->addHatRank(request()->all());
     }

        /**
        * PROCESS a request to update an existing hat rank
        * @param HatRankService $hatRankService
        * @param Request $request
        * @param $id
        * @return Illuminate\Http\Response
        */
     public function updateHatRank(HatRankService $hatRankService, $id) {
         return $hatRankService->updateHatRank(request()->all(), $id);
     }

        /**
        * PROCESS a request to delete an existing hat rank
        * @param HatRankService $hatRankService
        * @param $id
        * @return Illuminate\Http\Response
        */
     public function deleteHatRank($id, HatRankService $hatRankService) {
         return $hatRankService->deleteHatRank($id);
     }

 }