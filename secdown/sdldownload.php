<?php  
  /* 
    Plugin Name: WP Secure Links 
    Plugin URI: http://sixthlife.net/product/wp-secure-links/ 
    Description:  Create dyanmically build secure links, restrict by roles,usernames. Built email lists with freebies, use subscribe to download feature.
    Author: Sixthlife
    Version: 1.2
    Author URI: http://www.sixthlife.net 
    */  
    

include_once(dirname(__FILE__).DIRECTORY_SEPARATOR."sdlfunct.php");                      //including functions.php

add_action('init', 'sdl_download');

function sdl_download(){ 
	global $wp, $wpdb, $woocommerce,  $product, $post;

sdl_startover();									// print_r($_GET);exit;                                 // Delete records older that no of days defined in settings file.

if((isset($_GET['dc']))&& (!empty($_GET['dc']))){   // Check if download code is set

    if(sdl_authenticate($_GET['dc'])){                    //Verify if download code is correct.
    
	$filename = sdl_getfile($_GET['dc']);           
    $fakename = sdl_fakefilename($_GET['dc']);
    
        if(($filename!= false)&&($fakename!=false&& @fopen($filename,'r')==true)){
        	
    	if(isset($_GET['order'])&&isset($_GET['download_file'])&&isset($_GET['key'])&&isset($_GET['email']))
		{
		/* passing control from here*/
		return '';
		}        	
     	$file_id = sdl_getfileid($filename);
		$file_notadl = sdl_getfiledlstatus($file_id);
		$extension = sdl_getfileext($file_id);

    	if($extension!=''){
    		$mime = sdl_contenttype($extension);
			}
			else{
			$extension=	sdl_fileexten($filename);
			$mime = sdl_contenttype($extension);
			if($mime==''){
 			$mime = sdl_getContentType($filename);				
			}
			if($extension==''){
			$extension = sdl_fileextenfrommime($mime);	
				}				
			} 
			
      // echo 'start'.$mime.'end'; echo 'start'.$extension.'end'; exit;
       
		 	if($file_notadl==FALSE){ //echo $fakename.'.'.$extension;exit;
	        set_time_limit(0);
	        header('Pragma: public');
	        header('Expires: 0'); 
	        header("Content-Type:".$mime);
	        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	        header('Content-Disposition: attachment;filename='.$fakename.'.'.$extension);
	        header("Content-Transfer-Encoding: binary");
	        header('Connection: Keep-Alive');
	      /*    if (ob_get_length() > 0) {
	 			ob_end_clean();
				}
	        flush();
	        @readfile($filename); */
	        
	        $file = fopen($filename,"rb");
			while(!feof($file))
			{
				print(fread($file, 1024*8));
 				if( ob_get_level() > 0 ) ob_flush();
				flush();
			//	ob_end_clean();
				} 
				
    		if(!isset($_GET['order'])&& !isset($_GET['download_file'])&& !isset($_GET['key'])&& !isset($_GET['email']))
			{ 
				exit;
			} 
	        }
	        else{
	        	$mime = ($mime=='application/force-download')?"":$mime;
   		      header("Content-Type:".$mime);
		      header('Content-Disposition: inline;filename='.$fakename.'.'.$extension);  	
		      echo @file_get_contents($filename);
      		if(!isset($_GET['order'])&& !isset($_GET['download_file'])&& !isset($_GET['key'])&& !isset($_GET['email']))
			{ 
				exit;
			}        	
	        }
        }
        else{
    	$error ="<h3>We could not find this file</h3>";} // If the filename or fake filename could not be retrieved. 
        }  
    else{
    	$error =   "<h3>The download link is not correct.</h3>";             // If the download code  is not correct. 
    }
}
else{ 
	
	if(isset($_GET['order'])&&isset($_GET['download_file'])&&isset($_GET['key'])&&isset($_GET['email']))
	{
			$product_id = sdl_in_customer_product_id(absint($_GET['download_file']));
			$product = new WC_Product($product_id);
			$product_file = $product->get_file($_GET['key']);
	//	echo (strpos($product_file['file'], 'order=wc_order_')!==FALSE);exit;
			if(strpos($product_file['file'], 'order=wc_order_')!==FALSE){
	 		$no_accesspage = get_option( 'sdl_noaccesspage', false );
		 	header('location:'.get_permalink( $no_accesspage ));
			 exit;				
			}
   		
			else if(sdl_in_customer_downloads($_GET['download_file']) ){
	     	return '';				
			}
			else{

	 		$no_accesspage = get_option( 'sdl_noaccesspage', false );
		 	header('location:'.get_permalink( $no_accesspage ));				
			}
	
	}
// echo $error. ' '.$filename.' current page: '.currentpageurl(); exit; 	 // If the download code  is not set or empty. 
    }  

if(isset($error)){                              //show no permissions page.  
//echo $error. ' '.$filename.' current page: '.currentpageurl(); exit;
 $no_accesspage = get_option( 'sdl_noaccesspage', false );

header('location:'.get_permalink( $no_accesspage ));
exit;	
}

}

