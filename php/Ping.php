<?php
error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);
/**
 * Ping for PHP.
 *
 * This class pings a host.
 *
 * The ping() method pings a server using 'exec', 'socket', or 'fsockopen', and
 * and returns FALSE if the server is unreachable within the given ttl/timeout,
 * or the latency in milliseconds if the server is reachable.
 *
 * Quick Start:
 * @code
 *   include 'path/to/Ping/JJG/Ping.php';
 *   use \JJG\Ping as Ping;
 *   $ping = new Ping('www.example.com');
 *   $latency = $ping->ping();
 * @endcode
 *
 * @version 1.0.0
 * @author Jeff Geerling.
 */

class Ping {

  private $host;
  private $ttl;
  private $port = 80;
  private $data = 'Ping';

  /**
   * Called when the Ping object is created.
   *
   * @param string $host
   *   The host to be pinged.
   * @param int $ttl
   *   Time-to-live (TTL) (You may get a 'Time to live exceeded' error if this
   *   value is set too low. The TTL value indicates the scope or range in which
   *   a packet may be forwarded. By convention:
   *     - 0 = same host
   *     - 1 = same subnet
   *     - 32 = same site
   *     - 64 = same region
   *     - 128 = same continent
   *     - 255 = unrestricted
   *   The TTL is also used as a general 'timeout' value for fsockopen(), so if
   *   you are using that method, you might want to set a default of 5-10 sec to
   *   avoid blocking network connections.
   */
  public function __construct($host, $ttl = 255) {
    if (!isset($host)) {
      throw new Exception("Error: Host name not supplied.");
    }

    $this->host = $host;
    $this->ttl = $ttl;
  }

  /**
   * Set the ttl (in hops).
   *
   * @param int $ttl
   *   TTL in hops.
   */
  public function setTtl($ttl) {
    $this->ttl = $ttl;
    print_r($this->ttl);
  }

  /**
   * Set the host.
   *
   * @param string $host
   *   Host name or IP address.
   */
  public function setHost($host) {
    $this->host = $host;
  }

  /**
   * Set the port (only used for fsockopen method).
   *
   * Since regular pings use ICMP and don't need to worry about the concept of
   * 'ports', this is only used for the fsockopen method, which pings servers by
   * checking port 80 (by default).
   *
   * @param int $port
   *   Port to use for fsockopen ping (defaults to 80 if not set).
   */
  public function setPort($port) {
    $this->port = $port;
  }

