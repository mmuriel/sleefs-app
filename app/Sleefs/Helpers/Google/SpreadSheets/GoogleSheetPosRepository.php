<?php

namespace Sleefs\Helpers\Google\SpreadSheets;


use Sleefs\Helpers\Misc\Interfaces\IRepository;

use Google\Service\Sheets\AppendValuesResponse;
use Google\Service\Sheets as GoogleSheets;
use Google\Service\Sheets\BatchUpdateSpreadsheetResponse;
use Google\Service\Sheets\BatchUpdateValuesResponse;
use Google\Service\Sheets\ClearValuesResponse;

use Revolution\Google\Sheets\Facades\Sheets;
use Sleefs\Helpers\Google\SpreadSheets\GoogleSpreadsheetGetWorkSheetIndex;
use Illuminate\Support\Collection;

use Sleefs\Helpers\Misc\Response;

/**
 * Esta clase tiene como objetivo, establecer un API de manejo de datos
 * con el Google Spreadsheets basado en el patrón Repositorio 
 * (https://medium.com/@pererikbergman/repository-design-pattern-e28c0f3e4a30) 
 * 
 */ 

class GoogleSheetPosRepository implements \Sleefs\Helpers\Misc\Interfaces\IRepository{

	private $spreadSheet, $sheet;

	public function __construct()
	{
		if (config('google.spreadsheet_title') == null || config('google.spreadsheet_title') == '')
		{
			throw new \Exception("No spreadsheet title set", 1);
		}

		if (config('google.sheets')['pos'] == null || config('google.sheets')['pos'] == ''){
			throw new \Exception("No sheet ID set", 1);
		}

		$this->spreadsheet = \Revolution\Google\Sheets\Facades\Sheets::spreadsheetByTitle(config('google.spreadsheet_title'));
		$this->sheet = $this->spreadsheet->sheetById(config('google.sheets')['pos']);		
	}

	public function save(\stdClass $objData): mixed
	{

		$rowIndex = $this->getRowIndex($objData->sh_id);
		$response = new Response();

		if ($rowIndex == 0)
		{
			// No existe el elemento en Google Spreadsheets, 
			// lo registra por primera vez
			try {
				$appendReturn = $this->sheet->append([[
					$objData->sh_id,
					$objData->id,
					$objData->po_number,
					$objData->status,
					$objData->created_date,
					$objData->expected_date,
					$objData->vendor,
					$objData->product_cost,
					$objData->shipping_cost,
					$objData->total_cost,
					$objData->qty_of_skus,
					$objData->qty_of_units,
					$objData->qty_of_units_received
		        ]]);
		        //echo "\n---------------\nClass Type: ".get_class($appendReturn)."\n"; // Google\Service\Sheets\AppendValuesResponse o si hay algún fallo: Google\Service\Exception
				$response->value = $appendReturn;
				$response->notes = '';
				$response->status = 'Ok';
		    	return $response;
		    }
		    catch (\Exception $e){
		    	$response->value = $objData;
				$response->notes = $e->getMessage();
				$response->status = 'Error';
		    	return $response;
		    }
		}
		// Ya existe el elemento, lo actualiza solamente
		try {
			$updateReturn = $this->sheet->range("A".($rowIndex+1).":M".($rowIndex+1))->update([[
					$objData->sh_id,
					$objData->id,
					$objData->po_number,
					$objData->status,
					$objData->created_date,
					$objData->expected_date,
					$objData->vendor,
					$objData->product_cost,
					$objData->shipping_cost,
					$objData->total_cost,
					$objData->qty_of_skus,
					$objData->qty_of_units,
					$objData->qty_of_units_received            
	        ]]);
			$response->value = $updateReturn;
			$response->notes = '';
			$response->status = 'Ok';
			return $response;
		}
		catch (\Exception $e){
			$response->value = $objData;
			$response->notes = $e->getMessage();
			$response->status = 'Error';
	    	return $response;
		}
	}

	public function search(\stdClass $objSearch): \Illuminate\Support\Collection
	{
		$posCollection = new \Illuminate\Support\Collection([]);
		return $posCollection;
	}

	public function get($objId) : mixed
	{
		$response = new Response();
		$rowIndex = $this->getRowIndex($objId);
        if ($rowIndex == 0){
        	$response->value = false;
        	$response->notes = 'No existe una PO con el ID'.$objId;
        	$response->status = 'Error';
        	return $response;
        }
        $remotePo = $this->sheet->range("A".($rowIndex+1).":M".($rowIndex+1))->get();
        $cleanRemotePo = new \stdClass();
        $cleanRemotePo->sh_id = $remotePo->get(0)[0];
        $cleanRemotePo->id = $remotePo->get(0)[1];
        $cleanRemotePo->po_number = $remotePo->get(0)[2];
        $cleanRemotePo->status = $remotePo->get(0)[3];
        $cleanRemotePo->created_date = $remotePo->get(0)[4];
        $cleanRemotePo->expected_date = $remotePo->get(0)[5];
        $cleanRemotePo->vendor = $remotePo->get(0)[6];
        $cleanRemotePo->product_cost = floatval(preg_replace("/[\,\$]/",'',$remotePo->get(0)[7]));
        $cleanRemotePo->shipping_cost = floatval(preg_replace("/[\,\$]/",'',$remotePo->get(0)[8]));
        $cleanRemotePo->total_cost = floatval(preg_replace("/[\,\$]/",'',$remotePo->get(0)[9]));
        $cleanRemotePo->qty_of_skus = $remotePo->get(0)[10];
        $cleanRemotePo->qty_of_units = $remotePo->get(0)[11];
        $cleanRemotePo->qty_of_units_received = $remotePo->get(0)[12];
        return $cleanRemotePo;
	}

	public function delete($objId): bool
	{
		$gService = $this->spreadsheet->getService();
		$rowIndex = $this->getRowIndex($objId);
        if ($rowIndex == 0){
        	return false;
        }
        try {
	        $requestBody = new GoogleSheets\BatchUpdateSpreadsheetRequest([
	          'requests' => [
	            'deleteDimension' => [
	              'range' => [
	                'sheetId' => config('google.sheets')['pos'],
	                'dimension' => 'ROWS',
	                'startIndex' => $rowIndex,
	                'endIndex' => $rowIndex + 1      
	              ]
	            ]
	          ]
	        ]);
	        $response = $gService->spreadsheets->batchUpdate($this->spreadsheet->getSpreadsheetId(),$requestBody);
	        return true;
	    }
	    catch (\Exception $e){
	    	return false;	
	    }
	}

	public function getRowIndex ($poShipheroId)
	{
		$rows = $this->sheet->range("A:A")->get();
        $rowIndex = 0;
        for ($i = ($rows->count() - 1);$i>=1;$i--)
        {   
            if ($rows->get($i)[0] == $poShipheroId){
                $rowIndex = $i;
                break;
            }
        }
        return $rowIndex;
	}


}