function filter_woocommerce_product_files( $downloadable_files, $instance ) { 

 	
 	foreach($downloadable_files as &$downloadable_file){
 		$downloadable_file['file']=do_shortcode($downloadable_file['file']);
 	}
  	//print_r($downloadable_files);
    return $downloadable_files; 
}


function filter_woocommerce_customer_get_downloadable_products($downloads){

	foreach($downloads as &$download){
	$downfile =	$download['file'] ;
	$downurl =	$download['download_url'];
	$downs = explode('&',$downfile['file']);
	foreach($downs as $down){
	if(strpos($down, 'dc=')!==FALSE && strpos($down, '?')===FALSE){
	$download['download_url']=$download['download_url'].'&'.$down;	
	}
	else if(strpos($down, 'dc=')!==FALSE && strpos($down, '?')!==FALSE){
	$down = explode('?', $down);
	$download['download_url']=$download['download_url'].'&'.$down[1];
	}	
	}
	}
	return $downloads;
}


function filter_woocommerce_get_item_downloads( $files, $item, $instance ) { 

		 foreach($files as &$file){
 		$file['file']=do_shortcode($file['file']);
			$downs = explode('&',$file['file']);
			foreach($downs as $down){
				if(strpos($down, 'dc=')!==FALSE && strpos($down, '?')===FALSE){
				$file['download_url']=$file['download_url'].'&'.$down;	
				}
				else if(strpos($down, 'dc=')!==FALSE && strpos($down, '?')!==FALSE){
					$down = explode('?', $down);
					$file['download_url']=$file['download_url'].'&'.$down[1];
				}
			}
 	
 	}
 	//print_r($files);
    return $files; 
}

function filter_woocommerce_product_file_download_path( $file_path, $instance, $download_id ) { 
	if((isset($_GET['dc']))&& (!empty($_GET['dc']))){   // Check if download code is set

    if(sdl_authenticate($_GET['dc'])){                    //Verify if download code is correct.
    
	$filename = sdl_getfile($_GET['dc']);           
    
    	if(isset($_GET['order'])&&isset($_GET['download_file'])&&isset($_GET['key'])&&isset($_GET['email']))
	{
		$file_path = $filename;
	}

}
}
    return $file_path; 
}

//if ( class_exists( 'WooCommerce' ) ) {
add_filter('woocommerce_product_file_download_path','filter_woocommerce_product_file_download_path',11,3 );
add_filter( 'woocommerce_product_files', 'filter_woocommerce_product_files', 11, 2 ); 
add_filter( 'woocommerce_customer_get_downloadable_products', 'filter_woocommerce_customer_get_downloadable_products', 10, 1); 
add_filter( 'woocommerce_get_item_downloads', 'filter_woocommerce_get_item_downloads', 10, 3 ); 
//}

