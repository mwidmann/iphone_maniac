<?php

    require_once "settings.php";

    function showForum($id) {
        $request = new HTTPRequest(BOARD_URL . $id);
        $relevant_content = $request->DownloadToString();
        $relevant_content = substr($relevant_content, stripos($relevant_content, '<tr bgcolor="#7dacac">'));
        // explode to the threads
        $threads = explode('</tr>', $relevant_content);

        // remove the last two elements
        array_splice($threads, -2);

        $threads = array_map(function($thread) {
            $is_open = stripos($thread, 'img/open.png') > 0;
            $is_fixed = stripos($thread, 'img/fixed.gif') > 0;
            if ($is_fixed) {
                $is_open = true;
            }
            if (preg_match('/<font size="2">(.*)<\/font>/m', $thread, $matches)) {
                $title = $matches[1];
            }
            if (preg_match('/von (<font size="2" color="#FFC343">)?<b>(.*)<\/b>/m', $thread, $matches)) {
                $author = $matches[2];
                $is_mod = !empty($matches[1]);
            }

            if (preg_match('/Antworten: (\d+)/m', $thread, $matches)) {
                $replies = $matches[1];
            }
            if (preg_match('/ld\((\d+)\)/m', $thread, $matches)) {
                $thread_id = $matches[1];
            }
            if (preg_match('/letzte Antwort: (\d{2}.\d{2}.\d{4} \d{2}:\d{2})/m', $thread, $matches)) {
                $last_reply = $matches[1];
            }
            return [
                'is_fixed' => $is_fixed,
                'is_open' => $is_open,
                'thread_id' => $thread_id,
                'title' => $title,
                'author' => $author,
                'replies' => $replies,
                'is_mod' => $is_mod,
                'last_reply' => $last_reply,
             ];
        }, $threads);

        $counter = 0;
        $transformed_threads = array_map(function($thread) use (&$counter, $id){
            $thread_line .= "<li";
            $odd = "";
            if ($counter % 2 == 1) $odd = " rowodd";
            if ($thread['is_fixed'])
                $thread_line .= " class=\"highlight{$odd}\"";
            if (!$thread['is_open'])
                $thread_line .= " class=\"closed{$odd}\"";
            else
            	$thread_line .= " class=\"{$odd}\"";

            $thread_line .= ">\n";
            $thread_line .= "<a href=\"thread.php?id=$id&thread={$thread['thread_id']}&closed=". ($thread['is_open'] ? '0':'1') . "\">";
            $thread_line .= "<div class=\"title\">{$thread['title']}</div>\n";
            $thread_line .= "<div class=\"info\">Author: {$thread['author']}, {$thread['replies']} Antworten, zuletzt: {$thread['last_reply']}</div>";
            $thread_line .= "</a>";
            $thread_line .= "</li>\n";
            $counter ++;
            return $thread_line;
        }, $threads);

        return utf8_encode(implode("\n", $transformed_threads));
    }

    function showThread($board_id, $thread_id) {
        $request = new HTTPRequest(THREAD_URL . $thread_id . "&brdid=" . $board_id);
        $relevant_content = $request->DownloadToString();

        // delete everything before the first UL
        $relevant_content = substr($relevant_content, stripos($relevant_content, "<ul"));
        $relevant_content = substr($relevant_content, 0, strripos($relevant_content, "</ul>") + 5);
        $relevant_content = preg_replace("/\<img src=\"images\/arr_off.gif\" name=\"p(\d+)\"\/\>/i", "", $relevant_content);
        $relevant_content = str_replace("<span class=\"\">", "", $relevant_content);
        $relevant_content = str_replace("</span>", "", $relevant_content);

        $relevant_content = preg_replace("/\<font size=\"2\"\>/i", "", $relevant_content);
        $relevant_content = preg_replace("/ - \<font size=\"1\"\>/i", "<div class=\"author\">", $relevant_content);
        $relevant_content = preg_replace("/\<\/font\>/i", "", $relevant_content);
        $relevant_content = str_replace("</li>", "</div>\n</li>", $relevant_content);

        if (isset($_COOKIE['man_user']) && $_COOKIE['man_user'] != '') {
            $pattern = "/\s*" . $_COOKIE['man_user'] . "\s*/i";
            $relevant_content = preg_replace($pattern, "<span class=\"currentuser\">".$_COOKIE['man_user']."</span>", $relevant_content);
        }

        $relevant_content = preg_replace("/ href=\"pxmboard.php\?mode=message\&brdid=(\d+)\&msgid=(\d+)\" target=\"bottom\" onclick=\"cp\(\'p(\d+)\'\)\" name=\"p/i", " type=\"messagedetail\" href=\"message.php?id=$board_id&thread=$thread_id&message=", $relevant_content);
        $relevant_content = preg_replace("/ href=\"pxmboard.php\?mode=message\&amp;brdid=(\d+)\&amp;msgid=(\d+)\" target=\"bottom\" onclick=\"cp\(\'p(\d+)\'\)\" name=\"p/i", " type=\"messagedetail\" href=\"message.php?id=$board_id&thread=$thread_id&message=",  $relevant_content);

        // return utf8_encode($relevant_content);
        return ($relevant_content);
    }

    function showMessage($board_id, $thread_id, $message_id) {
        $request = new HTTPRequest(MESSAGE_URL . $message_id . "&brdid=" . $board_id);
        $relevant_content = $request->DownloadToString();

        $relevant_content = substr($relevant_content, stripos($relevant_content, "<td id=\"norm\"><b>") + 17);
        $title = substr($relevant_content, 0, stripos($relevant_content, "</b>"));

        $relevant_content = substr($relevant_content, stripos($relevant_content, "&usrid=") + 7);
        $userid = substr($relevant_content, 0, stripos($relevant_content, "\""));

        $relevant_content = substr($relevant_content, stripos($relevant_content, "hideLayer()") + 13);
        $username = substr($relevant_content, 0, stripos($relevant_content, "</a>"));

        $relevant_content = substr($relevant_content, stripos($relevant_content, "<font face=\"Courier New\">") + 25);
        $relevant_content = substr($relevant_content, 0, stripos($relevant_content, "</td>"));

        $relevant_content = substr($relevant_content, 0, strripos($relevant_content, "</font>"));

        //$relevant_content = preg_replace("#\[<a\b[^>]*>(.*?[gif|png|jpg])</a>\]#i", '<a href="\1" target="_new"><img src="\1" style="max-width:300px" border="0"/></a>', $relevant_content);

        $matches = array();
        preg_match_all( '/\[<a [^>]*>(.*?)<\/a>\]/im', $relevant_content, $matches );
        if ( is_array( $matches ) ) {
            for ( $i = 0; $i < count( $matches[0] ); $i++ ) {
                if ( endsWith( strtolower( $matches[1][$i] ), "jpg" ) || endsWith( strtolower( $matches[1][$i] ), "png" ) || endsWith( strtolower( $matches[1][$i] ), "jpg" ) ) {
                    $relevant_content = str_replace( $matches[0][$i], '<a href="' . $matches[1][$i] . '" target="_new"><img src="' . $matches[1][$i] . '" style="max-width:300px" border="0"/></a>', $relevant_content );
                }
            }
        }


        $relevant_content = "<div class=\"entry\">$relevant_content</div>";
        $relevant_content = "<div class=\"title\">$title</div>" . $relevant_content;
        if (!isset($_COOKIE['man_paranoia']) || $_COOKIE['man_paranoia'] == 1 || JUST_PARANOIA) {
            $relevant_content = "<div class=\"reply\"><a href=\"".PARANOIA_URL."$message_id&brdid=$board_id\" target=\"_blank\" onclick=\"trackPageView('board:$board_id:thread:$thread_id:message:$message_id:paranoia');\">Antworten (Paranoia modus)</a></div>" . $relevant_content;
        } else {
            $relevant_content = "<div class=\"reply\"><a href=\"#post\" onclick=\"fillForm(this,$board_id,$message_id);document.getElementById('post').style.top=this.offsetTop - 28;\">Antworten (Inline modus)</a></div>" . $relevant_content;
        }
        $relevant_content = "<div class=\"author\">$username</div>" . $relevant_content;

        // return utf8_encode($relevant_content);
        return ($relevant_content);
    }

