<?php

namespace Sleefs\Tests;

use Illuminate\Foundation\Testing\TestCase ;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;

use Sleefs\Models\Shopify\Product;
use Sleefs\Models\Shopify\Variant;


use Sleefs\Helpers\ShopifyAPI\Shopify;

class ShopifyApiTest extends TestCase {

	use RefreshDatabase;
	public $prd,$var1,$var2;

	public function setUp():void
    {
        parent::setUp();
        $this->prepareForTests();

        $this->prd = new Product();
		$this->prd->idsp = "shpfy_890987645";
		$this->prd->title = 'Colombian Sleeve Yellow';
		$this->prd->vendor = 'Sleefs';
		$this->prd->product_type = 'Sleeve';
		$this->prd->handle = 'colombian-sleeve-yellow';
		$this->prd->save();

		$this->var1 = new Variant();
		$this->var1->idsp = "shpfy_5678890951";
		$this->var1->sku = 'SL-COL-Y-L';
		$this->var1->title = 'Large';
		$this->var1->idproduct = $this->prd->id;
		$this->var1->price = 12.50;
		$this->var1->save();

		$this->var2 = new Variant();
		$this->var2->idsp = "shpfy_5678890952";
		$this->var2->sku = 'SL-COL-Y-XL';
		$this->var2->title = 'XL';
		$this->var2->idproduct = $this->prd->id;
		$this->var2->price = 12.50;
		$this->var2->save();
    }
 

	public function testInmemoryDatabaseAddingRecords(){		

		/* Testing saved items to database */
		$this->assertDatabaseHas('products',['idsp' => 'shpfy_890987645','title' => 'Colombian Sleeve Yellow']);
		$this->assertDatabaseHas('variants',['idsp' => 'shpfy_5678890951','idsp' => 'shpfy_5678890952']);

		
	}

	public function testInmemoryDatabaseProductVariantsRelationship(){

		$tmpPrd = Product::where('title','=','Colombian Sleeve Yellow')->first();
		$this->assertEquals('Sleefs',$this->var1->product->vendor);
		$this->assertMatchesRegularExpression('/SL\-COL\-Y\-L/',$tmpPrd->variants[0]->sku);


	}


	
	public function testGetProductsFromApi(){

		$spClient = new Shopify(getenv('SHPFY_BASEURL'),getenv('SHPFY_ACCESSTOKEN'));
		$options = "ids=431368941,10847934410";
		$data = $spClient->getAllProducts($options);
		//print_r($data);
		$this->assertMatchesRegularExpression("/Baseball\ Lace\ USA\ Arm\ Sleeve/",$data['content']->products[0]->title,"El nombre del producto no es: Baseball Lace USA Arm Sleeve, ahora es: ".$data['content']->products[0]->title);
		$this->assertEquals(1,count($data['content']->products),"La cantidad de productos recuperada no es 2, es: ".count($data['content']->products));
		$this->assertEquals("Sleeve",$data['content']->products[0]->product_type);
		//count($data->products);

	}


	public function testGetVariantsFromApi(){

		$spClient = new Shopify(getenv('SHPFY_BASEURL'),getenv('SHPFY_ACCESSTOKEN'));
		$options = "ids=431368941,10847934410";
		$data = $spClient->getAllProducts($options);
		
		$variantRaw = $spClient->getSingleProductVariant($data['content']->products[0]->variants[0]->id);
		$this->assertMatchesRegularExpression('/SL\-BB\-USA\-Y\-1/',$variantRaw->variant->sku);

	}



	public function testGetSingleProductFromApi(){

		$spClient = new Shopify(getenv('SHPFY_BASEURL'),getenv('SHPFY_ACCESSTOKEN'));
		$options = "ids=431368941,10847934410";
		$data = $spClient->getSingleProduct('431368941');
		$this->assertMatchesRegularExpression('/Baseball\ Lace\ USA\ Arm\ Sleeve/',$data->product->title);

	}



