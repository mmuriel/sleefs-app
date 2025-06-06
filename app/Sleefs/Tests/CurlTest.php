<?php

namespace Sleefs\Tests;

use Illuminate\Foundation\Testing\TestCase ;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;

use Sleefs\Helpers\curl\Curl;


class CurlTest extends TestCase {

    use RefreshDatabase;

    private $urlToCurl = '';
	public function setUp():void{
        parent::setUp();
        $this->urlToCurl = env('APP_URL');
        $this->urlToCurl = preg_replace("/(\:[0-9]{2,4})$/","",$this->urlToCurl);
        $this->urlToCurl = preg_replace("/^https/","http",$this->urlToCurl);
        //echo "\n".$this->urlToCurl."\n";
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