if(!function_exists("stripos")){
    function stripos(  $str, $needle, $offset = 0  ){
        return strpos(  strtolower( $str ), strtolower( $needle ), $offset  );
    }/* endfunction stripos */
}/* endfunction exists stripos */

if(!function_exists("strripos")){
    function strripos(  $haystack, $needle, $offset = 0  ) {
        if(  !is_string( $needle )  )$needle = chr(  intval( $needle )  );
        if(  $offset < 0  ){
            $temp_cut = strrev(  substr( $haystack, 0, abs($offset) )  );
        }
        else{
            $temp_cut = strrev(    substr(   $haystack, 0, max(  ( strlen($haystack) - $offset ), 0  )   )    );
        }
        if(   (  $found = stripos( $temp_cut, strrev($needle) )  ) === FALSE   )return FALSE;
        $pos = (   strlen(  $haystack  ) - (  $found + $offset + strlen( $needle )  )   );
        return $pos;
    }/* endfunction strripos */
}/* endfunction exists strripos */

function startsWith($haystack,$needle,$case=true)
{
   if($case)
       return strpos($haystack, $needle, 0) === 0;

   return stripos($haystack, $needle, 0) === 0;
}

function endsWith($haystack,$needle,$case=true)
{
  $expectedPosition = strlen($haystack) - strlen($needle);

  if($case)
      return strrpos($haystack, $needle, 0) === $expectedPosition;

  return strripos($haystack, $needle, 0) === $expectedPosition;
}