	public function testGet200ProductsFromApi(){

		$products = [];
		$nextParams = new \stdClass();
		$shopifyApiClient = new Shopify(getenv('SHPFY_BASEURL'),getenv('SHPFY_ACCESSTOKEN'));
		$options = "limit=50";
		$ctrlLoop = 1;
		do {
			$response = $shopifyApiClient->getAllProducts($options);
			//print_r($response['content']);
			$products = array_merge($products,$response['content']->products);
			$options = "limit=50&page_info=".$response['headers']->links_params[0]->cursor;
			if (isset($response['headers']->links_params[1]->linkType) && $response['headers']->links_params[1]->linkType == 'next'){
				$options = "limit=50&page_info=".$response['headers']->links_params[1]->cursor;
			}
			$ctrlLoop++;
		}
		while ($ctrlLoop <= 4);
		$this->assertEquals(200,count($products));
		$this->assertObjectHasProperty('id',$products[199]);
		$this->assertMatchesRegularExpression("/[0-9]{5,12}/" ,$products[199]->id);

	}


	public function testGetNextLinkParametersFromProductPagePagination(){

		$shopifyApiClient = new Shopify(getenv('SHPFY_BASEURL'),getenv('SHPFY_ACCESSTOKEN'));
		$strNextLink = '<https://sleefs-2.myshopify.com/admin/api/2020-01/products.json?limit=250&page_info=eyJsYXN0X2lkIjo2NjI4MDc5NDY4NjM3LCJsYXN0X3ZhbHVlIjoiMCBOdW1iZXIgRWFycmluZyAtIEdvbGQgUGxhdGVkIFN0YWlubGVzcyBTdGVlbCIsImRpcmVjdGlvbiI6Im5leHQifQ>; rel="next"';

		$paramsLink = $shopifyApiClient->getPaginationLinkParameters($strNextLink);


		
		$this->assertEquals('next',$paramsLink->linkType);
		$this->assertEquals('250',$paramsLink->limit);
		$this->assertEquals('eyJsYXN0X2lkIjo2NjI4MDc5NDY4NjM3LCJsYXN0X3ZhbHVlIjoiMCBOdW1iZXIgRWFycmluZyAtIEdvbGQgUGxhdGVkIFN0YWlubGVzcyBTdGVlbCIsImRpcmVjdGlvbiI6Im5leHQifQ',$paramsLink->cursor);

	}


	public function testGetPreviousLinkParametersFromProductPagePagination(){

		$shopifyApiClient = new Shopify(getenv('SHPFY_BASEURL'),getenv('SHPFY_ACCESSTOKEN'));
		$strPreviousLink = '<https://sleefs-2.myshopify.com/admin/products.json?limit=250&page_info=eyJkaXJlY3Rpb24iOiJwcmV2IiwibGFzdF9pZCI6NjU1MTMzOTM2ODU0MSwibGFzdF92YWx1ZSI6IjE1IE51bWJlciBQZW5kYW50IHdpdGggQ2hhaW4gTmVja2xhY2UgLSBHb2xkIFBsYXRlZCBTdGFpbmxlc3MgU3RlZWwifQ>; rel="previous"';

		$paramsLink = $shopifyApiClient->getPaginationLinkParameters($strPreviousLink);


		
		$this->assertEquals('previous',$paramsLink->linkType);
		$this->assertEquals('250',$paramsLink->limit);
		$this->assertEquals('eyJkaXJlY3Rpb24iOiJwcmV2IiwibGFzdF9pZCI6NjU1MTMzOTM2ODU0MSwibGFzdF92YWx1ZSI6IjE1IE51bWJlciBQZW5kYW50IHdpdGggQ2hhaW4gTmVja2xhY2UgLSBHb2xkIFBsYXRlZCBTdGFpbmxlc3MgU3RlZWwifQ',$paramsLink->cursor);

	}


