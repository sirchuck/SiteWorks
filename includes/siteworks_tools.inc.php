<?php
namespace SiteWorks;
// Tools are loaded for each page on your site, they should be things that are used a lot.
// Examples might be login or authentication tools, time tools, and simple calculations

class siteworks_tools
{
  protected $_s;
  public $chr_control_noprint = array();

  function __construct($_s)
  {
    $this->_s =& $_s;

    $this->chr_control_noprint = array(
      // control characters
      chr(0), chr(1), chr(2), chr(3), chr(4), chr(5), chr(6), chr(7), chr(8), chr(9), chr(10),
      chr(11), chr(12), chr(13), chr(14), chr(15), chr(16), chr(17), chr(18), chr(19), chr(20),
      chr(21), chr(22), chr(23), chr(24), chr(25), chr(26), chr(27), chr(28), chr(29), chr(30),
      chr(31),
      // non-printing characters
      chr(127)
    );


  }

  public function trace($af=0,$fullreport=false){
    if($fullreport){
        $backtraceList = debug_backtrace();
        $returnList = Array();
        for ($i=0; $i<count($backtraceList); $i++) {
          $step = $backtraceList[$i];
          $stepFormatted = '';
          $omit = false;
          foreach ($step as $type => $value) {
            if ($type === 'file' && __FILE__ == $value) {
              $omit = true;
              break;
            }
            if (in_array($type, Array('file', 'line'))) {
              $stepFormatted .= '['.$value.']';
            }
          }
          if ($omit === false) {
            $returnList[] = $stepFormatted;
          }
        }
      return implode("\n", str_replace(SITEWORKS_DOCUMENT_ROOT.'/','',$returnList));
    }else{
      $dout = '';
      $data = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,5);
      foreach($data as $k => $v){ if( $k == $af || $af == 0 ){foreach($v as $k2 => $v2){ if($k2=="file"||$k2=="line"){$dout .= "\n" . '['.$k2.']'.' ' . str_replace(SITEWORKS_DOCUMENT_ROOT.'/','',$v2) . ' ';} } } }
      return $dout;
    }
  }
  public function sw_tracesql(){
      $dout = '';
      $data = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,5);
      foreach($data as $k => $v){
        $d_file = '';
        $d_line = '';
        foreach($v as $k2 => $v2){
          if($k2=="file"){
            $d_file = str_replace(SITEWORKS_DOCUMENT_ROOT.'/','',$v2);
          }
          if($k2=="line"){
            $d_line = $v2;
          }
        }
        if( strpos($d_file, 'private/')!==FALSE ){
          $dout .= "\n[ ". str_pad($d_line,5,' ',STR_PAD_LEFT) . ' ] '. str_replace(['private/','modules/'],'',$d_file);
        }
      }
      return $dout;
  }


  public function dmsg($s,$showArray=true,$showline=true){
    if($this->_s->debugMode){
      try{
        $fp=@fsockopen($this->_s->debug_server, $this->_s->debug_server_port, $errno, $errstr, 30);
        if(!$this->_s->allowDebugColors){
          $s_pattern = ['[c_clear]','[c_black]','[c_red]','[c_green]','[c_orange]','[c_blue]','[c_purple]','[c_cyan]','[c_light_gray]','[c_gray]','[c_light_red]','[c_light_green]','[c_yellow]','[c_light_blue]','[c_light_purple]','[c_light_cyan]','[c_white]'];
          $s = str_replace($s_pattern, '', $s);
        }
        $btrace = '';
        if($showArray===1){ $btrace = $this->trace(0,true); }else{ $btrace = ltrim($this->trace(1,false),"\n"); }
        if (!$fp) { $this->_s->console['_sw_dmsg'] = "You are calling dmsg() in debug mode, but your ./debug_server is not accessable. - $errstr ($errno)"; } else { fwrite($fp,(($showArray)?$btrace."-> ":'').print_r($s,true).(($showline)?"\n__________________________________________\n":'') . "\0"); fclose($fp); }
      } catch (Exception $e) {
         $this->_s->console[] = 'You are in debug mode, but your ./debug_server is not accessable. ' . $e->getMessage();
      }

    }
  }

  public function thread($path='',$Milliseconds=0,$vars=''){
    // This function works with php_threader
    if($vars != ''){$vars = ' -q=' . base64_encode(json_encode($vars));}
    exec('bash -c "'. SITEWORKS_DOCUMENT_ROOT.'/php_threader -x1='.$this->_s->thread_php_path.' -x2='.$this->_s->thread_php_version.' -s='.$Milliseconds.' -p=' . SITEWORKS_DOCUMENT_ROOT . '/private/thread_scripts/'.$path.'.php'.$vars.'> /dev/null 2>&1 &"');
  }

  public function queue($path='',$vars='',$tag='',$waitstart=0,$timeout=0){
    // This function works with php_q_it server
    if($vars != ''){$vars = base64_encode(json_encode($vars));}
    $r = new t_site_works_queue(null,$this->_s->odb);
    $r->f['sw_tag']['value'] = $tag;
    $r->f['sw_script']['value'] = SITEWORKS_DOCUMENT_ROOT . '/private/queue_scripts/'.$path.'.php';
    $r->f['sw_vars']['value'] = $vars;
    $r->f['sw_waitstart']['value'] = $waitstart;
    $r->f['sw_timeout']['value'] = $timeout;
    $r->insertData();
  }

  public function broadcast($sw_var='',$sw_action='',$uid='',$tag='',$uniqueid='',$host='',$port='',$sendhost='',$sendport=''){
    // php_websockets must be running and php_websockets_client must be in your project root folder.
    $host = ($host == '') ? $this->_s->websocket_server : $host;
    $port = ($port == '') ? $this->_s->websocket_port : $port;
    $shost = ($sendhost == '') ? '' : ' -sh="' . $sendhost . '"';
    $sport = ($sendport == '') ? '' : ' -sp="' . $sendport . '"';
    return exec('bash -c \''. SITEWORKS_DOCUMENT_ROOT.'/php_websockets_client -m="'.$sw_var.'" -a="'.$sw_action.'" -h="'.$host.'" -p="'.$port.'" -u="'.$uid.'" -t="'.$tag.'" -uq="'.$uniqueid.'"'.$shost.$sport.'\'');
  }


  public function exitRamvar(){ return $this->ramvar('','','','','sw_exit'); }
  public function clearRamvar(){ return $this->ramvar('','','','','sw_clearData'); }
  public function clearAllRamvar(){ return $this->ramvar('','','','','sw_clearAllData'); }
  public function syncRamvar(){ return $this->ramvar('','','','','sw_sync'); }
  public function fullsyncRamvar(){ return $this->ramvar('','','','','sw_fullsync'); }
  public function setRamvar($k='', $v='', $t=''){ return $this->ramvar('1',$k,$v,$t); }
  public function getRamvar($k='', $v='', $t=''){ return $this->ramvar('2',$k,$v,$t); }
  public function getOrRamvar($k='', $v='', $t=''){ return $this->ramvar('2.1',$k,$v,$t); }
  public function deleteRamvar($k='', $v='', $t=''){ return $this->ramvar('3',$k,$v,$t); }
  public function deleteOrRamvar($k='', $v='', $t=''){ return $this->ramvar('3.1',$k,$v,$t); }
  public function ramvar($a='', $k='', $v='', $t='', $m=false){
      $ret = '';
      try{
        if($this->_s->ramvar_cert_crt != '' && $this->_s->ramvar_cert_key != ''){
          $fp=@fsockopen("tls://".$this->_s->ramvar_local_server, $this->_s->ramvar_local_port, $errno, $errstr, 30);
        }else{
          $fp=@fsockopen($this->_s->ramvar_local_server, $this->_s->ramvar_local_port, $errno, $errstr, 30);
        }
        if (!$fp) {
          if($this->_s->debugMode){ $this->dmsg( 'Failed to connect to the local ramvar server.' ); }
        } else {
          $sc = new \stdClass;
          $sc->a = $a;
          $sc->k = $k;
          $sc->v = $v;
          $sc->t = $t;
          $tmp = fgets($fp);
          fwrite($fp, $this->_s->ramvar_app_key . "\n");
          if($m===false){
            fwrite($fp, json_encode($sc)."\n");
            $ret = fgets($fp);
            fclose($fp);
          }else{
            fwrite($fp, $m."\n");
            fclose($fp);
            $ret = '1';
          }
       }
      } catch (Exception $e) {
          if($this->_s->debugMode){ $this->dmsg( 'Failed to connect to the local ramvar server.' ); }
      }
      return $ret;
  }


  public function listFiles($dir,$ftype=0,$recursive=true,&$results=array()){
    // ftype( 0 all, 1 files only, 2 folders only )
    if(!is_dir($dir)){return false;}
    $files = scandir($dir);
    foreach($files as $key => $value){
        $path = realpath($dir);
        if(!is_dir($path.DIRECTORY_SEPARATOR.$value)) {
            if($ftype==0 || $ftype == 1){ $results[] = array('path'=>$path.DIRECTORY_SEPARATOR,'name'=>$value); }
        } else if( substr($value, 0, 1) != '.' ) {
          if($recursive){ $this->listFiles($path.DIRECTORY_SEPARATOR.$value,$ftype,$recursive,$results); }
          if($ftype==0 || $ftype == 2){$results[] = array('path'=>$path.DIRECTORY_SEPARATOR,'name'=>$value); }
        }
    }
    return $results;
  }

  public function addTrailSlash($s){ return rtrim($s,'/').'/';}
  public function delTree($dir,$include_dir_folder=true) {
   if(!is_dir($dir)){return false;}
   $dir = $this->addTrailSlash($dir);
   $files = array_diff(scandir($dir), array('.','..','.githoldfolderprivate'));
    foreach ($files as $file) {
      (is_dir($dir.$file)) ? $this->delTree($dir.$file,true) : unlink($dir.$file);
    }
    if($include_dir_folder){ return rmdir($dir); }
    return true;
  }

  public function removeFile($s){if(file_exists($s)){unlink($s);}}

  public function buildText($m,$r,$is_js=false){
    $pid = -1;
    foreach($r->p['list'] as $v){
      if($v->sw_origional == $m[2]){
        if($v->sw_lang_keep == 3){
          $pid = $r->updateData($v->sw_lang_key,'`sw_lang_keep`=0');
        }
        $v->sw_lang_keep = '2';
        $pid = $v->sw_lang_key;
      }
    }
    if($pid==-1){
      $r->f['sw_lang_key']['value'] = 0;
      $r->f['sw_lang_keep']['value'] = 0;
      $r->f['sw_lang_category']['value'] = '';
      $r->f['sw_origional']['value'] = $r->clean($m[2]);
      $r->f['english']['value'] = $r->f['sw_origional']['value'];
      $pid = $r->insertData();
      $r->p['list'][] = (object) array('sw_lang_key' => $pid, 'sw_lang_keep' => 2, 'sw_origional' => $m[2]);
    }
    switch($m[1]){
      case '.':
        return $m[1] . ' $this->_s->tool->getText('.$pid.') ';
      break;
      case '+':
        return $m[1] . ' getText('.$pid.') ';
      break;
      case '=':
      case '(':
        if($is_js){ return $m[1] . ' getText('.$pid.') '; } else { return $m[1] . ' $this->_tool->getText('.$pid.') '; }
      break;
      case '"':
      case "'":
        return $m[1] . '{__' . $pid . '__}';
      break;
      default:
        return $m[1] . ' {__' . $pid . '__}';
    }
  }

  public function getText($index, $language=false){
    $_SESSION['language'] = (isset($_SESSION['language']) && $_SESSION['language'] != '') ? $_SESSION['language'] :$this->_s->language;
    $language = ($language==false)? $_SESSION['language'] : $language;
    $holdSQL = $this->_s->printSQL;
    $this->_s->printSQL = false;
    $r = new t_site_works_lang(null,$this->_s->odb);
    $r->query('SELECT  `' . $language . '` as l FROM `site_works_lang` WHERE sw_lang_key = ' . $index);
    $row = $r->getRows();
    $this->_s->printSQL = $holdSQL;
    return (isset($row->l))?$row->l:'';
  }

  public function cleanHTML(&$va){ if(is_array($va)||is_object($va)){ foreach($va as &$v){ if(is_array($v)||is_object($v)){ $this->cleanHTML($v); }else{ $v=htmlspecialchars($v); } } }else{$va=htmlspecialchars($va);} }
  public function noHTML(&$va, $o = ENT_QUOTES | ENT_HTML5, $e = 'UTF-8'){ if(is_array($va)||is_object($va)){ foreach($va as &$v){ if(is_array($v)||is_object($v)){ $this->noHTML($v,$o,$e); }else{ $v=htmlentities($v,$o,$e); } } }else{$va=htmlentities($va,$o,$e);} }
  public function cleanH($va){ $this->cleanHTML($va); return $va; }
  public function noH($va, $o = ENT_QUOTES | ENT_HTML5, $e = 'UTF-8'){ $this->noHTML($va,$o,$e); return $va; }
  public function p_r($array = []){ echo '<pre>'; print_r ($array); echo '</pre>'; }

  public function iRnd($length, $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'){
    $pieces = [];
    $max = mb_strlen($keyspace, '8bit') - 1;
    for ($i = 0; $i < $length; ++$i) {
      $pieces []= $keyspace[random_int(0, $max)];
    }
    return implode('', $pieces);
  }

  // Always changing security
  public function iEncrypt($s, $secret_key = 'EuhriejirjijLL', $secret_iv = 'djkRKoejORjohgh', $encrypt_method = 'AES-256-CBC'){ return $this->encrypt_decrypt($s, 'encrypt', $secret_key, $secret_iv, $encrypt_method); }
  public function iDecrypt($s, $secret_key = 'EuhriejirjijLL', $secret_iv = 'djkRKoejORjohgh', $encrypt_method = 'AES-256-CBC'){ return $this->encrypt_decrypt($s, 'decrypt', $secret_key, $secret_iv, $encrypt_method); }
  public function encrypt_decrypt($s,$a, $secret_key = 'EuhriejirjijLL', $secret_iv = 'djkRKoejORjohgh', $encrypt_method = 'AES-256-CBC'){
    $output = false;
    $key = hash('sha256', $secret_key);
    // iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
    $iv = substr(hash('sha256', $secret_iv), 0, 16);
    if ( $a == 'encrypt' ) {
        $output = openssl_encrypt($s, $encrypt_method, $key, 0, $iv);
    } else if( $a == 'decrypt' ) {
        $output = openssl_decrypt($s, $encrypt_method, $key, 0, $iv);
    }
    return $output;
  }

  // PHP Sodium encryption wrapper
  public function sodium_encrypt($m,$n=false,$k=false){ return sodium_crypto_secretbox($m, $n==false?$this->_s->sodium_nonce:$n, $k==false?$this->_s->sodium_key:$k); }
  public function sodium_decrypt($m,$n=false,$k=false){ return sodium_crypto_secretbox_open($m, $n==false?$this->_s->sodium_nonce:$n, $k==false?$this->_s->sodium_key:$k); }
  public function sodium_check($c,$m,$n=false,$k=false){ if($this->sodium_encrypt($m,$n==false?$this->_s->sodium_nonce:$n, $k==false?$this->_s->sodium_key:$k)===$c){return true;}return false;}
  public function sodium_create_key(){ return sodium_crypto_secretbox_keygen(); } // All sorts of keygen methods. random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
  public function sodium_create_nonce(){ return random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES); }
  public function sodium_create_files($p1=false,$p2=false){
    if( $p1 && !file_exists($p1) ){ $f=fopen($p1,"w"); if($f){ fwrite( $f, $this->sodium_create_key() ); fclose($f); } }
    if( $p2 && !file_exists($p2) ){ $f=fopen($p2,"w"); if($f){ fwrite( $f, $this->sodium_create_nonce() ); fclose($f); } }
  }
  public function sodium_read_key($p=false){
    $p = $p?$p:$this->_s->sodium_key_file;
    if(file_exists($p)){$k = file_get_contents($p); $this->_s->sodium_key = $k; return $k;} return false;
  }
  public function sodium_read_nonce($p=false){
    $p = $p?$p:$this->_s->sodium_nonce_file;
    if(file_exists($p)){$n = file_get_contents($p); $this->_s->sodium_nonce = $n; return $n;} return false;
  }
  public function sodium_read_files($p1=false,$p2=false){ $this->sodium_read_key($p1); $this->sodium_read_nonce($p2); }



  public function get_c($v=null,$v2=null){ return $this->vc($v,$v2,$_GET); }
  public function post_c($v=null,$v2=null){ return $this->vc($v,$v2,$_POST); }
  public function request_c($v=null,$v2=null){ return $this->vc($v,$v2,$_REQUEST); }
  public function vc($v=null,$v2=null,$a=null){
    if( is_null($a) ){
      if( is_null($v2) ){ return ( !is_null($v) && $v != '' ) ? true : false; } else { return ( !is_null($v) && $v != '' && $v == $v2 ) ? true : false; }
    } else {
      if( is_null($v2) ){ return ( isset($a[$v]) && !is_null($a[$v]) && $a[$v] != '' ) ? true : false; } else { return ( isset($a[$v]) && $a[$v] == $v2 ) ? true : false; }
    }
    return false;
  }

  // Clean special control and non-printing characters, specifically for binary encrypted sql
  public function chr_c($s,$s2="*"){ return str_replace($this->chr_control_noprint, $s2, $s); }

  // Some Curl
  public function curl_post($u=null,$p=array(),$h=false,$fp=null){return $this->curl($u,$p,$h,'POST',$fp);}
  public function curl_put($u=null,$p=array(),$h=false,$fp=null){return $this->curl($u,$p,$h,'PUT',$fp);}
  public function curl_get($u=null,$p=array()){return $this->curl($u,$p,false,'GET');}
  public function curl_delete($u=null,$p=array(),$h=false){return $this->curl($u,$p,$h,'DELETE');}
  public function curl_patch($u=null,$p=array(),$h=false){return $this->curl($u,$p,$h,'PATCH');}
  public function curl($url=null,$postvars=array(),$headers=false,$sendtype=null,$fp=null){
    $ch = curl_init(); $sendtype = strtoupper($sendtype);
    if($sendtype != 'GET'){
      // Headers should be array('X-Header1: value','Header2: value2');
      if( $headers !== false ){ curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); }
      // Postvars should be array('key1'=>'value1','key2'='value2');
      if( count($postvars) > 0 ){ curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postvars)); }
    }
    switch ($sendtype) {
      case 'POST': // Create a resourse
          if(!is_null($fp)){ $postvars=array('file_contents'=>'@'.$fp); }
          curl_setopt($ch, CURLOPT_POST, count($postvars));
          break;
      case 'PUT': // Update a resourse
          curl_setopt($ch, CURLOPT_PUT, 1);
          if(!is_null($fp)){ curl_setopt($ch, CURLOPT_INFILE, fopen($fp, 'r')); curl_setopt($ch, CURLOPT_INFILESIZE, filesize($fp)); }
          break;
      case 'GET': // Retrieve information from a resourse
          if( count($postvars) > 0 ){ $url .= '?' . http_build_query($postvars); }
          curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
          curl_setopt($ch, CURLOPT_HTTPGET, 1);
          break;
      case 'DELETE': // Delete a resourse
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE'); 
          break;
      case 'PATCH': // Update part of a resoruse
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
          break;
      default:
          curl_setopt($ch, CURLOPT_POST, 1);
          break;
    }
    curl_setopt($ch, CURLOPT_URL,$url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = ['body'=>'','header'=>array()];
    curl_setopt($ch, CURLOPT_HEADERFUNCTION, function($curl, $header) use (&$response){
        $len = strlen($header);
        $header = explode(':', $header, 2);
        if (count($header) < 2){ return $len; }
        $response['header'][strtolower(trim($header[0]))] = trim($header[1]);
        return $len;
    });
    $response['body'] = curl_exec ($ch); curl_close ($ch); return $response;
  }
  public function enable_cors($allow_methods=false,$allow_headers=false,$origin=false,$content_type=false,$max_age=false){
    $origin = ($origin === false) ? '*' : $origin;
    $allow_methods = ($allow_methods === false) ? 'OPTIONS,GET,POST,PUT,DELETE,PATCH' : $allow_methods;
    $allow_headers = ($allow_headers === false) ? 'DNT,User-Agent,Origin,X-Requested-With,If-Modified-Since,Cache-Control,Content-Type,Range' : $allow_headers;
    $content_type = ($content_type === false) ? 'application/json; charset=UTF-8' : $content_type;
    $max_age = ($max_age === false) ? '3600' : $max_age;
    header('Access-Control-Allow-Origin: '       . $origin);
    header('Content-Type: '                      . $content_type);
    header('Access-Control-Allow-Methods: '      . $allow_methods);
    header('Access-Control-Max-Age: '            . $max_age);
    header('Access-Control-Allow-Headers: '      . $allow_headers);
  }
  public function respond($hn=404,$ec='',$hm=array()){
    switch ($hn) {
        case 100: header('HTTP/1.1 100 Continue'); break;
        case 101: header('HTTP/1.1 101 Switching Protocols'); break;
        case 102: header('HTTP/1.1 102 Processing'); break;
        case 103: header('HTTP/1.1 103 Early Hints'); break;

        case 200: header('HTTP/1.1 200 OK'); break;
        case 201: header('HTTP/1.1 201 Created'); break;
        case 202: header('HTTP/1.1 202 Accepted'); break;
        case 203: header('HTTP/1.1 203 Non-Authoritative Information'); break;
        case 204: header('HTTP/1.1 204 No Content'); break;
        case 205: header('HTTP/1.1 205 Reset Content'); break;
        case 206: header('HTTP/1.1 206 Partial Content'); break;
        case 207: header('HTTP/1.1 207 Multi-Status'); break;
        case 208: header('HTTP/1.1 208 Already Reported'); break;
        case 226: header('HTTP/1.1 226 IM Used'); break;

        case 300: header("HTTP/1.1 300 Multiple Choices"); break;
        case 301: header('HTTP/1.1 301 Moved Permanently'); break;
        case 302: header('HTTP/1.1 302 Found'); break;
        case 303: header('HTTP/1.1 303 See Other'); break;
        case 304: header('HTTP/1.1 304 Not Modified'); break;
        case 305: header('HTTP/1.1 305 Use Proxy'); break;
        case 306: header('HTTP/1.1 306 Switch Proxy'); break;
        case 307: header('HTTP/1.1 307 Temporary Redirect'); break;
        case 308: header('HTTP/1.1 308 Permanent Redirect'); break;

        case 400: header('HTTP/1.1 400 Bad Request'); break;
        case 401: header('HTTP/1.1 401 Unauthorized'); break;
        case 402: header('HTTP/1.1 402 Payment Required'); break;
        case 403: header('HTTP/1.1 403 Forbidden'); break;
        case 404: header('HTTP/1.1 404 Not Found'); break;
        case 405: header('HTTP/1.1 405 Method Not Allowed'); break;
        case 406: header('HTTP/1.1 406 Not Acceptable'); break;
        case 407: header('HTTP/1.1 407 Proxy Authentication Required'); break;
        case 408: header('HTTP/1.1 408 Request Timeout'); break;
        case 409: header('HTTP/1.1 409 Conflict'); break;
        case 410: header('HTTP/1.1 410 Gone'); break;
        case 411: header('HTTP/1.1 411 Length Required'); break;
        case 412: header('HTTP/1.1 412 Precondition Failed'); break;
        case 413: header('HTTP/1.1 413 Payload Too Large'); break;
        case 414: header('HTTP/1.1 414 URI Too Long'); break;
        case 415: header('HTTP/1.1 415 Unsupported Media Type'); break;
        case 416: header('HTTP/1.1 416 Range Not Satisfiable'); break;
        case 417: header('HTTP/1.1 417 Expectation Failed'); break;
        case 418: header("HTTP/1.1 418 I'm a teapot"); break;

        case 421: header('HTTP/1.1 421 Misdirected Request'); break;
        case 422: header('HTTP/1.1 422 Unprocessable Entity'); break;
        case 423: header('HTTP/1.1 423 Locked'); break;
        case 424: header('HTTP/1.1 424 Failed Dependency'); break;
        case 425: header('HTTP/1.1 425 Too Early'); break;
        case 426: header('HTTP/1.1 426 Upgrade Required'); break;

        case 428: header('HTTP/1.1 428 Precondition Required'); break;
        case 429: header('HTTP/1.1 429 Too Many Requests'); break;

        case 431: header('HTTP/1.1 431 Request Header Fields Too Large'); break;
        case 451: header('HTTP/1.1 451 Unavailable For Legal Reasons'); break;

        case 500: header('HTTP/1.1 500 Internal Server Error'); break;
        case 501: header('HTTP/1.1 501 Not Implemented'); break;
        case 502: header('HTTP/1.1 502 Bad Gateway'); break;
        case 503: header('HTTP/1.1 503 Service Unavailable'); break;
        case 504: header('HTTP/1.1 504 Gateway Timeout'); break;
        case 505: header('HTTP/1.1 505 HTTP Version Not Supported'); break;
        case 506: header('HTTP/1.1 506 Variant Also Negotiates'); break;
        case 507: header('HTTP/1.1 507 Insufficient Storage'); break;
        case 508: header('HTTP/1.1 508 Loop Detected'); break;
        case 510: header('HTTP/1.1 510 Not Extended'); break;
        case 511: header('HTTP/1.1 511 Network Authentication Required'); break;

        default: header("HTTP/1.0 404 Not Found");
    }
    foreach($hm as $v){ header($v); }
    echo $ec;
  }


}
?>