function sdl_scripts_general() {
    wp_enqueue_script('secodwn',plugins_url('/js/general.js', __FILE__), array('jquery'));
}
add_action('wp_enqueue_scripts', 'sdl_scripts_general');

/*
	*downloadlink function can be called in themes  
	*@$atts array of param with ex: array('url'=>$url, 
	*'name'=>$name,'id'=>$cssid, 'class'=>$cssclass,
	*'title'=> $titleattr, 'onclick'=>$jsforonclick, 'roles'=>
	*$userrolefileperm, 'users'=>$usersfileperm, 
	*'message'=>$messageforuserswithoughtfileaccess),
	*'extension'=>$extensionoforiginalfile,
    *'notdownload'=>$inlinedisplay,
	*@anchortext the anchor text for the url be generated.
	*return string type hyperlink code
*/

function sdl_downloadlink($atts, $anchortext){
global $wp, $post,$wpdb, $wp_session;
 static $count =0;
   extract( shortcode_atts( array(
   	  'url' => '',
      'name' => 'download',
      'class' => '',
      'id' => '',
      'style' =>'',
      'title' => '',
      'onclick'=>'',
      'roles'=>'',
      'users'=>'',
      'message'=>'',
      'extension'=>'',
      'notdownload'=>'',
      'sdownload'=>0,
      'slist'=>'',
      'onlylink'=>0
      ), $atts ) );
      
      $sdownload = ($sdownload==true)?"1":$sdownload;
      $onlylink = ($onlylink==true)?"1":$onlylink;
      $notdownload= ($notdownload==true)?"1":$notdownload;
	  
	   if(isset($roles) && $roles!=='' &&  current_user_can('manage_options')==FALSE){
	   		$rolessarray = array();
		     if(strpos($roles, ',')!==FALSE){
	   	 	$roles = str_replace(' ', '',$roles);
	   	 	$rolessarray = explode(',',$roles);
	   	 	}
 		if(!is_user_logged_in () ){
 			return $message;
 		} 
 		else if(count($rolessarray)>0 && in_array(sdl_get_user_role(), $rolessarray)){ 
 			//do nothing
 		}
	   else	if(sdl_get_user_role()!=$roles){return $message;}
	   }

	   if(isset($users) && $users!='' &&  current_user_can('manage_options')==FALSE){
	   	$current_user = wp_get_current_user();
	   	 $current_user->user_login; 
	   	 $usersarray = array();
	   	 if(strpos($users, ',')!==FALSE){
	   	 	$users = str_replace(' ', '',$users);
	   	 	$usersarray = explode(',',$users);
	   	 } //print_r($usersarray);
	   	 if ( !($current_user instanceof WP_User) )
          return $message;
 		if(!is_user_logged_in () ){
 			return $message;
 		} 
 		else if(count($usersarray)>0 && in_array($current_user->user_login, $usersarray)){ 
 			//do nothing
 		}
	   	else if($current_user->user_login!==$users && $users !== 'loggedin'  ){
	   		return $message;
		   }
	   }
	   
	 $pluginpath = plugin_dir_url( 'setreferer.php' ).plugin_basename(__FILE__);
	 $pluginpath = explode('/',$pluginpath );
	 array_pop($pluginpath);
	 $pluginpath = implode('/',$pluginpath);
	 $pluginpath =$pluginpath;
	 if(isset($name) && $name==""){ $name = 'download';}
	 $downloadurl = sdl_downloadurl(trim($url),$name,$notdownload, $extension);
	 $link = '<a href="'.$downloadurl.'" ';
	 
	 if(isset($id) && $id != ''){ 	 	
	 	$link .= 'id="'.$id.'" ';
	 }
	 
	 if(isset($style) && $style != ''){ 	 	
	 	$link .= 'style="'.$style.'" ';
	 }	 
	 if(isset($title) && $title != ''){ 	
	 	$link .= 'title="'.$title.'" ';
	 }	 
	 if(isset($class) && $class != ''){ 	
	 	$link .= 'class="'.$class.'" ';
	 }
	 $postid = get_the_ID();
	 $link .=' onclick="javascript:updateReferer(this,\''.$pluginpath.'\', \''.$postid.'\');" >'.$anchortext.'</a>';
	 
	 if(isset($onclick) && $onclick != ''){ 	
	 	$link = str_replace('" >',$onclick.'" >', $link );
	 }

	 
	 if( $sdownload==1 && isset($_POST['sdl_download']) ){
	 //	echo 'sdl_dopageid '.$_POST['sdl_dopageid']." postid ".get_the_ID().'<br />';
			
	 	$email = $_POST['sdl_email'];
	 //	echo get_option('sdl_downloadfrmiagree').' anu'.isset($_POST['sdl_iagree']);
	 	$count++;
	 	
	 	if(is_email($email)==FALSE  && $_POST['sdl_dopageid'] ==  get_the_ID() && $count==(int)$_POST['sdl_formct']  ){
	 		
	 				$invalidemail = (get_option('sdl_downloadfrminvalid')=='')?'<font color="red">Email is not valid. Try again.</font>':html_entity_decode(stripslashes(get_option('sdl_downloadfrminvalid')));
	 		
	 		return sdl_formcode(html_entity_decode($invalidemail), $count);
	 		
	 	}
	 	else if(get_option('sdl_downloadfrmiagree')!=1 && !isset($_POST['sdl_iagree'])&& $_POST['sdl_dopageid'] ==  get_the_ID() && $count==(int)$_POST['sdl_formct'] ) {
	 		
	 		$iagree = (get_option('sdl_downloadfrmcheckit')=='')?'<font color="red">Please select "I Agree" to download.</font>':html_entity_decode(stripslashes(get_option('sdl_downloadfrmcheckit')));
	 		return sdl_formcode(html_entity_decode($iagree), $count);
	 	}
	 	else if( $_POST['sdl_dopageid'] !=  get_the_ID()|| $count!=(int)$_POST['sdl_formct']){
	 		return sdl_formcode("", $count);
	 	}
	 	 	
	 	if(class_exists('WYSIJA') && (get_option('sdl_newsletter')!='dsubscribers') && (get_option('sdl_newsletter')!='')){
		$my_list_id1='';
	 	 $user_data = array(
        'email' => $email,
		'status' => 1);
       if($slist !=''){
       $my_list_id1 =	get_mailpoet_list_byname($slist);
       }
       if($my_list_id1==''){
       	$my_list_id1 = get_option('sdl_newsletterlist');
       }
	    $data_subscriber = array(
      'user' => $user_data,
      'user_list' => array('list_ids' => array($my_list_id1))
    );
        $helper_user = WYSIJA::get('user','helper');
    	$helper_user->addSubscriber($data_subscriber);
    	
	 	}
	 	else{
	 		$my_list_name = ($slist==''|| !isset($slist))?"No List":$slist;
	 		addtocsv_csv($email, $my_list_name);
	 	}
	 	
	 	if(get_option('sdl_downloadfrmdirectd')==1){
	header('location:'.$downloadurl);
		}
		$message_before = (get_option('sdl_downloadfrmess')!='')? html_entity_decode(get_option('sdl_downloadfrmess')):'<strong>Download your file here </strong>';
		if($onlylink==1){return $downloadurl;}
	 	return html_entity_decode($message_before).$link;
	 }
	 else if( $sdownload==1 && !isset($_POST['sdl_download'])){
		$count++;
	 	return sdl_formcode("", $count);
	 }
if($onlylink==1){return $downloadurl;}
return $link;	 

}

