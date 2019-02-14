<?php
/**
Description: Member profile update from WP(CRF) to YM if save profive.
Author: Satyam Gollapalli
Version: 1.6
*/

// Calls Sa.People.Profile.Update : https://api.yourmembership.com/reference/2_30/Sa_People_Profile_Update.htm
function ym_member_profile_update($user_id, $old_user_data) {
  global $wpdb;

	//Get User and user meta data 
	$get_user_data = get_user_meta($user_id, $key = '', $single = false);
	$get_user_details = get_userdata($user_id);
  
  
	//Get Countries and Stages
	$geo_states =	ym_get_helper_states();
	$geo_countries =	ym_helper_country();
	
	//get Practice areas from WP ( CRF )
	$user_posts = array();
	$user_posts = get_post()->{"user"}->{"channels"};

	
	if($user_posts)
	{
	
	
	$querystr = "SELECT tt.description as `Practice Area`, CONCAT('soak_channel__', t.name) AS `meta_key`, tt.term_id AS `meta_value` FROM wp_term_taxonomy AS tt INNER JOIN wp_terms AS t ON tt.term_id = t.term_id AND taxonomy = 'soak_channel' ";
	$results = $wpdb->get_results($querystr);
	
	$pa_count = count($ymuser['Practiceareas']);
	
	$user_pa = array();
	for($i=0;$i<count($results);$i++) 
	{	
		$pa_key = explode("__",$results[$i]->{"meta_key"});
		//$pa_value = $results[$i]->{"meta_value"};
		
		if(in_array($pa_key[1],$user_posts))
		{
			$user_pa[] =  html_entity_decode($results[$i]->{"Practice Area"});
		}
	}
	
	//get Phone numbers from WP ( CRF )
	$user_ph = array();
	$user_ph = unserialize($get_user_data['telephone'][0]);

	foreach($user_ph as $k => $v)
	{	
		if($v['type'] == "work" )
		{	
			$user_work_ph = $v['value'];
		}
		if($v['type'] == "mobile" )
		{
			$user_mobile_ph = $v['value'];
		}
	}
	
	$api_response = new stdClass();
	$api_response->ErrCode =	 9001;

	
	$api_response = get_api_response(create_sa_xml(
                        'Sa.People.Profile.FindID',
                        '010'.rand(),
						NULL,
                        array(
						  'Username'=>$get_user_details->{"user_login"}
						)
					));
   
   
	$uid = (string)$api_response->{"Sa.People.Profile.FindID"}->ID;
	

	
	$user_state = $geo_states[$get_user_data['paupress_address_country_1'][0]][$get_user_data['paupress_address_state_1'][0]];
	
	if(empty($user_state)) 
		$user_state = $get_user_data['paupress_address_state_1'][0];
	
	
	$api_response = get_api_response(create_sa_xml(
                        'Sa.People.Profile.Update',
                        '008'.rand(),
						NULL,
                        array(
						  'ID'=>$uid,
						  'FirstName' => $get_user_data['first_name'][0],
						  'LastName' => $get_user_data['last_name'][0],
						  'EmailAddr' => $get_user_details->{"user_email"},
						  'Employer' => $get_user_data['organization'][0],
						  'Title' => $get_user_data['soak_pp_job_title'][0],
						  'EmpAddrLines' => $get_user_data['paupress_address_one_1'][0].','.$get_user_data['paupress_address_two_1'][0],
						  'EmpCity' => $get_user_data['paupress_address_city_1'][0],
						  'EmpLocation' => $user_state,
						  'EmpCountry' => $geo_countries[$get_user_data['paupress_address_country_1'][0]],
						  'EmpPostalCode' => $get_user_data['paupress_address_postal_code_1'][0],
						  'Mobile' => $user_mobile_ph,
						  'EmpPhone' => $user_work_ph
						   ),
						$user_pa,
						$get_user_data['soak_pp_linkedin_url'][0],
						$get_user_data['description'][0]
						));
    
	
	return $api_response;
	}
  
  // The API returned errors. The user couldn't be logged in.
  return false;
}









