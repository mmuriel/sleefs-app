<?php

namespace Sleefs\Tests;

use Illuminate\Foundation\Testing\TestCase ;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;

use Sleefs\Helpers\curl\Curl;


class CurlTest extends TestCase {

    use RefreshDatabase;
    //private $urlToCurl = 'http://apps.sleefs.com/appdev/public';



    // Para realizar los tests con este dominio, 
    // verificar que la maquina resuelva este dominio 
    // a la IP local 127.0.0.1 (localhost) en el 
    // archivo /etc/hosts

    //private $urlToCurl = 'https://local.sientifica.com';//dev 
    private $urlToCurl = 'https://sleefs-2.sientifica.com';//Production
	public function setUp():void{
        parent::setUp();
    }


    public function testGetWithCurlClass(){

    	//$res = Curl::urlGet('http://localhost/api/tests/curl/param1?checker=param2');
        $res = Curl::urlGet($this->urlToCurl.'/api/tests/curl/param1?checker=param2',['Custom-SSL-Verification:false']);
        //print_r($res);
    	$this->assertMatchesRegularExpression("/^1\. param2 \- param1/",$res);

    }

    public function testPostWithCurlClass(){

    	$content = array("v1"=>"Valor 1","v2"=>"Valor 2");
    	$res = Curl::urlPost($this->urlToCurl.'/api/tests/curl',json_encode($content),['Custom-SSL-Verification:false']);
    	$res = json_decode($res);
    	$this->assertMatchesRegularExpression("/(Valor\ 1)/",$res,"No contiene el 'Valor 1' en ".$res);

    }


    public function testPutWithCurlClass(){

    	$content = array("v1"=>"Valor 1","v2"=>"Valor 2");
    	$res = Curl::urlPUT($this->urlToCurl.'/api/tests/curl',json_encode($content),['Custom-SSL-Verification:false']);
    	$res = json_decode($res);
    	$this->assertMatchesRegularExpression("/(Valor\ 1)/",$res,"No contiene el 'Valor 1' en ".$res);

    }

    public function testDeleteWithCurlClass(){

    	$content = array("v1"=>"Valor 1","v2"=>"Valor 2");
    	$res = Curl::urlDelete($this->urlToCurl.'/api/tests/curl',json_encode($content),['Custom-SSL-Verification:false']);
    	//var_dump($res);
    	//$res = json_decode($res);
    	$this->assertMatchesRegularExpression("/(Valor\ 1)/",$res,"No contiene el 'Valor 1' en ".$res);

    }


    public function testGetHeadersBack(){

        $res = Curl::urlGetWithResponseHeaders($this->urlToCurl.'/api/tests/curl/param1?checker=param2',['Custom-SSL-Verification:false']);
        //print_r($res);
        $this->assertMatchesRegularExpression("/^1\. param2 \- param1/",$res['content']);
        $this->assertMatchesRegularExpression("/\"date\":/",$res['headers']);
        $this->assertObjectHasProperty("date",json_decode($res['headers']));

    }

	/* Preparing the Test */
	public function createApplication(){
        $app = require __DIR__.'/../../../bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();
        return $app;
    }

}