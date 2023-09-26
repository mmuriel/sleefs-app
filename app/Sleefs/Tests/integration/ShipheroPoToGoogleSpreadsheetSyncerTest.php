<?php

namespace Sleefs\Tests\integration;

use Illuminate\Foundation\Testing\TestCase ;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;

use Sleefs\Models\Shiphero\PurchaseOrder;
use Sleefs\Models\Shiphero\PurchaseOrderItem;
use Sleefs\Models\Shiphero\Vendor;

use Sleefs\Helpers\Google\SpreadSheets\GoogleSheetPosRepository;
use Sleefs\Helpers\Google\SpreadSheets\ShipheroPoToGoogleSpreadsheetSyncer;

use Sleefs\Helpers\ShipheroGQLApi\ShipheroGQLApi;




class ShipheroPoToGoogleSpreadsheetSyncerTest extends TestCase {

    use RefreshDatabase;
	private static $extendedPo = '';
    private $pos = array();
    private $items = array();
    private $vendors = array();

	public function setUp():void
    {
        parent::setUp();

        $this->prepareForTests();
        if (self::$extendedPo == '')
        {
            self::$extendedPo = json_decode('{"id":"UHVyY2hhc2VPcmRlcjoxNDkzNDgx","legacy_id":1493481,"po_number":"173892295001025491  Gloves","po_date":"2023-08-14 00:00:00","account_id":"QWNjb3VudDoxMTU3","vendor_id":"ND","created_at":"2023-05-04 15:45:08","fulfillment_status":"pending","po_note":"5\/8 Deposit $3,410.52\n7\/12 Balance $9,505.18","description":null,"subtotal":"11573.7","shipping_price":"1342.00","total_price":"12915.7","line_items":[{"node":{"id":"UHVyY2hhc2VPcmRlckxpbmVJdGVtOjIxODc3MzA5","price":"5.1300","po_id":"1493481","account_id":"QWNjb3VudDoxMTU3","warehouse_id":"V2FyZWhvdXNlOjE2ODQ=","vendor_id":"VmVuZG9yOjQxNjQ3MA==","po_number":"Gloves","sku":"SL-NEOORG-RG-M","barcode":"SL-NEOORG-RG-M","note":"","quantity":100,"quantity_received":100,"quantity_rejected":0,"product_name":"Hot Orange Sticky Football Receiver Gloves M \/ Hot Orange","fulfillment_status":"pending","vendor":null}},{"node":{"id":"UHVyY2hhc2VPcmRlckxpbmVJdGVtOjIxODc3MzE0","price":"4.8500","po_id":"1493481","account_id":"QWNjb3VudDoxMTU3","warehouse_id":"V2FyZWhvdXNlOjE2ODQ=","vendor_id":"VmVuZG9yOjQxNjQ3MA==","po_number":"Gloves","sku":"SL-WHT-RG-L","barcode":"SL-WHT-RG-L","note":"","quantity":500,"quantity_received":0,"quantity_rejected":0,"product_name":"Basic White Sticky Football Receiver Gloves L \/ White","fulfillment_status":"pending","vendor":null}},{"node":{"id":"UHVyY2hhc2VPcmRlckxpbmVJdGVtOjIxODc3MzE2","price":"5.1300","po_id":"1493481","account_id":"QWNjb3VudDoxMTU3","warehouse_id":"V2FyZWhvdXNlOjE2ODQ=","vendor_id":"VmVuZG9yOjQxNjQ3MA==","po_number":"Gloves","sku":"SL-NEOPNK-RG-L","barcode":"SL-NEOPNK-RG-L","note":"","quantity":150,"quantity_received":150,"quantity_rejected":0,"product_name":"Neon Pink Sticky Football Receiver Gloves L \/ Pink","fulfillment_status":"pending","vendor":null}},{"node":{"id":"UHVyY2hhc2VPcmRlckxpbmVJdGVtOjIxODc3MzEy","price":"4.7600","po_id":"1493481","account_id":"QWNjb3VudDoxMTU3","warehouse_id":"V2FyZWhvdXNlOjE2ODQ=","vendor_id":"VmVuZG9yOjQxNjQ3MA==","po_number":"Gloves","sku":"SL-BLK-RG-L","barcode":"SL-BLK-RG-L","note":"","quantity":500,"quantity_received":0,"quantity_rejected":0,"product_name":"Basic Black Sticky Football Receiver Gloves L \/ Black","fulfillment_status":"pending","vendor":null}},{"node":{"id":"UHVyY2hhc2VPcmRlckxpbmVJdGVtOjIxODc3MzEx","price":"4.8500","po_id":"1493481","account_id":"QWNjb3VudDoxMTU3","warehouse_id":"V2FyZWhvdXNlOjE2ODQ=","vendor_id":"VmVuZG9yOjQxNjQ3MA==","po_number":"Gloves","sku":"SL-WHT-RG-M","barcode":"SL-WHT-RG-M","note":"","quantity":500,"quantity_received":0,"quantity_rejected":0,"product_name":"Basic White Sticky Football Receiver Gloves M \/ White","fulfillment_status":"pending","vendor":null}},{"node":{"id":"UHVyY2hhc2VPcmRlckxpbmVJdGVtOjIxODc3MzEw","price":"4.7600","po_id":"1493481","account_id":"QWNjb3VudDoxMTU3","warehouse_id":"V2FyZWhvdXNlOjE2ODQ=","vendor_id":"VmVuZG9yOjQxNjQ3MA==","po_number":"Gloves","sku":"SL-YELLEM-RG-XL","barcode":"SL-YELLEM-RG-XL","note":"","quantity":100,"quantity_received":100,"quantity_rejected":0,"product_name":"Hue Lemon Yellow Sticky Football Receiver Gloves XL \/ Lemon Yellow","fulfillment_status":"pending","vendor":null}},{"node":{"id":"UHVyY2hhc2VPcmRlckxpbmVJdGVtOjIxODc3MzEz","price":"4.7600","po_id":"1493481","account_id":"QWNjb3VudDoxMTU3","warehouse_id":"V2FyZWhvdXNlOjE2ODQ=","vendor_id":"VmVuZG9yOjQxNjQ3MA==","po_number":"Gloves","sku":"SL-BLK-RG-M","barcode":"SL-BLK-RG-M","note":"","quantity":500,"quantity_received":0,"quantity_rejected":0,"product_name":"Basic Black Sticky Football Receiver Gloves M \/ Black","fulfillment_status":"pending","vendor":null}},{"node":{"id":"UHVyY2hhc2VPcmRlckxpbmVJdGVtOjIxODc3MzE1","price":"5.1300","po_id":"1493481","account_id":"QWNjb3VudDoxMTU3","warehouse_id":"V2FyZWhvdXNlOjE2ODQ=","vendor_id":"VmVuZG9yOjQxNjQ3MA==","po_number":"Gloves","sku":"SL-NEOPNK-RG-XXL","barcode":"SL-NEOPNK-RG-XXL","note":"","quantity":40,"quantity_received":40,"quantity_rejected":0,"product_name":"Neon Pink Sticky Football Receiver Gloves XXL \/ Pink","fulfillment_status":"pending","vendor":null}}],"vendor_name":"ND"}');
        }
        self::$extendedPo->po_id = $this->pos[0]->po_id;
    }
 