function ym_helper_country() {

        return apply_filters( 'ym_helper_country', array(
                                'AF' => __('Afghanistan', 'paupress'),
                                'AX' => __('&#197;land Islands', 'paupress'),
                                'AL' => __('Albania', 'paupress'),
                                'DZ' => __('Algeria', 'paupress'),
                                'AD' => __('Andorra', 'paupress'),
                                'AO' => __('Angola', 'paupress'),
                                'AI' => __('Anguilla', 'paupress'),
                                'AQ' => __('Antarctica', 'paupress'),
                                'AG' => __('Antigua and Barbuda', 'paupress'),
                                'AR' => __('Argentina', 'paupress'),
                                'AM' => __('Armenia', 'paupress'),
                                'AW' => __('Aruba', 'paupress'),
                                'AU' => __('Australia', 'paupress'),
                                'AT' => __('Austria', 'paupress'),
                                'AZ' => __('Azerbaijan', 'paupress'),
                                'BS' => __('Bahamas', 'paupress'),
                                'BH' => __('Bahrain', 'paupress'),
                                'BD' => __('Bangladesh', 'paupress'),
                                'BB' => __('Barbados', 'paupress'),
                                'BY' => __('Belarus', 'paupress'),
                                'BE' => __('Belgium', 'paupress'),
                                'BZ' => __('Belize', 'paupress'),
                                'BJ' => __('Benin', 'paupress'),
                                'BM' => __('Bermuda', 'paupress'),
                                'BT' => __('Bhutan', 'paupress'),
                                'BO' => __('Bolivia', 'paupress'),
                                'BA' => __('Bosnia and Herzegovina', 'paupress'),
                                'BW' => __('Botswana', 'paupress'),
								'BR' => __('Brazil', 'paupress'),
                                'IO' => __('British Indian Ocean Territory', 'paupress'),
                                'VG' => __('British Virgin Islands', 'paupress'),
                                'BN' => __('Brunei', 'paupress'),
                                'BG' => __('Bulgaria', 'paupress'),
                                'BF' => __('Burkina Faso', 'paupress'),
                                'BI' => __('Burundi', 'paupress'),
                                'KH' => __('Cambodia', 'paupress'),
                                'CM' => __('Cameroon', 'paupress'),
                                'CA' => __('Canada', 'paupress'),
                                'CV' => __('Cape Verde', 'paupress'),
                                'KY' => __('Cayman Islands', 'paupress'),
                                'CF' => __('Central African Republic', 'paupress'),
                                'TD' => __('Chad', 'paupress'),
                                'CL' => __('Chile', 'paupress'),
                                'CN' => __('China', 'paupress'),
                                'CX' => __('Christmas Island', 'paupress'),
                                'CC' => __('Cocos (Keeling) Islands', 'paupress'),
                                'CO' => __('Colombia', 'paupress'),
                                'KM' => __('Comoros', 'paupress'),
                                'CG' => __('Congo (Brazzaville)', 'paupress'),
                                'CD' => __('Congo (Kinshasa)', 'paupress'),
                                'CK' => __('Cook Islands', 'paupress'),
                                'CR' => __('Costa Rica', 'paupress'),
                                'HR' => __('Croatia', 'paupress'),
                                'CU' => __('Cuba', 'paupress'),
                                'CY' => __('Cyprus', 'paupress'),
                                'CZ' => __('Czech Republic', 'paupress'),
                                'DK' => __('Denmark', 'paupress'),
                                'DJ' => __('Djibouti', 'paupress'),
                                'DM' => __('Dominica', 'paupress'),
                                'DO' => __('Dominican Republic', 'paupress'),
                                'EC' => __('Ecuador', 'paupress'),
                                'EG' => __('Egypt', 'paupress'),
                                'SV' => __('El Salvador', 'paupress'),
                                'GQ' => __('Equatorial Guinea', 'paupress'),
                                'ER' => __('Eritrea', 'paupress'),
                                'EE' => __('Estonia', 'paupress'),
                                'ET' => __('Ethiopia', 'paupress'),
                                'FK' => __('Falkland Islands', 'paupress'),
								'FO' => __('Faroe Islands', 'paupress'),
                                'FJ' => __('Fiji', 'paupress'),
                                'FI' => __('Finland', 'paupress'),
                                'FR' => __('France', 'paupress'),
                                'GF' => __('French Guiana', 'paupress'),
                                'PF' => __('French Polynesia', 'paupress'),
                                'TF' => __('French Southern Territories', 'paupress'),
                                'GA' => __('Gabon', 'paupress'),
                                'GM' => __('Gambia', 'paupress'),
                                'GE' => __('Georgia', 'paupress'),
                                'DE' => __('Germany', 'paupress'),
                                'GH' => __('Ghana', 'paupress'),
                                'GI' => __('Gibraltar', 'paupress'),
                                'GR' => __('Greece', 'paupress'),
                                'GL' => __('Greenland', 'paupress'),
                                'GD' => __('Grenada', 'paupress'),
                                'GP' => __('Guadeloupe', 'paupress'),
                                'GT' => __('Guatemala', 'paupress'),
                                'GG' => __('Guernsey', 'paupress'),
                                'GN' => __('Guinea', 'paupress'),
                                'GW' => __('Guinea-Bissau', 'paupress'),
                                'GY' => __('Guyana', 'paupress'),
                                'HT' => __('Haiti', 'paupress'),
                                'HN' => __('Honduras', 'paupress'),
                                'HK' => __('Hong Kong', 'paupress'),
                                'HU' => __('Hungary', 'paupress'),
                                'IS' => __('Iceland', 'paupress'),
                                'IN' => __('India', 'paupress'),
                                'ID' => __('Indonesia', 'paupress'),
                                'IR' => __('Iran', 'paupress'),
                                'IQ' => __('Iraq', 'paupress'),
                                'IE' => __('Republic of Ireland', 'paupress'),
                                'IM' => __('Isle of Man', 'paupress'),
                                'IL' => __('Israel', 'paupress'),
                                'IT' => __('Italy', 'paupress'),
                                'CI' => __('Ivory Coast', 'paupress'),
                                'JM' => __('Jamaica', 'paupress'),
                                'JP' => __('Japan', 'paupress'),
                                'JE' => __('Jersey', 'paupress'),
								'JO' => __('Jordan', 'paupress'),
                                'KZ' => __('Kazakhstan', 'paupress'),
                                'KE' => __('Kenya', 'paupress'),
                                'KI' => __('Kiribati', 'paupress'),
                                'KW' => __('Kuwait', 'paupress'),
                                'KG' => __('Kyrgyzstan', 'paupress'),
                                'LA' => __('Laos', 'paupress'),
                                'LV' => __('Latvia', 'paupress'),
                                'LB' => __('Lebanon', 'paupress'),
                                'LS' => __('Lesotho', 'paupress'),
                                'LR' => __('Liberia', 'paupress'),
                                'LY' => __('Libya', 'paupress'),
                                'LI' => __('Liechtenstein', 'paupress'),
                                'LT' => __('Lithuania', 'paupress'),
                                'LU' => __('Luxembourg', 'paupress'),
                                'MO' => __('Macao S.A.R., China', 'paupress'),
                                'MK' => __('Macedonia', 'paupress'),
                                'MG' => __('Madagascar', 'paupress'),
                                'MW' => __('Malawi', 'paupress'),
                                'MY' => __('Malaysia', 'paupress'),
                                'MV' => __('Maldives', 'paupress'),
                                'ML' => __('Mali', 'paupress'),
                                'MT' => __('Malta', 'paupress'),
                                'MQ' => __('Martinique', 'paupress'),
                                'MR' => __('Mauritania', 'paupress'),
                                'MU' => __('Mauritius', 'paupress'),
                                'YT' => __('Mayotte', 'paupress'),
                                'MX' => __('Mexico', 'paupress'),
                                'MD' => __('Moldova', 'paupress'),
                                'MC' => __('Monaco', 'paupress'),
                                'MN' => __('Mongolia', 'paupress'),
                                'ME' => __('Montenegro', 'paupress'),
                                'MS' => __('Montserrat', 'paupress'),
                                'MA' => __('Morocco', 'paupress'),
                                'MZ' => __('Mozambique', 'paupress'),
                                'MM' => __('Myanmar', 'paupress'),
								'NA' => __('Namibia', 'paupress'),
                                'NR' => __('Nauru', 'paupress'),
                                'NP' => __('Nepal', 'paupress'),
                                'NL' => __('Netherlands', 'paupress'),
                                'AN' => __('Netherlands Antilles', 'paupress'),
                                'NC' => __('New Caledonia', 'paupress'),
                                'NZ' => __('New Zealand', 'paupress'),
                                'NI' => __('Nicaragua', 'paupress'),
                                'NE' => __('Niger', 'paupress'),
                                'NG' => __('Nigeria', 'paupress'),
                                'NU' => __('Niue', 'paupress'),
                                'NF' => __('Norfolk Island', 'paupress'),
                                'KP' => __('North Korea', 'paupress'),
                                'MP' => __('Northern Mariana Islands', 'paupress'),
                                'NO' => __('Norway', 'paupress'),
                                'OM' => __('Oman', 'paupress'),
                                'PK' => __('Pakistan', 'paupress'),
                                'PW' => __('Palau', 'paupress'),
                                'PS' => __('Palestinian Territory', 'paupress'),
                                'PA' => __('Panama', 'paupress'),
                                'PG' => __('Papua New Guinea', 'paupress'),
                                'PY' => __('Paraguay', 'paupress'),
                                'PE' => __('Peru', 'paupress'),
                                'PH' => __('Philippines', 'paupress'),
                                'PN' => __('Pitcairn', 'paupress'),
                                'PL' => __('Poland', 'paupress'),
                                'PT' => __('Portugal', 'paupress'),
                                'QA' => __('Qatar', 'paupress'),
                                'RE' => __('Reunion', 'paupress'),
                                'RO' => __('Romania', 'paupress'),
                                'RU' => __('Russia', 'paupress'),
                                'RW' => __('Rwanda', 'paupress'),
                                'BL' => __('Saint Barth&eacute;lemy', 'paupress'),
                                'SH' => __('Saint Helena', 'paupress'),
                                'KN' => __('Saint Kitts and Nevis', 'paupress'),
								'LC' => __('Saint Lucia', 'paupress'),
                                'MF' => __('Saint Martin (French part)', 'paupress'),
                                'PM' => __('Saint Pierre and Miquelon', 'paupress'),
                                'VC' => __('Saint Vincent and the Grenadines', 'paupress'),
                                'WS' => __('Samoa', 'paupress'),
                                'SM' => __('San Marino', 'paupress'),
                                'ST' => __('S&atilde;o Tom&eacute; and Pr&iacute;ncipe', 'paupress'),
                                'SA' => __('Saudi Arabia', 'paupress'),
                                'SN' => __('Senegal', 'paupress'),
                                'RS' => __('Serbia', 'paupress'),
                                'SC' => __('Seychelles', 'paupress'),
                                'SL' => __('Sierra Leone', 'paupress'),
                                'SG' => __('Singapore', 'paupress'),
                                'SK' => __('Slovakia', 'paupress'),
                                'SI' => __('Slovenia', 'paupress'),
                                'SB' => __('Solomon Islands', 'paupress'),
                                'SO' => __('Somalia', 'paupress'),
                                'ZA' => __('South Africa', 'paupress'),
                                'GS' => __('South Georgia/Sandwich Islands', 'paupress'),
                                'KR' => __('South Korea', 'paupress'),
                                'ES' => __('Spain', 'paupress'),
                                'LK' => __('Sri Lanka', 'paupress'),
                                'SD' => __('Sudan', 'paupress'),
                                'SR' => __('Suriname', 'paupress'),
                                'SJ' => __('Svalbard and Jan Mayen', 'paupress'),
                                'SZ' => __('Swaziland', 'paupress'),
                                'SE' => __('Sweden', 'paupress'),
                                'CH' => __('Switzerland', 'paupress'),
                                'SY' => __('Syria', 'paupress'),
                                'TW' => __('Taiwan', 'paupress'),
                                'TJ' => __('Tajikistan', 'paupress'),
                                'TZ' => __('Tanzania', 'paupress'),
                                'TH' => __('Thailand', 'paupress'),
                                'TL' => __('Timor-Leste', 'paupress'),
                                'TG' => __('Togo', 'paupress'),
                                'TK' => __('Tokelau', 'paupress'),
                                'TO' => __('Tonga', 'paupress'),
                                'TT' => __('Trinidad and Tobago', 'paupress'),
                                'TN' => __('Tunisia', 'paupress'),
                                'TR' => __('Turkey', 'paupress'),
								'TM' => __('Turkmenistan', 'paupress'),
                                'TC' => __('Turks and Caicos Islands', 'paupress'),
                                'TV' => __('Tuvalu', 'paupress'),
                                'UM' => __('US Minor Outlying Islands', 'paupress'),
                                'UG' => __('Uganda', 'paupress'),
                                'UA' => __('Ukraine', 'paupress'),
                                'AE' => __('United Arab Emirates', 'paupress'),
                                'GB' => __('United Kingdom', 'paupress'),
                                'US' => __('United States', 'paupress'),
                                'UY' => __('Uruguay', 'paupress'),
                                'UZ' => __('Uzbekistan', 'paupress'),
                                'VU' => __('Vanuatu', 'paupress'),
                                'VA' => __('Vatican', 'paupress'),
                                'VE' => __('Venezuela', 'paupress'),
                                'VN' => __('Vietnam', 'paupress'),
                                'WF' => __('Wallis and Futuna', 'paupress'),
                                'EH' => __('Western Sahara', 'paupress'),
                                'YE' => __('Yemen', 'paupress'),
                                'ZM' => __('Zambia', 'paupress'),
                                'ZW' => __('Zimbabwe', 'paupress'),
        ) );

}