add_shortcode( 'slink', 'sdl_downloadlink' );

// create custom plugin settings menu
add_action('admin_menu', 'sdl_create_menu');

function sdl_create_menu() {

	//create new top-level menu
	add_options_page('WP Secure Links', 'WP Secure Links', 'administrator', __FILE__, 'sdl_settings_page',plugins_url('/images/icon.png', __FILE__));

	//call register settings function
	add_action( 'admin_init', 'register_sdlsettings' );
}


function register_sdlsettings() {
	//register our settings
	register_setting( 'sdl-settings-group', 'sdl_nooffiles' );
	register_setting( 'sdl-settings-group', 'sdl_startover' );
	register_setting( 'sdl-settings-group', 'sdl_noaccesspage' );
	register_setting( 'sdl-settings-group', 'sdl_newsletter' );		
	register_setting( 'sdl-settings-group', 'sdl_newsletterlist' );
	register_setting( 'sdl-settings-group', 'sdl_delfile' );	
	register_setting( 'sdl-settings-group', 'sdl_downloadfrmcode' );
	register_setting( 'sdl-settings-group', 'sdl_downloadfrmiagree' );
	register_setting( 'sdl-settings-group', 'sdl_downloadfrmcheckit', 'sdl_clean_string_fordb' );	
	register_setting( 'sdl-settings-group', 'sdl_downloadfrminvalid', 'sdl_clean_string_fordb' );
	register_setting( 'sdl-settings-group', 'sdl_downloadfrmess', 'sdl_clean_string_fordb' );	
	register_setting( 'sdl-settings-group', 'sdl_downloadfrmdirectd' );	
	

}

