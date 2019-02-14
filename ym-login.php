<?php
/**
 * @package YM Integration
 * @version 1.0.0
 */
 
/*
Plugin Name: YM Integration
Plugin URI: Not published
Description: The Plugin allows YM SSO and Event Registration
Author: Satyam Gollapalli
Version: 1.0.0
Text Domain: Login-IntegratNoion-with-YM
*/

require_once("ym-member-profile-update.php");

//Get API resopnse-This is common method to get the API response.
function get_api_response($xml = '') {
  
  $options = array(
    'http' => array(
      'header'  => "Content-type: application/x-www-form-urlencoded; charset=utf-8",
      'method'  => 'POST',
      'content' => $xml
 ));

  return simplexml_load_string(file_get_contents(
      API_ENDPOINT,
      false,
      stream_context_create($options)
   ));
}


function sanitize_form_field($in = '') {
  return htmlspecialchars($in, ENT_QUOTES | ENT_SUBSTITUTE | ENT_DISALLOWED | ENT_XML1, 'UTF-8');
}


//Create the YourMembership XML request object.
function create_xml($ym_call = '', $ym_callID = '000', $sessionID = '', $ym_callArgs = array()) {
	global $error;
	
	
/* YM API Credentials*/
define('API_ENDPOINT', 'https://api.yourmembership.com');
define('API_VERSION', '2.30');
define('API_KEY_PUBLIC', '09927436-7F9E-4D8C-9E5F-798604A60F95');

  
  if (empty($ym_call)) {
    return '';
  }
  
  $doc = new DOMDocument('1.0', 'UTF-8');
  $doc->formatOutput = true;	// Human-readable XML. Good for debugging.
  
  $xml    = $doc->createElement('YourMembership');
  $ver    = $doc->createElement('Version', API_VERSION);
  $apiKey = $doc->createElement('ApiKey', API_KEY_PUBLIC);
  $callID = $doc->createElement('CallID', $ym_callID);
  $call   = $doc->createElement('Call');
  
  $callAttr = $doc->createAttribute('Method');
  $callAttr->value = $ym_call;
  
  $call->appendChild($callAttr);
  
  if (count($ym_callArgs)) {
    foreach ($ym_callArgs as $key => $val) {
      // Make sure all call argument data are sanitized
      $el = $doc->createElement($key, sanitize_form_field($val));
      $call->appendChild($el);
    }
  }
  
  $xml->appendChild($ver);
  $xml->appendChild($apiKey);
  $xml->appendChild($callID);
  
  if ($sessionID) {
    $el = $doc->createElement('SessionID', $sessionID);
    $xml->appendChild($el);
  }
  
  $xml->appendChild($call);
  $doc->appendChild($xml);
  return $doc->saveXML();
}



