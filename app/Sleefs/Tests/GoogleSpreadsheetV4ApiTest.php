<?php

namespace Sleefs\Tests;


use Google\Service\Sheets\AppendValuesResponse;
use Google\Service\Sheets as GoogleSheets;
use Google\Service\Sheets\BatchUpdateSpreadsheetResponse;
use Google\Service\Sheets\BatchUpdateValuesResponse;
use Google\Service\Sheets\ClearValuesResponse;
use Illuminate\Support\Collection;

use Illuminate\Foundation\Testing\TestCase ;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Http\Controllers\Controller;

use Revolution\Google\Sheets\Facades\Sheets;
use Sleefs\Helpers\Google\SpreadSheets\GoogleSpreadsheetGetWorkSheetIndex;

class GoogleSpreadsheetV4ApiTest extends TestCase {

    use RefreshDatabase;
    public $spreadsheet;
    private static $uniqueId = '';
    private static $fakePO = '';
    public function setUp():void
    {
        parent::setUp();
        $this->prepareForTests();
        if (!preg_match("/[0-9a-fA-F]{32}/",self::$uniqueId))
        {
            self::$uniqueId = md5(date("yyyy-mm-dd HH:ii:ss")." - sleefs - shiphero POs");
        }

        if (!isset(self::$fakePO->id))
        {
            self::$fakePO = new \stdClass();
            self::$fakePO->sh_id = self::$uniqueId;
            self::$fakePO->id = '2928';
            self::$fakePO->po_number = '185557482501025491 Pink Balaclav';   
            self::$fakePO->status = 'pending';
            self::$fakePO->created_date = '2023-08-31 12:19:00';    
            self::$fakePO->expected_date = '2023-09-18';
            self::$fakePO->vendor = 'Wuxi Jieyu Microfiber Fabric Manufacturing';
            self::$fakePO->product_cost = 1450.00;
            self::$fakePO->shipping_cost = 1475.00;
            self::$fakePO->total_cost = 2925.00;
            self::$fakePO->qty_of_skus = 1;
            self::$fakePO->qty_of_units = 1000;
            self::$fakePO->qty_of_units_received = 0;
        }
    }
 

    public function testAddPoAsRowToSheet(){
        $sheet = $this->spreadsheet->sheetById(config('google.sheets')['pos']);
        $appendReturn = $sheet->append([[
            self::$fakePO->sh_id,
            self::$fakePO->id,
            self::$fakePO->po_number,
            self::$fakePO->status,
            self::$fakePO->created_date,
            self::$fakePO->expected_date,
            self::$fakePO->vendor,
            self::$fakePO->product_cost,
            self::$fakePO->shipping_cost,
            self::$fakePO->total_cost,
            self::$fakePO->qty_of_skus,
            self::$fakePO->qty_of_units,
            self::$fakePO->qty_of_units_received
        ]]);
        $this->assertEquals (config('google.spreadsheet_id'),$appendReturn->spreadsheetId); # 1HQGmH-pjXOhjdOBe_rVlHMxU90PT1GwFaChaWQZnpgo
        /*
            Borra el nuevo registro creado
        */
        $addedPoRow = 0;
        if (preg_match("/pos\![A-Z]{1,4}[0-9]{0,6}\:[A-Z]{1,4}([0-9]{1,4})$/",$appendReturn->tableRange,$addedPoRow)){
            $addedPoRow = $addedPoRow[1];
        }
        $gService = $this->spreadsheet->getService();
        $rowIndex = $addedPoRow+1; // Replace with the row index you want to delete
        $requestBody = new GoogleSheets\BatchUpdateSpreadsheetRequest([
          'requests' => [
            'deleteDimension' => [
              'range' => [
                'sheetId' => $gService->spreadsheets->get(config('google.spreadsheet_id'))->sheets[0]->properties->sheetId,
                'dimension' => 'ROWS',
                'startIndex' => $rowIndex - 1,
                'endIndex' => $rowIndex
              ]
            ]
          ]
        ]);
        $response = $gService->spreadsheets->batchUpdate(config('google.spreadsheet_id'),$requestBody);
    }


