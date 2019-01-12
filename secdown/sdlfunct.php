<?php
  // Stop page from being loaded directly. 
if (preg_match("/sdlfunct.php/i", $_SERVER['PHP_SELF'])){
echo "Please do not load this page directly. Thanks!";
exit;
}   
                                    
global $wpdb;
/******************* Main Functions *******************/
function sdl_downloadcode($url, $pretendname = 'download' , $postid,  $notadl = 0 , $ext=''){ // function sdl_to generate download code  
global $wpdb, $wp_session; 
$downloadpage = sdl_currentpageurl();
 $uniquecode = sdl_generatecode();
//echo $_SERVER['HTTP_REFERER'].' '. $uniquecode.'<br />';
$client_ip =   sdl_getip();
$result_urlchk =$wpdb->get_row("SELECT * FROM ".$wpdb->prefix."ddown WHERE actuallink = '".$url."' LIMIT 1");
//echo  $postid." ".$wp_session['sdl_current_page'];
//writedberror($result_urlchk);
    if( NULL===$result_urlchk){
    sdl_addnewurltodb($url,$pretendname,$downloadpage,$notadl, $ext);
    $file_id = sdl_getfileid($url);
    sdl_addnewcodedb($file_id,$client_ip,$uniquecode, $postid);
    }	
    else if(NULL!==$result_urlchk){
    $file_id = $result_urlchk->id;
    $file_name = $result_urlchk->pretendname;
    $pageurl = $result_urlchk->whoreferred; 
    $dbnotadl = $result_urlchk->notadl;
    $dbext = $result_urlchk->fileexten;
        if($file_name!=trim($pretendname)){	
        sdl_changefakenamedb($pretendname, $file_id);
        }  
	//	echo $pageurl; echo ' '.$downloadpage; exit;  
		$pageurlarray = explode(',',$pageurl);
        if($pageurl!=trim($downloadpage)&& !in_array($downloadpage,$pageurlarray)){
        $downloadpagestr =  $pageurl.','.$downloadpage;	
        sdl_addtowhoreferreddb($downloadpagestr, $file_id, $postid);
        }
        if($dbnotadl!=$notadl){
        sdl_adddlstatusdb($notadl, $file_id);	
        }
        if($dbext!=$ext){
        sdl_adddfileextdb($ext, $file_id);	
        }        
		if(sdl_ipfileexists($client_ip, $file_id, $postid)==true && $postid!=$wp_session['sdl_current_page']){
        sdl_changecodedb($uniquecode,$client_ip,$file_id, $postid);
		}
		else{
	    sdl_addnewcodedb($file_id,$client_ip,$uniquecode, $postid);			
		} 
    }
return 	$uniquecode;
}

function sdl_ipfileexists($client_ip, $file_id, $pageid){
		global $wp, $wpdb;
		if(current_user_can('manage_options') && $pageid==''){
        $query_ipchk = "SELECT * FROM ".$wpdb->prefix."ipmap WHERE ipaddress='".$client_ip."' AND id_file = ".$file_id."  LIMIT 1";			
		}
		else{
        $query_ipchk = "SELECT * FROM ".$wpdb->prefix."ipmap WHERE ipaddress='".$client_ip."' AND id_file = ".$file_id." AND pageid=".$pageid." LIMIT 1";
        }
		$result_ipchk = $wpdb->get_row($query_ipchk);
		if($result_ipchk == NULL){
			return false;
		}
		else {
			return true;
		}	
}

function sdl_authenticate($code){                              //Verify IP address and downlaod code beofore a file download.
global $wpdb;
$client_ip = sdl_getip();
$query_urlverify = "SELECT * FROM ".$wpdb->prefix."ipmap WHERE dccode = '".$code."' and ipaddress='".$client_ip."'";
$result_urlverify= $wpdb->get_row($query_urlverify);
//writedberror($result_urlverify);
    if(NULL===$result_urlverify){
    	    //	echo 'ipmap rows: '.' '.$query_urlverify. '  '.mysql_numrows($result_urlverify); exit;
   	return false;
    }	
    elseif(NULL!==$result_urlverify){ 
    $file_id = $result_urlverify->id_file;  
    $refererstr = sdl_whoreferredstr($file_id);
    $refererarr = explode(',',$refererstr);
    $referer = isset($_SERVER['HTTP_REFERER'])?$_SERVER['HTTP_REFERER']: wp_get_referer();
       if( in_array($referer, $refererarr)!=FALSE){      
        return true;
        } 
        else if(in_array(sdl_getSReferer($code), $refererarr)!=FALSE){
         sdl_clearsReferer($code);
       	return true;
        }
        else{
        //	echo 'http referrer: '.$_SERVER['HTTP_REFERER'];
         // 	print_r($refererarr);   
			// echo 'get referer: ' .sdl_getSReferer($code);  
			// exit;	
			return false;
			}
        }
    else {
    //	echo 'ipmap rows: '.mysql_numrows($result_urlverify); exit;
    return false;
    }
}

