<?php 

namespace App\Http\Controllers;

use App\Services\HatLevelService;
use Illuminate\Http\Client\Request;

class HatLevelController extends Controller {

    public function __construct() {
    }

    /**
     * PROCESS a request to get all hat levels
     * @param HatLevelService $hatLevelService
     * @return Illuminate\Http\Response
     */
    public function getHatLevels( HatLevelService $hatLevelService){
        return $hatLevelService->getHatLevels();
    }

    /**
     * PROCESS a request to get a hat level
     * @param HatLevelService $hatLevelService
     * @param Request $request
     * @param $id
     * @return Illuminate\Http\Response
     */
    public function getHatLevel($id, HatLevelService $hatLevelService){
        return $hatLevelService->getHatLevel($id);
    }

    /**
     * PROCESS a request to add a new hat level
     * @param HatLevelService $hatLevelService
     * @param Request $request
     * @return Illuminate\Http\Response
     */
    public function addHatLevel(HatLevelService $hatLevelService){
        return $hatLevelService->addHatLevel(request()->all());
    }

    /**
     * PROCESS a request to update an existing hat level
     * @param HatLevelService $hatLevelService
     * @param Request $request
     * @param $id
     * @return Illuminate\Http\Response
     */
    public function updateHatLevel(HatLevelService $hatLevelService, $id) {
        return $hatLevelService->updateHatLevel(request()->all(), $id);
    }

    /**
     * PROCESS a request to delete an existing hat level
     * @param HatLevelService $hatLevelService
     * @param $id
     * @return Illuminate\Http\Response
     */
    public function deleteHatLevel($id, HatLevelService $hatLevelService) {
        return $hatLevelService->deleteHatLevel($id);
    }

}