	public function testSplitingRawLinksInHeaderResponse(){

		$shopifyApiClient = new Shopify(getenv('SHPFY_BASEURL'),getenv('SHPFY_ACCESSTOKEN'));
		$rawLinksString = '<https://sleefs-2.myshopify.com/admin/api/2020-01/products.json?limit=250&page_info=eyJkaXJlY3Rpb24iOiJwcmV2IiwibGFzdF9pZCI6NjU1MTMzOTM2ODU0MSwibGFzdF92YWx1ZSI6IjE1IE51bWJlciBQZW5kYW50IHdpdGggQ2hhaW4gTmVja2xhY2UgLSBHb2xkIFBsYXRlZCBTdGFpbmxlc3MgU3RlZWwifQ>; rel="previous", <https://sleefs-2.myshopify.com/admin/api/2020-01/products.json?limit=250&page_info=eyJkaXJlY3Rpb24iOiJuZXh0IiwibGFzdF9pZCI6NjU1MTMzNzQ2Nzk5NywibGFzdF92YWx1ZSI6IjY1IE51bWJlciBQZW5kYW50IHdpdGggQ2hhaW4gTmVja2xhY2UgLSBTdGFpbmxlc3MgU3RlZWwifQ>; rel="next"';
		$links = $shopifyApiClient->splitPaginationLinksInResponseHeader($rawLinksString);
		$this->assertEquals(2,count($links));
		$this->assertMatchesRegularExpression("/rel=\"previous\"/",$links[0]);
		$this->assertMatchesRegularExpression("/rel=\"next\"/",$links[1]);

	}


	public function testTryToSplitASingleLinkRawString(){

		$shopifyApiClient = new Shopify(getenv('SHPFY_BASEURL'),getenv('SHPFY_ACCESSTOKEN'));
		$rawLinksString = '<https://sleefs-2.myshopify.com/admin/products.json?limit=50&page_info=eyJsYXN0X2lkIjo2NjI4MDgyMzUyMjIxLCJsYXN0X3ZhbHVlIjoiMTUgTnVtYmVyIEVhcnJpbmcgLSBTdGFpbmxlc3MgU3RlZWwiLCJkaXJlY3Rpb24iOiJuZXh0In0>; rel="next"';
		$links = $shopifyApiClient->splitPaginationLinksInResponseHeader($rawLinksString);
		$this->assertEquals(1,count($links));
		$this->assertMatchesRegularExpression("/rel=\"next\"/",$links[0]);
	}


	public function testGetNextCursorIdInPaginationLinks(){

		$linksInHeader = [new \stdClass(),new \stdClass()];
		$linksInHeader[0]->linkType = 'previous';
		$linksInHeader[0]->cursor = 'eyJkaXJlY3Rpb24iOiJwcmV2IiwibGFzdF9pZCI6NzExODA5NjY2MjYyMSwibGFzdF92YWx1ZSI6Ijc3IE51bWJlciBQZW5kYW50IHdpdGggQ2hhaW4gS2lkcyBOZWNrbGFjZSAtIEdvbGQgUGxhdGVkIFN0YWlubGVzcyBTdGVlbCJ9';
		$linksInHeader[0]->limit = 250;

		$linksInHeader[1]->linkType = 'next';
        $linksInHeader[1]->cursor = 'eyJkaXJlY3Rpb24iOiJuZXh0IiwibGFzdF9pZCI6NzEwMjQ0NjM3MDkwOSwibGFzdF92YWx1ZSI6IkFybXkgR3JlZW4gQ2xlYXIgSGVsbWV0IEV5ZS1TaGllbGQgVmlzb3IgZm9yIEtpZHMifQ';
        $linksInHeader[1]->limit =250;	

		$shopifyApiClient = new Shopify(getenv('SHPFY_BASEURL'),getenv('SHPFY_ACCESSTOKEN'));
		$nextCursor = $shopifyApiClient->getPaginationCursorValue($linksInHeader,'next');

		
		$this->assertEquals('eyJkaXJlY3Rpb24iOiJuZXh0IiwibGFzdF9pZCI6NzEwMjQ0NjM3MDkwOSwibGFzdF92YWx1ZSI6IkFybXkgR3JlZW4gQ2xlYXIgSGVsbWV0IEV5ZS1TaGllbGQgVmlzb3IgZm9yIEtpZHMifQ',$nextCursor);
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