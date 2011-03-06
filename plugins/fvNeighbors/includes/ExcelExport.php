<?php
class ExcelExport {
   var $file;
   var $row;

   function ExcelExport() {
      $this->file = $this->__BOF();
      $row = 0;
   }

   function __BOF() {
       return pack("ssssss", 0x809, 0x8, 0x0, 0x10, 0x0, 0x0);
   }

   function __EOF() {
       return pack("ss", 0x0A, 0x00);
   }

   function __writeNum($row, $col, $value) {
       $this->file .= pack("sssss", 0x203, 14, $row, $col, 0x0);
       $this->file .= pack("d", $value);
   }

   function __writeString($row, $col, $value ) {
       $L = strlen($value);
       $this->file .= pack("ssssss", 0x204, 8 + $L, $row, $col, 0x0, $L);
       $this->file .= $value;
   }
   
   function writeCell($value,$row,$col) {
      if(is_numeric($value)) {
         $this->__writeNum($row,$col,$value);
      }elseif(is_string($value)) {
         $this->__writeString($row,$col,$value);
      }
   }
   
   function addRow($data,$row=null) {
      //If the user doesn't specify a row, use the internal counter.
      if(!isset($row)) {
         $row = $this->row;
         $this->row++;
      }
      for($i = 0; $i<count($data); $i++) {
         $cell = $data[$i];
         $this->writeCell($cell,$row,$i);
      }
   }

   function download($filename) {
      $this->write($filename);
   }
   
   function write($filename) {
      $http_headers = "Pragma: public\r\n";
      $http_headers .= "Expires: 0\r\n";
      $http_headers .= "Cache-Control: must-revalidate, post-check=0, pre-check=0\r\n";
      $http_headers .= "Content-Type: application/force-download\r\n";
      $http_headers .= "Content-Type: application/octet-stream\r\n";
      $http_headers .= "Content-Type: application/download\n";
      $http_headers .= "Content-Disposition: attachment;filename=$filename \r\n";
      $http_headers .= "Content-Transfer-Encoding: binary \r\n";
	$GLOBALS['http_headers'] = $http_headers;
      echo $file = $this->file.$this->__EOF();
   }
}
