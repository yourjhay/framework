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
         $model->fn = 'Jhay';
         $this->assertEquals($model->fn,'Jhay');
     }
}
