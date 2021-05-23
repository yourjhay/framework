<?php

class ModelTest extends \PHPUnit\Framework\TestCase
{
     public function testSetFillable()
     {
         $model = new \Simple\Tests\app\Model;
         $model->fn = 'Rey';
         $this->assertEquals($model->fn,'Rey');
     }

     public function testTrimFillable()
     {
         $model = new \Simple\Tests\app\Model;
         $model->fn = 'Jhay            ';
         $this->assertEquals($model->fn,'Jhay');
     }

     public function testQueryBuilderWhere()
     {
         $model = new \Simple\Tests\app\Model;
         $response = $model->where('id','6')->get();
         $response = $response[0];
         $this->assertIsArray($response);
         $this->assertArrayHasKey('id',$response);
         $this->assertArrayHasKey('email',$response);
         $this->assertArrayHasKey('name',$response);


         $this->assertEquals($response['email'], 'jhay@yahoo.com');
         $this->assertEquals($response['name'], 'Rey Jhon Baquirin');
         $this->assertEquals($response['id'], 6);

     }

     public function testQueryBuilderOnly()
     {
         $model = new \Simple\Tests\app\Model;
         $response = $model->only('6');
         $this->assertIsObject($response);
         $this->assertInstanceOf("\\Simple\\Tests\\app\\Model", $response);

         $response = $model->only('7');
         $this->assertIsBool($response);
     }
}