function sdl_generatecode(){                                // Check Random Code for uniqueness and regenerate if required. 
global $wpdb;
$randcode = sdl_randomcode();
$count = 1;
    while($count>0){
    $query_unq = "SELECT id FROM ".$wpdb->prefix."ipmap WHERE dccode = '".$randcode."' LIMIT 1";	
    $result_unq = $wpdb->get_row($query_unq);
    $count =($result_unq!==NULL)?1:0;
    $randcode = sdl_randomcode();	
    }
return $randcode;
}

function sdl_downloadurl($url, $pretendname = 'download', $notadl = 0, $ext=''){      //function sdl_to print download URL in Download Page
global $wp, $wpdb, $post, $wp_session;

$postid = get_the_ID(); 

$file_id = sdl_getfileid($url);
$client_ip =   sdl_getip();

if(!isset($wp_session['sdl_current_page']) || $postid!=$wp_session['sdl_current_page']){
$wp_session['sdl_current_page'] = $postid;
}

	$downloadcode = (isset($_GET['dc'])&& $_GET['dc']!='')?urlencode($_GET['dc']):sdl_downloadcode($url, $pretendname, $postid, $notadl, $ext);

if(strpos(sdl_currentpageurl(), '?')==false){
	return sdl_currentpageurl().'?dc='.$downloadcode;
}
else if(strpos(sdl_currentpageurl(), '&dc=')){
	$temppart = explode('&dc=', sdl_currentpageurl());
	$temppart = $temppart[0];
	return $temppart.'&dc='.$downloadcode;
}
else if(strpos(sdl_currentpageurl(), '?dc=')){
	$temppart = explode('?dc=', sdl_currentpageurl());
	$temppart = $temppart[0];
	return $temppart.'?dc='.$downloadcode;	
}
else{
	return sdl_currentpageurl().'&dc='.$downloadcode;
}
}

function sdl_getfile($code){                                    // Get File Url 
global $wpdb;
$client_ip = sdl_getip();
$query_i = "select id_file from ".$wpdb->prefix."ipmap where  dccode = '".$code."' and ipaddress = '".$client_ip."' LIMIT 1";
$result_i = $wpdb->get_row($query_i);

    if($result_i!==NULL){
    $file_id = $result_i->id_file;
    $query_f = "select actuallink from ".$wpdb->prefix."ddown where  id = ".$file_id." LIMIT 1";
    $result_f = $wpdb->get_row($query_f);
    return $result_f->actuallink;
    }
else {return false;}
}

/******************* Functions for Internal Use *******************/

function sdl_fakefilename($code){                           //function sdl_to get pretended filename while downloading file. 
global $wpdb;
$client_ip =   sdl_getip();
$query_f = "select id_file from ".$wpdb->prefix."ipmap where dccode = '".$code."' and ipaddress = '".$client_ip."' LIMIT 1"; 
$result_f = $wpdb->get_row($query_f); 
//echo $query_f;echo $result_f->id_file; exit;

    if($query_f!==NULL){
    $file_id = $result_f->id_file;
    $query_pf = "select pretendname from ".$wpdb->prefix."ddown where id = '".$file_id."' LIMIT 1";
    $result_pf = $wpdb->get_row($query_pf);
    //writedberror($result_pf);
        if($result_pf!==NULL){	
        $fakefname = $result_pf->pretendname;
        return $fakefname;
        }
        else {return false;}
        }
    else {return false;}	
}

function sdl_startover(){                                   //function sdl_to cleanup database after defined number of days.
global $wpdb;
$days = get_option('sdl_startover');
if($days>0){
$query_del = "delete from ".$wpdb->prefix."ipmap WHERE timestamp < (NOW() - INTERVAL ".$days." DAY)";
$result_del =$wpdb->query($query_del);
}
//writedberror($result_del);	
$query_deli = "delete from ".$wpdb->prefix."ipmap where not exists(select null FROM ".$wpdb->prefix."ddown d WHERE d.id = id_file)";
$result_deli = $wpdb->query($query_deli);
//writedberror($result_deli);	
$query_deld = "delete from ".$wpdb->prefix."ddown where id NOT IN (SELECT i.id_file FROM ".$wpdb->prefix."ipmap i)";
$result_deld = $wpdb->query($query_deld);
//writedberror($result_deld);	
}

