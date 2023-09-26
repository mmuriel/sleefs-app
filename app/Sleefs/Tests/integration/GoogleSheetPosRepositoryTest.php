<?php

namespace Sleefs\Tests\integration;

use Illuminate\Foundation\Testing\TestCase ;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Sleefs\Helpers\Google\SpreadSheets\GoogleSheetPosRepository;


class GoogleSheetPosRepositoryTest extends TestCase {

    use RefreshDatabase;
    public $spreadsheet;
    private static $uniqueId = '';
    private static $fakePO = '';
    public function setUp():void
    {
        parent::setUp();
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
 

    public function testSaveANewPo(){
        $gsRepo = new GoogleSheetPosRepository();
        $savedPoResponse = $gsRepo->save(self::$fakePO);
        $this->assertEquals('Ok',$savedPoResponse->status);
        $this->assertEquals('Google\Service\Sheets\AppendValuesResponse',get_class($savedPoResponse->value));
    }


    public function testSaveAnExistingPo(){
        $gsRepo = new GoogleSheetPosRepository();
        self::$fakePO->shipping_cost = (self::$fakePO->shipping_cost + 200);//New value: self::$fakePO->shipping_cost = 1675.00
        $saveOperationResponse = $gsRepo->save(self::$fakePO);
        $this->assertEquals('Ok',$saveOperationResponse->status);
        $this->assertEquals('Google\Service\Sheets\BatchUpdateValuesResponse',get_class($saveOperationResponse->value));
    }


    public function testSearchForPos()
    {
        $gsRepo = new GoogleSheetPosRepository();
        $poSearchData = new \stdClass();
        $poSearchData->fields = ['status']; //Array of fields name
        $poSearchData->operators = ['==']; //Posible operators: ==, <=, >=, >, < 
        $poSearchData->values = ['pending'];
        $poSearchData->connectors = [];//Logical connector to connect fields
        $pos = $gsRepo->search($poSearchData);

        $this->assertInstanceOf(\Illuminate\Support\Collection::class,$pos);
    }


    public function testGetAPoByShipheroId()
    {
        $gsRepo = new GoogleSheetPosRepository();
        $remotePo = $gsRepo->get('UHVyY2hhc2VPcmRlcjoxNjM4NjU9');//Get Remote PO by Shiphero New ID
        $this->assertEquals(1450.90,$remotePo->product_cost);
        $this->assertEquals('UHVyY2hhc2VPcmRlcjoxNjM4NjU9',$remotePo->sh_id);
    }


    public function testGetPoRowIndexNumberInSpreadsheet()
    {
        $gsRepo = new GoogleSheetPosRepository();
        $poId = 'UHVyY2hhc2VPcmRlcjoxNjM4NjU9';
        $poSpreadSheetRowNumber = $gsRepo->getRowIndex($poId);
        $this->assertEquals(2,$poSpreadSheetRowNumber);//Row index in google spreadsheets starts in 0
    }


    public function testDeletePoRowByPoId()
    {
        $gsRepo = new GoogleSheetPosRepository();
        $deletePoResponse = $gsRepo->delete(self::$fakePO->sh_id);
        $this->assertTrue($deletePoResponse);
    }


    /* Preparing the Test */

    public function createApplication()
    {
        $app = require __DIR__.'/../../../../bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();
        return $app;
    }


     /**
     * Migrates the database and set the mailer to 'pretend'.
     * This will cause the tests to run quickly.
     */
    private function prepareForTests()
    {
    }

}