function sdl_settings_page() {
$args = array(
    'post_type' => 'page',
	'name' => 'sdl_noaccesspage',
	'show_option_none' => 'Select Page',
	'selected' => get_option('sdl_noaccesspage')
	);
	
	  $belowdocroot = sdl_belowdocroot(); 
	
	if(file_exists($belowdocroot.'/emaillist.csv') && get_option('sdl_delfile')==1){
		unlink($belowdocroot.'/emaillist.csv');
		update_option('sdl_delfile', 0);
		
	}
?><div class="wrap">
<div id="icon-link-manager" class="icon32" ></div>
<h2>WP Secure Links</h2>

<form method="post" action="options.php" ><?php settings_fields( 'sdl-settings-group' ); ?><table class="form-table">
<tbody>
 		<?php if(file_exists($belowdocroot.'/emaillist.csv') && (get_option('sdl_newsletter')=='dsubscribers' || get_option('sdl_newsletter')=='')){ ?>
        <tr valign="top">

		<th scope="row"><label for="">Download File</th> <td>
     <?php echo sdl_downloadlink(array('url'=>$belowdocroot.'/emaillist.csv'), 'Download Subscribers');  ?> Keep file <input name="sdl_delfile" type="radio" value="0" <?php  if(get_option('sdl_delfile')!='1'){echo 'checked="checked"';} ?> />Delete File <input name="sdl_delfile" type="radio" value="1"  <?php  if(get_option('sdl_delfile')=='1'){echo 'checked="checked"';} ?> />
      
        <p class="description">Delete the Subscribers after you have downloaded. </p>
       </td>
         </tr>  
         <?php } ?>
        <tr valign="top">
		
		<th scope="row"><label for="">Number of Files</th> <td>
        <input type="text" class="regular-text" name="sdl_nooffiles" value="<?php if(get_option('sdl_nooffiles')==""){ echo 10000;} else{  echo get_option('sdl_nooffiles');} ?>" />
        <p class="description">Expected Number of downloadable Files. Default Value is 10,000. </p>
       </td>
         </tr>
		 <tr valign="top">
        <th  scope="row"><label for="">
  Start Over</label></th><td>
        <input type="text" class="regular-text" name="sdl_startover" value="<?php if(get_option('sdl_startover')==""){ echo 30;} else{  echo get_option('sdl_startover');} ?>" />
        <p class="description">Number of days after which unusable data from database is flushed or removed. Default value is 30. </p>
               </td></tr>
      		 <tr valign="top">
        <th  scope="row"><label for="">
   Unauthorized Access Page</label></th><td>
<?php wp_dropdown_pages( $args ); ?> 
        <p class="description">No Permissions page for Unauthorized Access to Downloadable Files.  </p>
               </td></tr> 
			   
      		 <tr valign="top">
        <th  scope="row"><label for="">
  Newsletter System</label></th><td>
<select name="sdl_newsletter">
<option value="dsubscribers" <?php if(get_option('sdl_newsletter')=='dsubscribers'){echo 'selected="selected"';} ?> >Download Subscribers</option>
<option value="mailpoet" <?php if(get_option('sdl_newsletter')=='mailpoet'){echo 'selected="selected"';} ?> >MailPoet</option>
</select>

        <p class="description">Select the Newsletter System you will use for download subscription form.  </p>
               </td></tr> 
      		 <tr valign="top">
        <th  scope="row"><label for="">
  Mailing List </label></th><td>

<select name="sdl_newsletterlist">

<?php

if(class_exists('WYSIJA') && get_option('sdl_newsletter')!='dsubscribers' && get_option('sdl_newsletter')!=''){
	 $lists=  get_mailpoet_lists();
foreach($lists as $list){
	
	if(get_option('sdl_newsletterlist')==$list['list_id']){ 
		$selected = 'selected="selected"';
	}
	else{
		$selected = '';
	}
echo '<option value="'.$list['list_id'].'" '.$selected. '>'.$list['name'].'</option>';	
}
 }
 else{
 	echo '<option value="0">No List</option>';
 }
?>

</select>

        <p class="description">Default Mailing List for subscribers. You can override this in short tag. </p>
               </td></tr> 	
			   
		 <tr valign="top">
        <th  scope="row"><label for="">
 Download Form Code</label></th><td>
        <textarea name="sdl_downloadfrmcode" rows="9" cols="50"> <?php if(get_option('sdl_downloadfrmcode')==""){ echo '<input type="text" name="sdl_email" value="Enter your Email Address" onclick="this.value==\'Enter your Email Address\'?this.value=\'\':this.value;" class="sdl_email wpress-text" type="text" style="width:250px;" id="sdl_email"/><input type="submit" name="sdl_download" value="Download" class="wpress-btn wpress-btn-primary" id="sdl_button"/><h5><input type="checkbox" name="sdl_iagree" /> I agree to receive weekly newsletters from <a href="http://sixthlife.net">Sixthlife.net</a>. </h5>';} else{  echo get_option('sdl_downloadfrmcode');} ?></textarea>
        <p class="description">When using the Subscibe to Download (sdownload) option the form fields. </p>
               </td></tr>	
			   
      	<tr valign="top">
		
		<th scope="row"><label for="">Invalid Email Message</th> <td>
        <input type="text" class="regular-text" name="sdl_downloadfrminvalid" value="<?php if(get_option('sdl_downloadfrminvalid')==""){ echo htmlspecialchars('<font color="red">Email is not valid. Try again.</font>');} else{    echo (get_option('sdl_downloadfrminvalid'));} ?>" />
        <p class="description">For Subscribe to Download option (sdownload) the invalid email message. </p>
       </td>
         </tr>	     
		 
      	<tr valign="top">
		
		<th scope="row"><label for="">Mandatory "I Agree" </th> <td>
        Yes<input type="radio" name="sdl_downloadfrmiagree" value="0" <?php if(get_option('sdl_downloadfrmiagree')!=1){ echo 'checked="checked"';}  ?> />
               No <input type="radio" name="sdl_downloadfrmiagree" value="1" <?php if(get_option('sdl_downloadfrmiagree')==1){ echo 'checked="checked"';}  ?> />
        <p class="description">For Subscribe to Download option (sdownload). "I Agree" checkbox should be checked. </p>
       </td>
         </tr>	
		 
      	<tr valign="top">
		
		<th scope="row"><label for="">"I Agree" Message</th> <td>
        <input type="text" class="regular-text" name="sdl_downloadfrmcheckit" value="<?php if(get_option('sdl_downloadfrmcheckit')==""){ echo htmlspecialchars('<font color="red">Please select "I Agree" to download.</font>');} else{  echo stripslashes(get_option('sdl_downloadfrmcheckit'));} ?>" />
        <p class="description">For Subscribe to Download option (sdownload) the unchecked "I agree" message. </p>
       </td>
         </tr>		 		 
      	<tr valign="top">
		
		<th scope="row"><label for="">Direct Download After Subscribe</th> <td>
        Yes<input type="radio" name="sdl_downloadfrmdirectd" value="1" <?php if(get_option('sdl_downloadfrmdirectd')=="1"){ echo 'checked="checked"';}  ?> />
               No <input type="radio" name="sdl_downloadfrmdirectd" value="0" <?php if(get_option('sdl_downloadfrmdirectd')!="1"){ echo 'checked="checked"';}  ?> />
        <p class="description">For Subscribe to Download option (sdownload). If user will download immediately or see a download link. </p>
       </td>
         </tr>	
		 
      	<tr valign="top">
		
		<th scope="row"><label for="">Download Message</th> <td>
        <input type="text" class="regular-text" name="sdl_downloadfrmess" value="<?php if(get_option('sdl_downloadfrmess')==""){ echo htmlspecialchars('<strong>Download your file here </strong>');} else{  echo stripslashes(get_option('sdl_downloadfrmess'));} ?>" />
        <p class="description">For Subscribe to Download option (sdownload) Message that appears besides download Link after subscribe. </p>
       </td>
         </tr>			 	  			   
			       
</tbody>
</table><?php submit_button(); ?></form>
</div>
<?php } ?><?php 