/******************* Database Functions ************************/

function sdl_createdbtables(){
	global $wpdb;
$query_cr = "CREATE TABLE IF NOT EXISTS ".$wpdb->prefix."ddown (".
  "id int(10) NOT NULL AUTO_INCREMENT,".
  "actuallink varchar(300) COLLATE utf8_unicode_ci NOT NULL,".
  "pretendname varchar(100) COLLATE utf8_unicode_ci NOT NULL,".
  "whoreferred text COLLATE utf8_unicode_ci NOT NULL,".
  "notadl tinyint(1) NOT NULL DEFAULT '0',".
  "fileexten char(10) COLLATE utf8_unicode_ci NOT NULL,".  
  "PRIMARY KEY (id),".
  "UNIQUE KEY actuallink (actuallink)".
") ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
$result_cr = $wpdb->query($query_cr);

$query_cr1 = "CREATE TABLE IF NOT EXISTS ".$wpdb->prefix."ipmap (".
  "id int(10) NOT NULL AUTO_INCREMENT,".
  "id_file int(10) NOT NULL,".
  "ipaddress varchar(15) COLLATE utf8_unicode_ci NOT NULL,".
  "dccode varchar(30) COLLATE utf8_unicode_ci NOT NULL,".
  "timestamp timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,".
  "pageid int(10) NOT NULL,".
  "refer varchar(300) COLLATE utf8_unicode_ci NOT NULL,".
  "PRIMARY KEY (id),".
  "UNIQUE KEY dccode (dccode)".
") ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
$result_cr1 = $wpdb->query($query_cr1);

}

function sdl_addnewurltodb($url,$pretendname,$downloadpage, $notadl = 0, $ext=''){
	global $wpdb;
    $result_dcins	= $wpdb->insert($wpdb->prefix.'ddown', array('id'=>'','actuallink'=>$url, 'pretendname'=>$pretendname, 'whoreferred'=>$downloadpage,  'notadl'=>$notadl, 'fileexten'=>$ext));
    //writedberror($result_dcins);    
}

function sdl_addnewcodedb($file_id,$client_ip,$uniquecode, $pageid 	){ //Generate new code on page reload
global $wpdb;
$result_ipmap	= $wpdb->insert($wpdb->prefix.'ipmap', array('id_file'=>$file_id, 'ipaddress'=>$client_ip,'dccode'=>$uniquecode,'pageid'=>$pageid ));

}

function sdl_getuniquecode($file_id,$client_ip, $pageid){ //Generate new code on page reload
global $wpdb;
$query_ipmap = "SELECT dccode from ".$wpdb->prefix."ipmap WHERE id_file = ".$file_id." and ipaddress = '".$client_ip."' and  pageid = ".$pageid;
$result_ipmap = $wpdb->query($query_ipmap);
if($result_ipmap){return $result_ipmap->dccode;}
else return false;   
}

function sdl_changecodedb($uniquecode,$client_ip,$file_id, $pageid){ //Generate new code on page reload
global $wpdb;
$query_chngcode = "UPDATE ".$wpdb->prefix."ipmap SET dccode='".$uniquecode."',  ipaddress = '".$client_ip."' WHERE id_file = ".$file_id." and  pageid = ".$pageid."";
$result_chngcode = $wpdb->query($query_chngcode);
//writedberror($result_chngcode);    
}

function sdl_changefakenamedb($pretendname, $file_id){      //Change fake filename for a file
global $wpdb;
$query_chngname = "UPDATE ".$wpdb->prefix."ddown SET pretendname='".$pretendname."' WHERE id = ".$file_id;
$result_chngname = $wpdb->query($query_chngname);
//writedberror($query_chngname);
}

function sdl_addtowhoreferreddb($downloadpagestr, $file_id){  //Update Who Refered string in database.
global $wpdb;

	$query_chngurl = "UPDATE ".$wpdb->prefix."ddown SET whoreferred='".$downloadpagestr."' WHERE id = ".$file_id;
$result_chngurl = $wpdb->query($query_chngurl);
  

//writedberror($result_chngurl);
}

