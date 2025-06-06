<?php

namespace Sleefs\Tests;

use Illuminate\Foundation\Testing\TestCase ;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;

use Sleefs\Models\Shiphero\PurchaseOrder;
use Sleefs\Models\Shiphero\PurchaseOrderItem;

use \mdeschermeier\shiphero\Shiphero;


class ShipheroApiTest extends TestCase {

	use RefreshDatabase;
	public $po,$item1,$item2;

	public function setUp():void
    {
        parent::setUp();
        $this->prepareForTests();

        $this->po = new PurchaseOrder();
        $this->po->po_id = 515;
        $this->po->po_id_legacy = 53032;
        $this->po->po_id_token = 'UHVyY2hhc2VPcmRlcjo1MzAzMg==';
        $this->po->po_number = '1710-05 Brett Stern Order';
        $this->po->po_date = '2017-10-30 00:00:00';
        $this->po->fulfillment_status = 'closed';
		$this->po->save();

		$this->item1 = new PurchaseOrderItem();
		$this->item1->idpo = $this->po->id;
		$this->item1->sku = 'SL-USA-BLK-CL-L';
		$this->item1->barcode = 'bSL-USA-BLK-CL-L';
		$this->item1->shid = '59dbc5830f969';
		$this->item1->quantity = 5;
		$this->item1->quantity_received = 5;
		$this->item1->name = 'USA America Flag / Black Compression Tights / Leggings L / Red/White/Blue/Black';
		$this->item1->idmd5 = md5('SL-USA-BLK-CL-L'.'-'.'515');
		$this->item1->save();

		$this->item2 = new PurchaseOrderItem();
		$this->item2->idpo = $this->po->id;
		$this->item2->sku = 'SL-USA-BLK-CL-XL';
		$this->item2->barcode = 'bSL-USA-BLK-CL-XL';
		$this->item2->shid = '59dbc5830fa20';
		$this->item2->quantity = 3;
		$this->item2->quantity_received = 3;
		$this->item2->name = 'USA America Flag / Black Compression Tights / Leggings XL / Red/White/Blue/Black';
		$this->item2->idmd5 = md5('SL-USA-BLK-CL-XL'.'-'.'515');
		$this->item2->save();
    }
 

	public function testInmemoryDatabaseAddingRecords(){		

		/* Testing saved items to database */
		$this->assertDatabaseHas('sh_purchaseorders',['po_id' => '515','fulfillment_status' => 'closed','po_id_legacy' => 53032]);
		$this->assertDatabaseHas('sh_purchaseorder_items',['shid' => '59dbc5830f969','sku' => 'SL-USA-BLK-CL-L']);

		
	}

	public function testInmemoryDatabaseProductVariantsRelationship(){

		$tmpPrd = PurchaseOrder::where('po_id','=','515')->first();
		$this->assertEquals('closed',$this->po->fulfillment_status);		
		$this->assertMatchesRegularExpression('/SL\-USA\-BLK\-CL\-L/',$tmpPrd->items[0]->sku);

		$tmpVariant = PurchaseOrderItem::where('shid','=','59dbc5830fa20')->first();
		$this->assertMatchesRegularExpression('/SL\-USA\-BLK\-CL\-XL/',$tmpVariant->sku);


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

     	\Artisan::call('migrate');
    }

}