class HTTPRequest
{
    var $_fp;        // HTTP socket
    var $_url;        // full URL
    var $_host;        // HTTP host
    var $_protocol;    // protocol (HTTP/HTTPS)
    var $_uri;        // request URI
    var $_port;        // port

    // scan url
    function _scan_url()
    {
        $req = $this->_url;

        $pos = strpos($req, '://');
        $this->_protocol = strtolower(substr($req, 0, $pos));

        $req = substr($req, $pos+3);
        $pos = strpos($req, '/');
        if($pos === false)
            $pos = strlen($req);
        $host = substr($req, 0, $pos);

        if(strpos($host, ':') !== false)
        {
            list($this->_host, $this->_port) = explode(':', $host);
        }
        else
        {
            $this->_host = $host;
            $this->_port = ($this->_protocol == 'https') ? 443 : 80;
        }

        $this->_uri = substr($req, $pos);
        if($this->_uri == '')
            $this->_uri = '/';
    }

    // constructor
    function HTTPRequest($url)
    {
        $this->_url = $url;
        $this->_scan_url();
    }

    // download URL to string
    function DownloadToString()
    {
        $crlf = "\r\n";

        // generate request
        $req = 'GET ' . $this->_uri . ' HTTP/1.0' . $crlf
            .    'Host: ' . $this->_host . $crlf
            .    $crlf;

        $response ="";
        // fetch
        $this->_fp = fsockopen(($this->_protocol == 'https' ? 'ssl://' : '') . $this->_host, $this->_port);
        fwrite($this->_fp, $req);
        while(is_resource($this->_fp) && $this->_fp && !feof($this->_fp))
            $response .= fread($this->_fp, 1024);
        fclose($this->_fp);

        // split header and body
        $pos = strpos($response, $crlf . $crlf);
        if($pos === false)
            return($response);
        $header = substr($response, 0, $pos);
        $body = substr($response, $pos + 2 * strlen($crlf));

        // parse headers
        $headers = array();
        $lines = explode($crlf, $header);
        foreach($lines as $line)
            if(($pos = strpos($line, ':')) !== false)
                $headers[strtolower(trim(substr($line, 0, $pos)))] = trim(substr($line, $pos+1));

        // redirection?
        if(isset($headers['location']))
        {
            $http = new HTTPRequest($headers['location']);
            return($http->DownloadToString($http));
        }
        else
        {
            return($body);
        }
    }
}

function PostRequest($url, $referer, $_data) {
    // convert variables array to string:
    $data = array();
    while(list($n,$v) = each($_data)){
        $data[] = urlencode($n)."=".urlencode($v);
    }

    $data = implode('&', $data);
    // format --> test1=a&test2=b etc.

    // parse the given URL
    $url = parse_url($url);
    if ($url['scheme'] != 'http') {
        die('Only HTTP request are supported !');
    }

    // extract host and path:
    $host = $url['host'];
    $path = $url['path'];

    // open a socket connection on port 80
    $fp = fsockopen($host, 80);

    // send the request headers:
    fputs($fp, "POST $path HTTP/1.1\r\n");
    fputs($fp, "Host: $host\r\n");
    fputs($fp, "Referer: $referer\r\n");
    fputs($fp, "Content-type: application/x-www-form-urlencoded\r\n");
    fputs($fp, "Content-length: ". strlen($data) ."\r\n");
    fputs($fp, "Connection: close\r\n\r\n");
    fputs($fp, $data);

    $result = '';
    while(!feof($fp)) {
        // receive the results of the request
        $result .= fgets($fp, 128);
    }

    // close the socket connection:
    fclose($fp);

    // split the result header from the content
    $result = explode("\r\n\r\n", $result, 2);
    $header = isset($result[0]) ? $result[0] : '';
    $content = isset($result[1]) ? $result[1] : '';

    // return as array:
    return array($header, $content);
}



?>