<?php

namespace Sleefs\Helpers\Google\SpreadSheets; 

use Sleefs\Helpers\Google\SpreadSheets\GoogleSheetPosRepository;
use Sleefs\Helpers\Google\SpreadSheets\ShipheroExtendedPoToGoogleSheetRecordConverter;


Class ShipheroPoToGoogleSpreadsheetSyncer {

    public function sync (\stdClass $extendedPo) : mixed
    {
        $shipheroPoToGSConverter = new ShipheroExtendedPoToGoogleSheetRecordConverter();
        $gsRepo = new GoogleSheetPosRepository();
        $normalizedForGSPo = $shipheroPoToGSConverter->convert($extendedPo);
        $saveResponse = $gsRepo->save($normalizedForGSPo);
        return $saveResponse;
    }

}