// Calls Auth.Authenticate: https://api.yourmembership.com/reference/2_30/Auth_Authenticate.htm
function is_user_logged_in_ym($usr = '', $pwd = '') {
  
    
  $api_response = new stdClass();
  $api_response->ErrCode = 9001;
  
	
  // Create session 
  // Calls Session.Create: https://api.yourmembership.com/reference/2_30/Session_Create.htm
  $api_response = get_api_response(create_xml('Session.Create', '001', ''));
  
  
	
  // Authenticate submitted user credentials if the session created
  if ($api_response->ErrCode == 0) {
  	$sessionID = (string)$api_response->{"Session.Create"}->SessionID;
	
	
	$api_response = get_api_response(create_xml(
                        'Auth.Authenticate',
                        '002',
                        $sessionID,
                        array(
                          'Username' => $usr,
                          'Password' => $pwd,
                       )));
    
	
    $user['uid'] = (string)$api_response->{"Auth.Authenticate"}->ID;

	
 	if ($user['uid']) {
		
		$api_response = get_api_response(create_xml(
                        'Member.Profile.Get',
                        '003',
                        $sessionID,
                        array(
                          'Username' => $usr,
                          'Password' => $pwd,
                       )));
		
		//complete member profile information- $user array will have complete profile information that require for synch meber profile in CRF website
		$user['EmailAddr'] = (string)$api_response->{"Member.Profile.Get"}->EmailAddr;
		
		$user['NamePrefix'] = (string)$api_response->{"Member.Profile.Get"}->NamePrefix;
		$user['FirstName'] = (string)$api_response->{"Member.Profile.Get"}->FirstName;
		$user['LastName'] = (string)$api_response->{"Member.Profile.Get"}->LastName;
		$user['Mobile'] = (string)$api_response->{"Member.Profile.Get"}->Mobile;
		$user['EmpPhone'] = (string)$api_response->{"Member.Profile.Get"}->EmpPhone;
		$user['Employer'] = (string)$api_response->{"Member.Profile.Get"}->Employer;
		$user['Title'] = (string)$api_response->{"Member.Profile.Get"}->Title;
		$user['EmpAddrLine1'] = (string)$api_response->{"Member.Profile.Get"}->EmpAddrLine1;	
		$user['EmpAddrLine2'] = (string)$api_response->{"Member.Profile.Get"}->EmpAddrLine2;
		$user['EmpCity'] = (string)$api_response->{"Member.Profile.Get"}->EmpCity;
		$user['EmpPostalCode'] = (string)$api_response->{"Member.Profile.Get"}->EmpPostalCode;
		$user['EmpLocation'] = (string)$api_response->{"Member.Profile.Get"}->EmpLocation;
		$user['EmpCountry'] = (string)$api_response->{"Member.Profile.Get"}->EmpCountry;
		$user['Membership'] = (string)$api_response->{"Member.Profile.Get"}->MemberTypeCode;
		
		
		
		
		
		$api_results = (array)$api_response->{"Member.Profile.Get"};
		
		$custom_values = $api_results['CustomFieldResponses']->{"CustomFieldResponse"};
		
		for($i=0; $i<count($custom_values);$i++) {
			
			
			if( $custom_values[$i]['FieldCode'] == "PA1" )
			{
				//Practice Area(s) values
				$user['Practiceareas'] = (array)$custom_values[$i]->{"Values"}->Value;
			}
			
			if( $custom_values[$i]['FieldCode'] == "LIURL" )
			{
				//Linkedin URL
				$user['linkedinURL'] = (string)$custom_values[$i]->{"Values"}->Value;
			}
			
			if( $custom_values[$i]['FieldCode'] == "Bio" )
			{
				//Biography
				$user['Biography']  = (string)$custom_values[$i]->{"Values"}->Value;
			}
			
			if( $custom_values[$i]['FieldCode'] == "InSec" )
			{
				//Industry Sector
				$user['Insector']  = (string)$custom_values[$i]->{"Values"}->Value;
			}
			
			if( $custom_values[$i]['FieldCode'] == "OrgS" )
			{
				//Org Size
				$user['Orgsize']  = (string)$custom_values[$i]->{"Values"}->Value;
			}
			
			if( $custom_values[$i]['FieldCode'] == "keyu" )
			{
				//Key User
				$user['Keyuser']  = (string)$custom_values[$i]->{"Values"}->Value;
			}
			
			
		}
			
			
		//session id	
		$user['sessionid'] = $sessionID;
		

		
				
	    $api_response = get_api_response(create_xml(
	                        'Auth.CreateToken',
	                        '004',
	                        $sessionID,
	                        array(
	                          'Username' => $usr,
	                          'Password' => $pwd,
	                          'Persist'  => 1,
	                          //'RetUrl'	 => 'http://w3.org'
	                       )));

	    $user['AuthToken'] = (string)$api_response->{"Auth.CreateToken"}->AuthToken;
	    $user['GoToUrl'] = (string)$api_response->{"Auth.CreateToken"}->GoToUrl;

	    return $user;
	}//Got UserID
  }
  
  // The API returned errors. The user couldn't be logged in.
  return false;
}


















//This method will call if wp_authenticate invoked