function sdl_adddlstatusdb($notadl, $file_id){
	global $wpdb;
 $query_chngdl = "UPDATE ".$wpdb->prefix."ddown SET notadl='".$notadl."' WHERE id = ".$file_id;
$result_chngdl = $wpdb->query($query_chngdl);	
}

function sdl_adddfileextdb($ext, $file_id){
		global $wpdb;
$query_chngdl = "UPDATE ".$wpdb->prefix."ddown SET fileexten='".$ext."' WHERE id = ".$file_id;
$result_chngdl =  $wpdb->query($query_chngdl);	
}

function sdl_getfileid($url){                                 //Get File id from actual url
global $wpdb;
$query_id = "SELECT id FROM ".$wpdb->prefix."ddown WHERE actuallink = '".$url."' LIMIT 1";
$result_id = $wpdb->get_row($query_id);
if($result_id!==NULL){return $result_id->id;}
else return false;
}

function sdl_whoreferredstr($file_id){                       //Retrieve Who Refered string from database. 
global $wpdb;
$query_ref ="SELECT whoreferred FROM ".$wpdb->prefix."ddown WHERE id = ".$file_id." LIMIT 1";
$result_ref = $wpdb->get_row($query_ref);
//writedberror($result_ref);
$refererstr = $result_ref->whoreferred;  
return  $refererstr;
}

function sdl_getSReferer($code){							//Retrieve Who Refered by AJAX from database. for blank/ invalid referer. 
global $wpdb;
$client_ip = sdl_getip();
$code = esc_sql($code);
$query_ref = "SELECT refer FROM ".$wpdb->prefix."ipmap WHERE ipaddress = '".$client_ip."' and dccode='".$code."' limit 1";
$result_ref = $wpdb->get_row($query_ref);	
return strip_tags($result_ref->refer);
}

function sdl_clearsReferer($code){							//Clear Who Refered by AJAX from database. blank/ invalid referer case.
global $wpdb;
$client_ip = sdl_getip();

 $query_rref = "update ".$wpdb->prefix."ipmap  set refer = '' WHERE ipaddress = '".$client_ip."' and dccode='".$code."' limit 1"; 
$result_rref = $wpdb->query($query_rref);	
}

function sdl_getfiledlstatus($file_id){                                 //Get File id from actual url
global $wpdb;
$query_id = "SELECT notadl FROM ".$wpdb->prefix."ddown WHERE id = '{$file_id}' LIMIT 1";
$result_id = $wpdb->get_row($query_id);
if($result_id!==NULL){return $result_id->notadl;}
else return false;
}

function sdl_getfileext($file_id){                                 //Get File extension from id
global $wpdb;
$query_id = "SELECT fileexten FROM ".$wpdb->prefix."ddown WHERE id = '{$file_id}' LIMIT 1";
$result_id = $wpdb->get_row($query_id);
if($result_id!==1){return $result_id->fileexten;}
else return false;
}

/* function sdl_to give the error after databse operations.*/
 function sdl_writedberror($result){                        //show database error 
	if(!$result){die(mysql_error());}
}

/******************* Utility Functions ************************/

function sdl_getip(){                                        // Get IP address of current user
if (!empty($_SERVER['HTTP_CLIENT_IP'])){
$ip=$_SERVER['HTTP_CLIENT_IP'];
}
elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
$ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
}
else{
$ip=$_SERVER['REMOTE_ADDR'];
}
return $ip;
}

function sdl_fileexten($filename){                          // Get File extension from filename string.
$filenamesplit =explode('.',$filename);
$extension = $filenamesplit[count($filenamesplit)-1];
$extension = explode('?',$extension);
return $extension[0];
}

function sdl_currentpageurl(){                               //function sdl_to return current page url                        
return (!empty($_SERVER['HTTPS']) ? 'https://': 'http://').$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
}

function sdl_randomcode(){	                                // Generate Random Code	
$nofiles = get_option('sdl_nooffiles');
if($nofiles<10000){ $nofiles = 10000;} 
for($i=0; $i< strlen($nofiles)*2; $i++){
$randchars[] = chr(rand(97,122));
 }
$timestring = (string)time();
$code = '';	
$i = 0;
foreach($randchars as $randchar){
$code .= $randchar;
    if($i<strlen($timestring)){
    $code .= $timestring{$i};
    }
$i++;	
}
return $code;
}

