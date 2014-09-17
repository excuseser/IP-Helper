<?php

  require_once "RestServer.php";
  require_once 'Ping.php';


  class Tools {

    public function methodList(){
      $outmsg['state'] = 1;
      $outmsg['ver'] = '1.0';
      $outmsg['method'] = array('ping', 'traceroute', 'port');
      exec("ping",$out, $return); 
      $outmsg['exec'] = $return;

      //$outmsg['urlImg'] = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data='.'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'].'';
      return $outmsg;
    }

    // example: http://path/to/tools.php?method=ping&host=8.8.4.4&ttl=255
    public function ping($host, $ttl){
      return $this->_ping($host, $ttl);
    }

    // example: http://path/to/tools.php?method=port&traceroute=8.8.4.4
    // not use
    public function traceroute($host){
      return 0;
    }

    // example: http://path/to/tools.php?method=port&host=8.8.4.4&ttl=80
    public function port($host, $port){
      return $this->_ping($host, 255, $port, 'fsockopen');
    }

    public function socket($host){
      return $this->_ping($host, 255, $port, 'socket');
    }


    private function _ping($host, $ttl=255, $port=80, $method=NULL){
      try {
        $ping = new Ping($host, $ttl);
        $ping->setPort($port);
        if (is_null($method)) {
          $latency = $ping->ping();
        }
        else{
          $latency = $ping->ping($method);
        }
        return $latency;

      } catch (Exception $e) {
        err('host is null');
      }
    }

    private function err($msg){
      $outmsg['state'] = 0;
      $outmsg['err'] = $msg;
      return $outmsg;
    }

  }



  $Tools = new Tools();

  if (!isset($_REQUEST['method'])){
    echo json_encode($Tools->methodList());
    die();
  }

  $rest = new RestServer();
  $rest->addServiceClass($Tools);
  $rest->handle();

?>