function custom_authenticate($login) {

if ($found || $_SERVER['REQUEST_METHOD'] === 'POST') {

	//Get USername and password entered by User
	$username = (string)$_REQUEST['log'];
	$password = (string)$_REQUEST['pwd'];

	// To check YM having this account or not and authnticated. 
	//And this method will return both authenticated token and complete member profile details. 
	$ymuser_data = is_user_logged_in_ym($username, $password);
}	
  
$_SESSION['ymuser'] = $ymuser_data;

//To check valied YM user or not.
if ( $ymuser_data['uid']  && $ymuser_data['AuthToken'] &&  $ymuser_data['EmailAddr']) 
{
	
	$member_id = FALSE;
	$user_id = FALSE;
	
	//Update or create c crf site Member profile
	$member_id = ym_member_profille_update($username, $password, $ymuser_data);
	
	
	

	if($member_id)
	{
		$ymuser_data['member_id'] = $member_id;
		
		//Update or create c crf site user profile
		$user_id = ym_user_profille_update($username, $password, $ymuser_data);
	}
	 
	//update relationship claimed
	if($user_id)
	{
		$get_user_data = get_user_meta($member_id, $key = 'paupress_pp_relationship_claimed', $single = true);
		
		if (!in_array($user_id, $get_user_data))
		{
			$get_user_data[$user_id] = array("value" => $user_id ,"type" => "organization");
						
			update_user_meta_data($member_id,array("paupress_pp_relationship_claimed"=> $get_user_data));
		}
	}
	
	

}
	


}













//This method will call after user successfull login. And YM authenticated token keeping in Cookie.
function custom_login() {
	
if($_SESSION['ymuser']['AuthToken'])
{
    $ymurl1 = $_SESSION['ymuser']['GoToUrl'].",".$_SESSION['ymuser']['sessionid'].",".$_SESSION['ymuser']['AuthToken'];

	setcookie("yminfo", $ymurl1, time() + (86400 * 30), "/");

	//custom page redirection. This is for testing. Will remove after integration.
	//wp_redirect( home_url() .'/ympage');
	//exit();
}

}






	

	

//Adding forgot password link as YM site
add_filter( 'lostpassword_url',  'wdm_lostpassword_url', 10, 0 );
function wdm_lostpassword_url() {
    return "https://crf.site-ym.com/general/email_pass.asp";
}


//To check YM site anuthentication. 
//Adding following actions to overide default wordpress functionality.
add_action( 'wp_authenticate', 'custom_authenticate');
add_action( 'wp_login', 'custom_login',1 );



//custom logout 
function custom_logout() 
	{
		$ymdetails = explode(",",$_COOKIE['yminfo']);
		
		//Session Destroys  
		$api_response = get_api_response(create_xml(
	                        'Session.Abandon',
	                        '005',
	                        $ymdetails[1],
	                        array(                    
	                       )));
						   
		
		unset($_COOKIE['yminfo']);
		setcookie('yminfo', null, -1, '/');
		
	}

//To logout YM site.
//Adding following action. 
add_action( 'wp_logout','custom_logout');


//Update user meta data
function update_user_meta_data($user_id, $user_meta)
{

		foreach($user_meta as $key => $value) {
            // Will return false if the previous value is the same as $new_value.
			$updated = update_user_meta( $user_id, $key, $value );
 
			// So check and make sure the stored value matches $new_value.
			if ( $value != get_user_meta( $user_id,  $key, true ) ) 
			{
				wp_die( __( 'An error occurred : '.$key, 'textdomain' ) );
			}
        }
	
}


//Delete user meta data
function delete_user_meta_data($user_id, $user_delete_meta)
{
		foreach($user_delete_meta as $key => $value) {
			
			delete_user_meta($user_id, $value);

		}

}



//This function is called to return the ym event registration button link.
 function ym_login_create_event_link($regurl) {

		$returl = "";
		
		
		if(isset($_COOKIE['yminfo'])) 
		{
			$ymdetails = explode(",",$_COOKIE['yminfo']);
			$eventurl = urlencode($regurl);
			$returl =  "<a class=\"ym-event-register\" href=\"".$ymdetails[0].$eventurl."\"  target=\"_blank\">Register</a>";
		}
					
		return $returl;
}