// Add buttons to html editor
add_action('admin_print_footer_scripts','eg_quicktags');
function eg_quicktags() {
 ?>
<script type="text/javascript" charset="utf-8">
if ( typeof(QTags) == 'function' ) { 
QTags.addButton( 'eg_slink', 'slink', '[slink url=""]', '[/slink]', 'slink', 'Secure Link' );
}

</script>
<?php } ?><?php 

function sdl_enqueue_plugin_scripts($plugin_array)
{
    //enqueue TinyMCE plugin script with its ID.
    $plugin_array["slink_button_plugin"] =  plugin_dir_url(__FILE__) . "js/editorsnip.js";
    $plugin_array["sslink_button_plugin"] =  plugin_dir_url(__FILE__) . "js/editorsnip.js";
    return $plugin_array;
}

add_filter("mce_external_plugins", "sdl_enqueue_plugin_scripts");

function sdl_register_buttons_editor($buttons)
{
    //register buttons with their id.
    array_push($buttons, "slink");
    array_push($buttons, "sslink");
    return $buttons;
}

add_filter("mce_buttons", "sdl_register_buttons_editor");

global $sdl_db_version;
$sdl_db_version = "1.2";

function sdl_install(){
global $wpdb;
global $sdl_db_version;	

sdl_createdbtables();
require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
add_option("sdl_db_version", $sdl_db_version);	
}

register_activation_hook(__FILE__, 'sdl_install');

function sdl_on_deactivate() {
global $wpdb;
$sql = "DROP TABLE  {$wpdb->prefix}ddown";
$sql1 = "DROP TABLE  {$wpdb->prefix}ipmap";

require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
$wpdb->query($sql);
$wpdb->query($sql1);

delete_option('sdl_noaccesspage');
delete_option('sdl_nooffiles');
delete_option('sdl_startover');
delete_option('sdl_newsletter' );		
delete_option('sdl_newsletterlist' );
delete_option('sdl_delfile' );
delete_option('sdl_downloadfrmcode');
delete_option('sdl_downloadfrmiagree' );		
delete_option('sdl_downloadfrmcheckit' );
delete_option('sdl_downloadfrminvalid' );
delete_option('sdl_downloadfrmess' );
delete_option('sdl_downloadfrmdirectd' );
}
register_deactivation_hook(__FILE__, 'sdl_on_deactivate');
?>