function ym_get_helper_states() {

        // OZ -- NOTE: WE'VE MODIFIED SOUTH AUSTRALIA, WESTERN AUSTRALIA AND NORTHERN TERRITORY TO PREVENT CONFLICTS AND STANDARDIZE THE ABBREVIATIONS
        return apply_filters( 'ym_get_helper_states', array(
                'AU' => array(
                        'ACT' => __('Australian Capital Territory', 'paupress'),
                        'NSW' => __('New South Wales', 'paupress'),
                        'NTA' => __('Northern Territory', 'paupress'),
                        'QLD' => __('Queensland', 'paupress'),
                        'SAA' => __('South Australia', 'paupress'),
                        'TAS' => __('Tasmania', 'paupress'),
                        'VIC' => __('Victoria', 'paupress'),
                        'WAA' => __('Western Australia', 'paupress'),
                ),
                'CA' => array(
                        'AB' => __('Alberta', 'paupress'),
                        'BC' => __('British Columbia', 'paupress'),
                        'MB' => __('Manitoba', 'paupress'),
                        'NB' => __('New Brunswick', 'paupress'),
                        'NF' => __('Newfoundland', 'paupress'),
                        'NT' => __('Northwest Territories', 'paupress'),
                        'NS' => __('Nova Scotia', 'paupress'),
                        'NU' => __('Nunavut', 'paupress'),
                        'ON' => __('Ontario', 'paupress'),
                        'PE' => __('Prince Edward Island', 'paupress'),
                        'QC' => __('Quebec', 'paupress'),
                        'SK' => __('Saskatchewan', 'paupress'),
                        'YT' => __('Yukon Territory', 'paupress'),
                ),
				'US' => array(
                        'AL' => __('Alabama', 'paupress'),
                        'AK' => __('Alaska', 'paupress'),
                        'AZ' => __('Arizona', 'paupress'),
                        'AR' => __('Arkansas', 'paupress'),
                        'CA' => __('California', 'paupress'),
                        'CO' => __('Colorado', 'paupress'),
                        'CT' => __('Connecticut', 'paupress'),
                        'DE' => __('Delaware', 'paupress'),
                        'DC' => __('District Of Columbia', 'paupress'),
                        'FL' => __('Florida', 'paupress'),
                        'GA' => __('Georgia', 'paupress'),
                        'HI' => __('Hawaii', 'paupress'),
                        'ID' => __('Idaho', 'paupress'),
                        'IL' => __('Illinois', 'paupress'),
                        'IN' => __('Indiana', 'paupress'),
                        'IA' => __('Iowa', 'paupress'),
                        'KS' => __('Kansas', 'paupress'),
                        'KY' => __('Kentucky', 'paupress'),
                        'LA' => __('Louisiana', 'paupress'),
                        'ME' => __('Maine', 'paupress'),
                        'MD' => __('Maryland', 'paupress'),
                        'MA' => __('Massachusetts', 'paupress'),
                        'MI' => __('Michigan', 'paupress'),
                        'MN' => __('Minnesota', 'paupress'),
                        'MS' => __('Mississippi', 'paupress'),
                        'MO' => __('Missouri', 'paupress'),
                        'MT' => __('Montana', 'paupress'),
                        'NE' => __('Nebraska', 'paupress'),
                        'NV' => __('Nevada', 'paupress'),
                        'NH' => __('New Hampshire', 'paupress'),
                        'NJ' => __('New Jersey', 'paupress'),
                        'NM' => __('New Mexico', 'paupress'),
                        'NY' => __('New York', 'paupress'),
						'NC' => __('North Carolina', 'paupress'),
                        'ND' => __('North Dakota', 'paupress'),
                        'OH' => __('Ohio', 'paupress'),
                        'OK' => __('Oklahoma', 'paupress'),
                        'OR' => __('Oregon', 'paupress'),
                        'PA' => __('Pennsylvania', 'paupress'),
                        'RI' => __('Rhode Island', 'paupress'),
                        'SC' => __('South Carolina', 'paupress'),
                        'SD' => __('South Dakota', 'paupress'),
                        'TN' => __('Tennessee', 'paupress'),
                        'TX' => __('Texas', 'paupress'),
                        'UT' => __('Utah', 'paupress'),
                        'VT' => __('Vermont', 'paupress'),
                        'VA' => __('Virginia', 'paupress'),
                        'WA' => __('Washington', 'paupress'),
                        'WV' => __('West Virginia', 'paupress'),
                        'WI' => __('Wisconsin', 'paupress'),
                        'WY' => __('Wyoming', 'paupress'),
                        'AA' => __('Armed Forces Americas', 'paupress'),
                        'AE' => __('Armed Forces Europe', 'paupress'),
                        'AP' => __('Armed Forces Pacific', 'paupress'),
                        'AS' => __('American Samoa', 'paupress'),
                        'FM' => __('Micronesia', 'paupress'),
                        'GU' => __('Guam', 'paupress'),
                        'MH' => __('Marshall Islands', 'paupress'),
                        'PR' => __('Puerto Rico', 'paupress'),
                        'VI' => __('U.S. Virgin Islands', 'paupress'),
                ),
        ) );

}

						
						
						
						
						
						
						
						
						
?>


	
	


