<?php

namespace Sleefs\Tests;

use Illuminate\Foundation\Testing\TestCase ;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;

use Sleefs\Helpers\curl\Curl;
use Sleefs\Helpers\GraphQL\GraphQLClient;


class GraphQLTest extends TestCase {

    use RefreshDatabase;
    //private $urlToCurl = 'http://apps.sleefs.com/appdev/public';



    // Para realizar los tests con este dominio, 
    // verificar que la maquina resuelva este dominio 
    // a la IP local 127.0.0.1 (localhost) en el 
    // archivo /etc/hosts

    //private $urlToCurl = 'https://local.sientifica.com';//dev 
    private $urlToCurl = '';//Production
	public function setUp():void{
        parent::setUp();
    }


    public function testGQQueryBasic()
    {

        //echo "SHIPHERO_ACCESS_TOKEN: ".env('SHIPHERO_ACCESSTOKEN')."\n";

    	$gqlClient = new GraphQLClient('https://public-api.shiphero.com/graphql',array("Authorization: Bearer ".env('SHIPHERO_ACCESSTOKEN')));

        $gqlQuery = array("query" => '{purchase_order(id:"427614"){data{id,po_number,po_date,account_id,vendor_id,created_at,fulfillment_status,po_note,description,subtotal,total_price,images,vendor_id,line_items(first: 1){edges{node {id,legacy_id,po_id,account_id,warehouse_id,vendor_id,po_number,sku,barcode,note,quantity,quantity_received,quantity_rejected,product_name}}}}}}');

        $resp = $gqlClient->query($gqlQuery,array("Content-type: application/json"));
        //print_r($resp->data->purchase_order->data->po_number);
        //print_r($resp->data);
        $this->assertMatchesRegularExpression('/SL191217/',$resp->data->purchase_order->data->po_number);
    }

    public function testGQQueryHttpHeadersError()
    {

        //Error generado a propósito, adicionando una misma cabecera HTTP dos veces (la cabecera "Authorization"), esto daña la estructura de la petición HTTP.
        $gqlClient = new GraphQLClient('https://public-api.shiphero.com/graphql',array("Authorization: Bearer ".env('SHIPHERO_ACCESSTOKEN'),"Authorization: Bearer ".env('SHIPHERO_ACCESSTOKEN')));

        $gqlQuery = array("query" => '{purchase_order(id:"427614"){data{id,po_number,po_date,account_id,vendor_id,created_at,fulfillment_status,po_note,description,subtotal,total_price,images,vendor_id,line_items(first: 1){edges{node {id,legacy_id,po_id,account_id,warehouse_id,vendor_id,po_number,sku,barcode,note,quantity,quantity_received,quantity_rejected,product_name}}}}}}');

        $resp = $gqlClient->query($gqlQuery,array("Content-type: application/json"));
        //print_r($resp->data->purchase_order->data->po_number);
        $this->assertTrue($resp->error);
    }

    /*

    //This test is make against monday.com's GQL API, it creates a new Pulse in a test Board
    //2025-06-05 This doesn't work anymore, since monday.com is not supported anymore by this app

    public function testGQLMutationBasic()
    {
        $gqlClient = new GraphQLClient(env('MONDAY_GRAPHQL_BASEURL'),array('Authorization: '.env('MONDAY_APIKEY').''));

        $gqlMutationCreation = array("query" => 'mutation{create_item(board_id:670700889,item_name:"P1201813-800" column_values:"{\"title6\":\"MMA800 - Titulo\",\"vendor2\":\"People Sports\",\"created_date8\":\"2020-11-20 23:04:21\",\"expected_date3\":\"2020-12-10 12:35:00\",\"pay\":\"Pending\",\"received\":\"\"}"){id,name,column_values{id,type,text,value,title}}}');

        $respCreation = $gqlClient->query($gqlMutationCreation,array("Content-type: application/json"));

        $gqlMutationDeletion = array("query" => 'mutation{delete_item(item_id: '.$respCreation->data->create_item->id.'){id}}');
        $respDeletion = $gqlClient->query($gqlMutationDeletion,array("Content-type: application/json"));

        $this->assertMatchesRegularExpression('/P1201813-800/',$respCreation->data->create_item->name);
        $this->assertTrue(isset($respDeletion->data->delete_item->id));
    }
    */


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

        //\Artisan::call('migrate');
    }

}