//Update User profile in CRF site
function ym_user_profille_update($username,$password,$ymuser)
{
	global $wpdb;
		
	
	//prepre user meta data
	$user_meta = array();
	
	$geo_states =	ym_get_helper_states();
	$geo_countries =	ym_helper_country();
	 
	$user_meta['paupress_pp_prefix'] = $ymuser['NamePrefix'];
	
	$user_meta['first_name'] = $ymuser['FirstName'];
	$user_meta['last_name'] = $ymuser['LastName'];
	$user_meta['soak_pp_job_title'] = $ymuser['Title'];
	$user_meta['paupress_address_one_1'] = $ymuser['EmpAddrLine1'];
	$user_meta['paupress_address_two_1'] = $ymuser['EmpAddrLine2'];
	$user_meta['paupress_address_city_1'] = $ymuser['EmpCity'];
	$user_meta['paupress_address_postal_code_1'] = $ymuser['EmpPostalCode'];
	$user_meta['paupress_address_country_1'] = array_search($ymuser['EmpCountry'],$geo_countries,true);
	$user_meta['paupress_address_state_1'] = array_search($ymuser['EmpLocation'],$geo_states[$user_meta['paupress_address_country_1']],true);
	//$user_meta['wp_capabilities'] = 'a:2:{s:4:"user";b:1;s:8:"key-user";b:1;}';
	if(empty($user_meta['paupress_address_state_1']) )
		$user_meta['paupress_address_state_1'] = $ymuser['EmpLocation'];
	
	
	
 	
	$user_meta['soak_organization_id'] =  $ymuser['member_id'];
	
	//$user_meta['paupress_pp_relationship'] = 'a:1:{i:0;a:2:{s:5:"value";i:701;s:4:"type";s:12:"organization";}}';
	$user_meta['paupress_pp_relationship'] = array( "0" => array( "value" => $ymuser['member_id'] ,"type" => "organization" ));
	$user_meta['organization'] = $ymuser['Employer'];
	
	
	$user_meta['wp_user_level'] = 0;
	$user_meta['paupress_pp_ind_type'] = "user";
	$user_meta['paupress_pp_user_type'] = "ind";
	$user_meta['soak_pp_ind_status'] = "ACTIVE";
	
	if($ymuser['Keyuser'] == "Yes" )
	{
		$user_meta['wp_capabilities'] = array("user"=>1,"key_user"=>TRUE);
		$user_meta['soak_pp_key_user'] = "true";
	}
	else
	{	
		$user_meta['wp_capabilities'] = array("user"=>1);
		$user_meta['soak_pp_key_user'] = "false";
	}
	
	$user_meta['description'] = $ymuser['Biography'];
	$user_meta['rich_editing'] = "TRUE";
	$user_meta['syntax_highlighting'] = "TRUE";
	$user_meta['admin_color'] = "fresh";
	
	$user_meta['use_ssl'] = 0;
	$user_meta['show_admin_bar_front'] = "TRUE";
	
	$user_meta['paupress_pp_public'] = 'a:16:{s:21:"soak_pp_last_modified";s:5:"false";s:18:"soak_pp_last_login";s:5:"false";s:19:"soak_pp_login_count";s:5:"false";s:18:"soak_pp_ind_status";s:5:"false";s:17:"soak_pp_job_title";s:5:"false";s:23:"soak_pp_last_event_date";s:5:"false";s:29:"soak_pp_events_attended_count";s:5:"false";s:11:"description";s:5:"false";s:9:"telephone";s:5:"false";s:13:"address_one_1";s:5:"false";s:13:"address_two_1";s:5:"false";s:14:"address_city_1";s:5:"false";s:15:"address_state_1";s:5:"false";s:17:"address_country_1";s:5:"false";s:21:"address_postal_code_1";s:5:"false";s:20:"soak_pp_linkedin_url";s:5:"false";}';
	
	
	$user_meta['telephone'] = array(array("value" => $ymuser['EmpPhone'],"type" => "work"),array("value" => "","type" => "private"),
									array("value" => $ymuser['Mobile'],"type" => "mobile"),array("value" => "","type" => "fax"));
	$user_meta['soak_pp_subscription_type'] = "NONE";
		
	
	
	$user_meta['soak_new_user_email_sent'] = 1;
	$user_meta['soak_pp_linkedin_url'] = $ymuser['linkedinURL'];
	
	$querystr = "SELECT tt.description as `Practice Area`, CONCAT('soak_channel__', t.name) AS `meta_key`, tt.term_id AS `meta_value` FROM wp_term_taxonomy AS tt INNER JOIN wp_terms AS t ON tt.term_id = t.term_id AND taxonomy = 'soak_channel' ";
	$results = $wpdb->get_results($querystr);

	$pa_count = count($ymuser['Practiceareas']);
	$delete_user_meta = array();
	for($i=0;$i<count($results);$i++)
	{	

		$pa_key = $results[$i]->{"meta_key"};
		$pa_value = $results[$i]->{"meta_value"};
		$delete_user_meta[] = $pa_key;
		
		if( $pa_count >= 1 )
		{

			for($j=0;$j<$pa_count; $j++)
			{
				$pa_current = html_entity_decode($results[$i]->{"Practice Area"});
				$pa_update = $ymuser['Practiceareas'][$j];

				if( strcasecmp(trim($pa_update),trim($pa_current)) == 0 ) 
					{ 
						$user_meta[$pa_key] = $pa_value;
									
					}
			}
		}
		else
		{
					$user_meta[$pa_key] = $pa_value;
		}
	}
	//die();
	
	$user_meta['soak_pp_last_modified'] = date("m/d/y H:i");
	
	
	//To check the same user exists in CRF website or not. If not exists will create new account in CRF website
	$user_id = username_exists( $username );

	if(!$user_id)
		$user_id = email_exists($username);

	
	if ( !$user_id and email_exists($ymuser['EmailAddr']) == false ) {
		$user_id = wp_create_user( $username, $password, $ymuser['EmailAddr'] );

		$user_meta['soak_pp_show_contact_email'] = "FALSE";
		$user_meta['soak_pp_show_contact_telephone'] = "FALSE";
		
		
		//update meta data
		update_user_meta_data($user_id,$user_meta);
		
		return $user_id;
		
	} 
		//if user exist in CRF site
	elseif ($user_id)
	{
		//If user exists update password for user CRF website to sysnch password in both YM and CRF website. 
		//Also update member profile information to synch both YM and CRF website
		wp_set_password($password,$user_id);
		
		//update email address
		wp_update_user( array( 'ID' => $user_id, 'user_email' => $ymuser['EmailAddr'] ) );
		
		//delete meta practice areas
		delete_user_meta_data($user_id,$delete_user_meta);
		
		//update meta data
		update_user_meta_data($user_id,$user_meta);
		
				
	return $user_id;
	}
	else
	{
		 echo $user->login;  //if both cases or not valied then redirecting to login.
	}

	//if user exists in YM and if password entered wrong then it will redirected to login CRF website.
	if ( is_wp_error($user) )
		echo $user->login;	
	

	 return false;
}





