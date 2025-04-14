<?php
 ini_set('display_errors', 0);
 error_reporting(E_ALL);
 

 // Tratamento de exceções
 set_exception_handler(function($e) {
  error_log("Exceção: " . $e->getMessage());
  // Mensagem genérica para o usuário, se necessário
 });
 

 // Tratamento de erros
 set_error_handler(function($errno, $errstr, $errfile, $errline) {
  error_log("Erro [$errno] em $errfile na linha $errline: $errstr");
  // Impede que erros sejam exibidos ao usuário
  return true;
 });
 ?>