    public function testGetRowByPoId(){
        
        $sheet = $this->spreadsheet->sheetById(config('google.sheets')['pos']);
        $appendReturn = $sheet->append([[
            self::$fakePO->sh_id,
            self::$fakePO->id,
            self::$fakePO->po_number,
            self::$fakePO->status,
            self::$fakePO->created_date,
            self::$fakePO->expected_date,
            self::$fakePO->vendor,
            self::$fakePO->product_cost,
            self::$fakePO->shipping_cost,
            self::$fakePO->total_cost,
            self::$fakePO->qty_of_skus,
            self::$fakePO->qty_of_units,
            self::$fakePO->qty_of_units_received
        ]]);
        //========================================================================================
        $sheet = $this->spreadsheet->sheetById(config('google.sheets')['pos']);
        $rows = $sheet->range("A:A")->get();
        $rowIndex = 0;
        for ($i = ($rows->count() - 1);$i>=1;$i--)
        {   
            if ($rows->get($i)[0] == self::$fakePO->sh_id){
                $rowIndex = $i;
                break;
            }
        }
        $fakePoDataGotFromGoogleSheets = $sheet->range("A".($rowIndex+1).":M".($rowIndex+1))->get();
        $this->assertEquals (self::$fakePO->sh_id,$fakePoDataGotFromGoogleSheets->get(0)[0]);

        /*
            Borra el nuevo registro creado
        */
        $gService = $this->spreadsheet->getService();
        //$rowIndex = $addedPoRow+1; // Replace with the row index you want to delete
        $requestBody = new GoogleSheets\BatchUpdateSpreadsheetRequest([
          'requests' => [
            'deleteDimension' => [
              'range' => [
                'sheetId' => $gService->spreadsheets->get(config('google.spreadsheet_id'))->sheets[0]->properties->sheetId,
                'dimension' => 'ROWS',
                'startIndex' => $rowIndex,
                'endIndex' => $rowIndex + 1
              ]
            ]
          ]
        ]);
        $response = $gService->spreadsheets->batchUpdate(config('google.spreadsheet_id'),$requestBody);
    }


    public function testUpdateRowDataInSheets()
    {
        $timeToSleep = 1; // 6 secs
        $sheet = $this->spreadsheet->sheetById(config('google.sheets')['pos']);
        $appendReturn = $sheet->append([[
            self::$fakePO->sh_id,
            self::$fakePO->id,
            self::$fakePO->po_number,
            self::$fakePO->status,
            self::$fakePO->created_date,
            self::$fakePO->expected_date,
            self::$fakePO->vendor,
            self::$fakePO->product_cost,
            self::$fakePO->shipping_cost,
            self::$fakePO->total_cost,
            self::$fakePO->qty_of_skus,
            self::$fakePO->qty_of_units,
            self::$fakePO->qty_of_units_received
        ]]);
        //========================================================================================
        $sheet = $this->spreadsheet->sheetById(config('google.sheets')['pos']);
        $rows = $sheet->range("A:A")->get();
        $rowIndex = 0;
        for ($i = ($rows->count() - 1);$i>=1;$i--)
        {   
            if ($rows->get($i)[0] == self::$fakePO->sh_id){
                $rowIndex = $i;
                break;
            }
        }
        $fakePoDataGotFromGoogleSheets = $sheet->range("A".($rowIndex+1).":M".($rowIndex+1))->get();
        $fakePoDataGotFromGoogleSheets = $fakePoDataGotFromGoogleSheets->get(0);

        sleep($timeToSleep);
        //Update values in Google Sheet
        self::$fakePO->expected_date = '2023-09-20';
        self::$fakePO->qty_of_units_received = 300;
        $requestResponse = $sheet->range("A".($rowIndex+1).":M".($rowIndex+1))->update([[
            self::$fakePO->sh_id,
            self::$fakePO->id,
            self::$fakePO->po_number,
            self::$fakePO->status,
            self::$fakePO->created_date,
            self::$fakePO->expected_date,
            self::$fakePO->vendor,
            self::$fakePO->product_cost,
            self::$fakePO->shipping_cost,
            self::$fakePO->total_cost,
            self::$fakePO->qty_of_skus,
            self::$fakePO->qty_of_units,
            self::$fakePO->qty_of_units_received
        ]]);
        //print_r($requestResponse);

        //Get 
        $updatedFakePo = $sheet->range("A".($rowIndex+1).":M".($rowIndex+1))->get()->get(0);
        //Assertions:
        $this->assertEquals (config('google.spreadsheet_id'),$requestResponse->spreadsheetId);#1HQGmH-pjXOhjdOBe_rVlHMxU90PT1GwFaChaWQZnpgo
        $this->assertEquals ('2023-09-20',$updatedFakePo[5]);
        $this->assertEquals (self::$fakePO->sh_id,$updatedFakePo[0]);
        sleep($timeToSleep);
        //================================================================================================
        //Borra el registro remoto para evitar el crecimiento sin control
        $gService = $this->spreadsheet->getService();
        //$rowIndex = $addedPoRow+1; // Replace with the row index you want to delete
        $requestBody = new GoogleSheets\BatchUpdateSpreadsheetRequest([
          'requests' => [
            'deleteDimension' => [
              'range' => [
                'sheetId' => $gService->spreadsheets->get(config('google.spreadsheet_id'))->sheets[0]->properties->sheetId,
                'dimension' => 'ROWS',
                'startIndex' => $rowIndex,
                'endIndex' => $rowIndex + 1
              ]
            ]
          ]
        ]);
        $response = $gService->spreadsheets->batchUpdate(config('google.spreadsheet_id'),$requestBody);

    }


    /* Preparing the Test */

    public function createApplication()
    {
        $app = require __DIR__.'/../../../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }


     /**
     * Migrates the database and set the mailer to 'pretend'.
     * This will cause the tests to run quickly.
     */
    private function prepareForTests()
    {

        $this->spreadsheet = Sheets::spreadsheetByTitle(config('google.spreadsheet_title')); 
        // \Artisan::call('migrate');
    }

}