	public function testCreateANewRemotePo()
    {
        //print_r(self::$extendedPo);
        $shipheroPoToGSSyncer = new ShipheroPoToGoogleSpreadsheetSyncer();
        $syncResponse = $shipheroPoToGSSyncer->sync(self::$extendedPo);
        //print_r($syncResponse);
        $this->assertEquals('Ok',$syncResponse->status);
        $this->assertInstanceOf('Google\Service\Sheets\AppendValuesResponse',$syncResponse->value);
    }


    public function testUpdateANewRemotePo()
    {
        //print_r(self::$extendedPo);
        $shipheroPoToGSSyncer = new ShipheroPoToGoogleSpreadsheetSyncer();
        $gsRepo = new GoogleSheetPosRepository();
        
        self::$extendedPo->shipping_price = self::$extendedPo->shipping_price + 250.20;
        self::$extendedPo->total_price = self::$extendedPo->total_price + 250.20;
        
        $syncResponse = $shipheroPoToGSSyncer->sync(self::$extendedPo);
        //print_r($syncResponse);
        $this->assertEquals('Ok',$syncResponse->status);
        $this->assertInstanceOf('Google\Service\Sheets\BatchUpdateValuesResponse',$syncResponse->value);

        $poInGoogleSheets = $gsRepo->get(self::$extendedPo->id);
        $this->assertEquals(13165.9,$poInGoogleSheets->total_cost);

        $gsRepo->delete(self::$extendedPo->id);
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

     	\Artisan::call('migrate');
        array_push($this->pos, new PurchaseOrder());
        $this->pos[0]->po_id = 2833;
        $this->pos[0]->po_id_legacy = 1493481;
        $this->pos[0]->po_id_token = 'UHVyY2hhc2VPcmRlcjoxNDkzNDgx';
        $this->pos[0]->po_number = '173892295001025491 Gloves';
        $this->pos[0]->po_date = '2023-08-14 00:00:00';
        $this->pos[0]->fulfillment_status = 'pending';
        $this->pos[0]->save();


        array_push($this->vendors,new Vendor());
        $this->vendors[0]->idsp = 'VmVuZG9yOjQxNjQ3MA==';
        $this->vendors[0]->name = 'FUJIAN HUAFEI LEATHER PRODUCTS';
        $this->vendors[0]->legacy_idsp = '416470';
        $this->vendors[0]->email = 'sales5@wonny.cn';
        $this->vendors[0]->save();

        
    }

}