function sdl_mimetypearray(){
	$mime_types=array();
$mime_types['ai']    ='application/postscript';
$mime_types['asx']   ='video/x-ms-asf';
$mime_types['au']    ='audio/basic';
$mime_types['avi']   ='video/x-msvideo';
$mime_types['bmp']   ='image/bmp';
$mime_types['css']   ='text/css';
$mime_types['doc']   ='application/msword';
$mime_types['eps']   ='application/postscript';
$mime_types['exe']   ='application/octet-stream';
$mime_types['gif']   ='image/gif';
$mime_types['htm']   ='text/html';
$mime_types['html']  ='text/html';
$mime_types['ico']   ='image/x-icon';
$mime_types['jpeg']  ='image/jpeg';
$mime_types['jpg']   ='image/jpeg';
$mime_types['js']    ='application/x-javascript';
$mime_types['mid']   ='audio/mid';
$mime_types['mov']   ='video/quicktime';
$mime_types['mp3']   ='audio/mpeg';
$mime_types['mpeg']  ='video/mpeg';
$mime_types['mpg']   ='video/mpeg';
$mime_types['pdf']   ='application/pdf';
$mime_types['pps']   ='application/vnd.ms-powerpoint';
$mime_types['ppt']   ='application/vnd.ms-powerpoint';
$mime_types['ps']    ='application/postscript';
$mime_types['pub']   ='application/x-mspublisher';
$mime_types['qt']    ='video/quicktime';
$mime_types['rtf']   ='application/rtf';
$mime_types['svg']   ='image/svg+xml';
$mime_types['swf']   ='application/x-shockwave-flash';
$mime_types['tif']   ='image/tiff';
$mime_types['tiff']  ='image/tiff';
$mime_types['txt']   ='text/plain';
$mime_types['wav']   ='audio/x-wav';
$mime_types['wmf']   ='application/x-msmetafile';
$mime_types['xls']   ='application/vnd.ms-excel';
$mime_types['zip']   ='application/zip';
$mime_types['rar'] = 'application/rar';

$mime_types['ra'] = 'audio/x-pn-realaudio';
$mime_types['ram'] = 'audio/x-pn-realaudio';
$mime_types['ogg'] = 'audio/x-pn-realaudio';

$mime_types['wav'] = 'video/x-msvideo';
$mime_types['wmv'] = 'video/x-msvideo';
$mime_types['avi'] = 'video/x-msvideo';
$mime_types['asf'] = 'video/x-msvideo';
$mime_types['divx'] = 'video/x-msvideo';

$mime_types['mp3'] = 'audio/mpeg';
$mime_types['mp4'] = 'audio/mpeg';
$mime_types['mpeg'] = 'video/mpeg';
$mime_types['mpg'] = 'video/mpeg';
$mime_types['mpe'] = 'video/mpeg';
$mime_types['mov'] = 'video/quicktime';
$mime_types['swf'] = 'video/quicktime';
$mime_types['3gp'] = 'video/quicktime';
$mime_types['m4a'] = 'video/quicktime';
$mime_types['aac'] = 'video/quicktime';
$mime_types['m3u'] = 'video/quicktime';
return $mime_types;
}

function sdl_contenttype($ext){                                // Function returns Mime type depending
	$mime_types=sdl_mimetypearray();
	if(array_key_exists($ext,$mime_types)){
	$mimetype = $mime_types[$ext];
	}
    else{ $mimetype = 'application/force-download';}
return $mimetype;
}

function sdl_getContentType($url){
	$contenttype='';
	$contenttype = sdl_getContentTypeCurl($url);
		
	if($contenttype==""){	
	$headers = 	@get_headers($url); //print_r($headers);
	$contenttype = '';
	if(empty($headers)){
		return '';
	}
	foreach($headers as $key=>$value){
		if($key == 'Content-Type'|| strpos($value, 'Content-Type')!==FALSE){
			$contenttype = is_array($value)?$value[0]: $value;
		}
	}
	$contenttype = str_replace(array('Content-Type', ':', ' '), '',$contenttype);
	}

	if($contenttype!=""){$contenttype = explode(';',$contenttype);}

	return trim($contenttype[0]);
}

function sdl_getContentTypeCurl($url){
	$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_HEADER, 1);
curl_setopt($ch, CURLOPT_NOBODY, 1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //print_r(trim(curl_exec($ch)));

$results = explode("\n", trim(curl_exec($ch)));
foreach($results as $line) { //echo $line.'<br />';
        if (strpos($line, 'Content-Type') !== FALSE) {
                $parts = explode(":", $line);
                return trim($parts[1]);
        }
}
}