//Update or create member profile in CRF site
function ym_member_profille_update($username,$password,$ymuser)
{
	
	//adding member_ to username to create dummy member username
	$username = "member+".$username;
	$email = "member+".$ymuser['EmailAddr'];
	
	
	
	//prepre user meta data
	$member_meta = array();
	
	$geo_states =	paupress_get_helper_states();
	$geo_countries =	paupress_helper_country();

	
	$member_meta['nickname'] = $ymuser['Employer'];
	
	$member_meta['paupress_address_organization_1'] = $ymuser['Employer'];
	$member_meta['paupress_pp_contact'] = "FALSE";
	$member_meta['soak_pp_city_for_listing'] = $ymuser['EmpCity'];
	$member_meta['soak_pp_country_for_listing'] = $ymuser['EmpCountry'];
	
	//$member_meta['paupress_pp_relationship_claimed'] = array("22651" => array("value" => 22651,"type" => "organization"));
        
	$member_meta['wp_capabilities'] = array("organization"=>1);
 	
	
	$member_meta['organization'] = $ymuser['Employer'];
	
	$member_meta['wp_user_level'] = 0;
	$member_meta['paupress_pp_org_type'] = "member";
	$member_meta['paupress_pp_user_type'] = "org";
	
	$org_status_type = array("Lite"=>"Europe",
							 "Stnd"=>"United Kingdom",
							 "Besp"=>"United Kingdom",
							 "Intl"=>"International",
							 "Virt"=>"Virtual",
							 "Ptn"=>"SPONSOR",
							 "Pro1"=>"PROSPECT");
	

	$member_meta['soak_pp_org_status'] = "ACTIVE";
	$member_meta['soak_pp_member_type'] = "";
	
	
	
	if($ymuser['Membership'] == "Lite" || $ymuser['Membership'] == "Stnd" ||
	   $ymuser['Membership'] == "Besp" || $ymuser['Membership'] == "Intl" || 
	   $ymuser['Membership'] == "Virt" )
	{  
		$member_meta['soak_pp_member_type'] = $org_status_type[$ymuser['Membership']];
	}
	
	if($ymuser['Membership'] == "Ptn" || $ymuser['Membership'] == "Pro1")
	{
		$member_meta['soak_pp_org_status'] = $org_status_type[$ymuser['Membership']];	
	}
	
		
	 
	if($ymuser['Keyuser'] == "Yes" )
		$member_meta['soak_pp_key_user'] = "TRUE";
	else
		$member_meta['soak_pp_key_user'] = "FALSE";
	
	$member_meta['soak_pp_industry_sector'] = $ymuser['Insector'];
	$member_meta['paupress_pp_subscription'] = "TRUE";
	$member_meta['rich_editing'] = "TRUE";
	$member_meta['syntax_highlighting'] = "TRUE";
	$member_meta['admin_color'] = "fresh";
	$member_meta['comment_shortcuts'] = "FALSE";
	$member_meta['use_ssl'] = 0;
	$member_meta['show_admin_bar_front'] = "TRUE";
	$member_meta['dismissed_wp_pointers'] = "wp496_privacy";
	
	
	$member_meta['soak_pp_size'] = $ymuser['Orgsize'];
	$member_meta['user_email'] = $email;
	$member_meta['soak_new_user_email_sent'] = 1;
	$member_meta['soak_pp_users_can_register_events'] = "true";

	$member_meta['soak_pp_company_number'] = $ymuser['EmpPhone'];
	$member_meta['telephone'] = array();
	$member_meta['soak_pp_divisions'] = array();
	$member_meta['soak_pp_last_modified'] = date("m/d/y H:i");
	
	
	$args = array(
	'meta_query' => array(
		'relation' => 'AND',
			array(
				'key'     => 'paupress_pp_org_type',
				'value'   => 'member',
	 			'compare' => '='
			),
			array(
				'key'     => 'organization',
				'value'   => $member_meta['organization'],
				'compare' => '='
			)
	)
	
	);
	
	//Get member id from crf wp site
	$user_query = new WP_User_Query( $args );
	$results = $user_query->get_results();
	
	
	$user_id = FALSE;
	
	if($results[0]->ID)
		$user_id = $results[0]->ID;

	if($ymuser['Keyuser'] == "Yes" )
	{
	
		if ( !$user_id and email_exists($email) == false ) {
			
			$user_id = wp_create_user( $username, $password, $email );

		
			//update meta data
			update_user_meta_data($user_id,$member_meta);
			
			
			return $user_id;
			
		} 
			//if user exist in CRF site
		elseif ($user_id) 
		{
			//If user exists update password for user CRF website to sysnch password in both YM and CRF website. 
			//Also update member profile information to synch both YM and CRF website
			//wp_set_password($password,$user_id);
			//update meta data
			
			
			
			update_user_meta_data($user_id,$member_meta);
			
			//$get_user_data = get_user_meta($user_id, $key = '', $single = false);
			
			
			return $user_id;
		}
		else
		{
			 echo $user->login;  //if both cases or not valied then redirecting to login.
		}

		//if user exists in YM and if password entered wrong then it will redirected to login CRF website.
		if ( is_wp_error($user) )
			echo $user->login;	
	}
	else
	{
		return $user_id;
	}

	return false;
}


add_action( 'profile_update', 'ym_member_profile_update', 10, 2 );


?>