  /**
   * Ping a host.
   *
   * @param string $method
   *   Method to use when pinging:
   *     - exec (default): Pings through the system ping command. Fast and
   *       robust, but a security risk if you pass through user-submitted data.
   *     - fsockopen: Pings a server on port 80.
   *     - socket: Creates a RAW network socket. Only usable in some
   *       environments, as creating a SOCK_RAW socket requires root privileges.
   *
   * @return mixed
   *   Latency as integer, in ms, if host is reachable or FALSE if host is down.
   */
  public function ping($method = 'exec') {
    $latency = false;

    $pingRet['ip'] = '';
    $pingRet['ttl'] = false;
    $pingRet['time'] = false;
    $pingRet['rtl'] = false;
    $pingRet['err'] = 0;

    try {
      switch ($method) {
      // The exec method uses the possibly insecure exec() function, which
      // passes the input to the system. This is potentially VERY dangerous if
      // you pass in any user-submitted data. Be SURE you sanitize your inputs!
      case 'exec':
        $ttl = escapeshellcmd($this->ttl);
        $host = escapeshellcmd($this->host);
        // Exec string for Windows-based systems.
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
          // -n = number of pings; -i = ttl.
          $exec_string = 'ping -n 1 -l 56 -i ' . $ttl . ' ' . $host;
          $host_type = 'windows';
          $result_line = 1;
          $ip_param = 2;
          $time_param = 4;
          $ttl_param = 5;
        }
        // Exec string for UNIX-based systems (Mac, Linux).
        else {
          // -n = numeric output; -c = number of pings; -t = ttl.
          //eng chn 
          $exec_string = 'ping -n -c 1 -t ' . $ttl . ' ' . $host;
          $host_type = 'unix';
          $result_line = 1;
          $time_param = 6;
          $ttl_param = 5;
        }

        $str = exec($exec_string, $output, $return);
        // If the result line in the output is not empty, parse it.
        $output = array_filter($output);
        $output = array_values($output);

        $flag1= false;
        $flag2= false;

        //"100%" string means lost
        foreach($output as $str){
          if (strstr($str, "100%")) {
            $flag1 = true;
          }
          if (strstr($str, "live")) {
            $flag2 = true;
          }
          if (!$flag2 && $flag1) {
            $pingRet['err'] = 2;
            $latency = false;
            break;
          }
        }



        //print_r($output);
        if (!empty($output[$result_line])) {
          $array = explode(' ', $output[$result_line]);
          // If the time parameter is missing, the host is unreachable.
          if (!isset($array[$time_param])) {
            $latency = false;
          }
          else {
            // If on a windows machine, also remove the 'ms'.
            if ($host_type == 'windows') {
              $latency = mystr($array[$time_param], '=', 'ms');
              if (!$latency) {
                $tmp = mystr($array[$time_param], '<', 'ms');
                if (is_numeric($tmp)) $latency = $tmp - 1;
              }
            }
            else{
              // Remove 'time=' from latency stat.
              $tmp = str_replace('time=', '', $array[$time_param]);
              if (is_numeric($tmp)) $latency = $tmp;
            }
            // Convert latency to microseconds.
            //$latency = round($latency);
          }

          if (!isset($array[$ttl_param])) {
            $ttlret = false;
          }
          else {
            // Remove 'time=' from latency stat.
            $ttlret = false;
            $tmp = str_ireplace('TTL=', '', $array[$ttl_param]);
            if (is_numeric($tmp)) $ttlret = $tmp;
          }


          $ipret = findIP($output[$result_line]);

          $pingRet['ip'] = $ipret;
          $pingRet['ttl'] = floatval($ttlret);
          $pingRet['time'] = floatval($latency);
          $pingRet['rtl'] = $output[$result_line];          
        }
        else {
          $pingRet['err'] = 1;
          $latency = false;
        }

        break;

      // The fsockopen method simply tries to reach the host on a port. This
      // method is often the fastest, but not necessarily the most reliable.
      // Even if a host doesn't respond, fsockopen may still make a connection.
      case 'fsockopen':
        $outline = '';
        $start = microtime(true);
        $fp = fsockopen($this->host, $this->port, $errno, $errstr, 10);
        //if fsockopen or pfsockopen method can't be use
        //$fp = stream_socket_client("tcp://".$this->host.":".$this->port, $errno, $errstr, 10);
        if (!$fp) {
          $latency = false;
          $pingRet['err'] = 2;//not connect the host
        }
        else {
          if ($this->port == 80) {
            $out = "GET / HTTP/1.1\r\n";
              $out .= "Host: www.example.com\r\n";
              $out .= "Connection: Close\r\n\r\n";
              fwrite($fp, $out);
              while (!feof($fp)) {
                  $outline = $outline . fgets($fp, 128);
              }
              fclose($fp);
          }
        $latency = microtime(true) - $start;
        $latency = round($latency * 1000);
        $pingRet['ip'] = gethostbynamel($this->host);
        $pingRet['time'] = floatval($latency);
        $pingRet['rtl'] = $outline;
        }

        break;

      // The socket method uses raw network packet data to try sending an ICMP
      // ping packet to a server, then measures the response time. Using this
      // method requires the script to be run with root privileges, though, so
      // this method only works reliably on Windows systems and on Linux servers
      // where the script is not being run as a web user.
      case 'socket':
        // Create a package.
        $type = "\x08";
        $code = "\x00";
        $checksum = "\x00\x00";
        $identifier = "\x00\x00";
        $seq_number = "\x00\x00";
        $package = $type . $code . $checksum . $identifier . $seq_number . $this->data;

        // Calculate the checksum.
        $checksum = $this->calculateChecksum($package);

        // Finalize the package.
        $package = $type . $code . $checksum . $identifier . $seq_number . $this->data;

        // Create a socket, connect to server, then read socket and calculate.
        if ($socket = socket_create(AF_INET, SOCK_RAW, 1)) {
          socket_connect($socket, $this->host, null);
          $start = microtime(true);
          // Send the package.
          socket_send($socket, $package, strlen($package), 0);
          if (socket_read($socket, 255) !== false) {
            $latency = microtime(true) - $start;
            $latency = round($latency * 1000);
          }
          else {
            $latency = false;
          }
          // Close the socket.
          socket_close($socket);
        }
        else {
          $latency = false;
        }
        // Close the socket.
        socket_close($socket);
        break;
        $pingRet['time'] = floatval($latency);
      }
    } catch (Exception $e) {
      $pingRet['err'] = 1;
    }

    // Return the latency.
    return $pingRet;
  }

  /**
   * Calculate a checksum.
   *
   * @param string $data
   *   Data for which checksum will be calculated.
   *
   * @return string
   *   Binary string checksum of $data.
   */
  private function calculateChecksum($data) {
    if (strlen($data) % 2) {
      $data .= "\x00";
    }

    $bit = unpack('n*', $data);
    $sum = array_sum($bit);

    while ($sum >> 16) {
      $sum = ($sum >> 16) + ($sum & 0xffff);
    }

    return pack('n*', ~$sum);
  }
}


function mystr($string,$search1,$search2) {
  $tempstr= ltrim(strstr($string,$search1),$search1);
  $i= stripos($tempstr,$search2);
  return substr($tempstr,0,$i);
}


function findIP($str=''){
  $str=trim($str);
  if(empty($str)){return '';}
  $reg= '/(\d+)\.(\d+)\.(\d+)\.(\d+)/';
  preg_match_all($reg,$str,$result);
  if(is_array($result)&&!empty($result)&&!empty($result[1])&&!empty($result[1][0])){
    return $result[0][0];
  }
  return '';
}


?>