function sdl_url_exists($url) {
    if (!$fp = curl_init($url)) return false;
    return true;
}

function sdl_get_user_role() {
	global $current_user;

	$user_roles = $current_user->roles;
	$user_role = array_shift($user_roles);

	return $user_role;
}

  function sdl_readfileinchunk($filename, $retbytes = TRUE) {
    $buffer = '';
    $count =0;

    $file = fopen($filename, 'rb');
    if ($file === false) {
      return false;
    }
    while (!feof($file)) {
      $buffer = fread($file, 1024*1024);
      echo  $buffer;

      ob_flush();
      flush();
ob_end_clean();
      if ($retbytes) {
        $count += strlen($buffer);
      }
    }
    $status = fclose($file);
    if ($retbytes && $status) {
      return $count; 
    }
    return $status;
  }
  
  function get_mailpoet_lists(){
  	//this will return an array of results with the name and list_id of each mailing list
$model_list = WYSIJA::get('list','model');
$mailpoet_lists = $model_list->get(array('name','list_id'),array('is_enabled'=>1));
 
return $mailpoet_lists;
  }
  
  function get_mailpoet_list_byname($listname){
  	//this will return an array of results with the name and list_id of each mailing list
$model_list = WYSIJA::get('list','model');
$mailpoet_lists = $model_list->get(array('name','list_id'),array('is_enabled'=>1));
 $listbynameid = '';
foreach($mailpoet_lists as $list){
	//echo $list['name'].' '.$listname;
	if($list['name']==$listname){
		return $list['list_id'];
	}
}
  }
  
  function sdl_belowdocroot(){
			$belowdocroot = explode('/www',$_SERVER['DOCUMENT_ROOT'] );
  			$belowdocroot = $belowdocroot[0];
			$belowdocroot = explode('/public_html',$belowdocroot);
			$belowdocroot = $belowdocroot[0];
			return $belowdocroot;
  }
  
  function addtocsv_csv($email, $listname){
			$belowdocroot = sdl_belowdocroot();
  			$file = fopen($belowdocroot."/emaillist.csv","a");
			$currentpage = sdl_currentpageurl();
			fputcsv($file,array($listname, $email,$currentpage));
			fclose($file);
  }
  
  function sdl_formcode($invalid = '', $formcounter){
  		global $wp, $wpdb, $post, $wp_session;
 		$currentpage = sdl_currentpageurl();
		$formcode = (get_option('sdl_downloadfrmcode')=='')?'<input type="text" name="sdl_email" value="Enter your Email Address" onclick="this.value==\'Enter your Email Address\'?this.value=\'\':this.value;" class="sdl_email wpress-text" type="text" style="width:250px;" id="sdl_email"/><input type="submit" name="sdl_download" value="Download" class="wpress-btn wpress-btn-primary" id="sdl_button"/><h5><input type="checkbox" name="sdl_iagree" /> I agree to receive weekly newsletters from <a href="http://sixthlife.net">Sixthlife.net</a>. </h5>':get_option('sdl_downloadfrmcode');
		
  	return '<p><a name="sdlfrm'.$formcounter.'"></a></p><br/>'.$invalid.' <form action="'.$currentpage.'#sdlfrm'.$formcounter.'" method="POST"><input type="hidden" name="sdl_dopageid" value="'.get_the_ID().'" /><input type="hidden" name="sdl_formct" value="'.$formcounter.'" />'.$formcode.'</form>';
  }

function sdl_clean_string_fordb($text){

	return esc_sql(htmlspecialchars($text));
}

function sdl_in_customer_downloads($download_id){
	global $wp, $wpdb, $post, $woocommerce;
	$userdownloads = wc_get_customer_available_downloads(  get_current_user_id() );
	//print_r($userdownloads); exit;
	     foreach($userdownloads as $userdownload){
	     if(($_GET['key']==$userdownload['download_id'])){
	     	return true;
	     	}
		}
		return false;
}

function sdl_in_customer_product_id($download_id){
	global $wp, $wpdb, $post, $woocommerce;
	$userdownloads = wc_get_customer_available_downloads(  get_current_user_id() );
	//print_r($userdownloads); exit;
	     foreach($userdownloads as $userdownload){
	     if(($_GET['key']==$userdownload['download_id'])){
	     	return $userdownload['product_id'];
	     	}
		}
		return false;
}
?>