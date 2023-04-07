<?php

  // get countries list by function
  function xtc_gcnfic2($code, $shorts = false, $spacer = ', ') {
	$data = explode(',', $code);
	$output = array();
	foreach ($data as $countries) {
		$input_query = xtDBquery("SELECT countries_name FROM ".TABLE_COUNTRIES." WHERE countries_iso_code_2 = '".$countries."'");
		$input = xtc_db_fetch_array($input_query, true);
		$output[] .= $input['countries_name'].(($shorts == 'true') ? ' ('.$countries.')' : '');
	}
	$output = implode($spacer, $output); 
	
	return $output;
  }

  // new Smarty instance
  $content_smarty = new Smarty;

  // get installed shipping modules
  $ship_modules = explode(';', MODULE_SHIPPING_INSTALLED);
  $exclude_array = array('.php', 'dpd', 'selfpickup', 'productsfreeshipping', 'freeshipping');
  $ship_modules = str_replace($exclude_array, '', $ship_modules);

  // build arrays
  foreach ($ship_modules as $shipping_module) {
  
    if (is_file(DIR_WS_LANGUAGES . $_SESSION['language'] . '/modules/shipping/' . $shipping_module . '.php')) {
      include_once (DIR_WS_LANGUAGES . $_SESSION['language'] . '/modules/shipping/' . $shipping_module . '.php');
    }
    $zones_query = xtDBquery("SELECT COUNT(configuration_value) AS count FROM ".TABLE_CONFIGURATION." WHERE configuration_key LIKE 'MODULE_SHIPPING_".$shipping_module."_COUNTRIES_%'");
    $zones = xtc_db_fetch_array($zones_query, true);
    
    if (defined(strtoupper('MODULE_SHIPPING_' . $shipping_module . '_STATUS')) && constant(strtoupper('MODULE_SHIPPING_' . $shipping_module . '_STATUS')) == 'True') {
      $shipping_array[] .= $shipping_module;
      $shipping_names[] .= constant(strtoupper('MODULE_SHIPPING_' . $shipping_module . '_TEXT_TITLE'));
      $num_of_zones[] 	.= (($shipping_module == 'flat') ? 1 : $zones['count'])+1;
    }
    
  }

  for($w = 0; $w < count($shipping_array); $w++) {
	
  	  $active_query = xtc_db_query("SELECT configuration_value FROM ".TABLE_CONFIGURATION." WHERE configuration_key = 'MODULE_SHIPPING_".$shipping_array[$w]."_STATUS'");
  	  $active = xtc_db_fetch_array($active_query);
  	
	  if ($active['configuration_value'] == 'True' || $active['configuration_value'] == 'true') {
		
		$content_smarty->assign('MODUL_ACTIVE', '1');
		
		$shipping_list[] = array('NAME' => $shipping_names[$w],
							     'LINK' => xtc_href_link(FILENAME_CONTENT, 'coID='.$_REQUEST['coID'], 'SSL').'#'. str_replace(' ', '_', $shipping_names[$w])
							    );
							    
		$content_smarty->assign('CHOOSE_YOUR_OPTION', CHOOSE_YOUR_OPTION);
		$content_smarty->assign('SHIPPING_ZONES_TEXT', SHIPPING_ZONES);

		$handling_query = xtc_db_query("SELECT configuration_value AS value FROM ".TABLE_CONFIGURATION." WHERE configuration_key = 'MODULE_SHIPPING_".$shipping_array[$w]."_HANDLING'");
		$handling = xtc_db_fetch_array($handling_query);
		$content_smarty->assign('COUNTRY_SHIPPING_HANDLING_TEXT', COUNTRY_SHIPPING_HANDLING_TEXT);

		if ($_SESSION['customers_status']['customers_status_show_price_tax'] == '1') {
			$tax_query = xtc_db_query("SELECT configuration_value FROM ".TABLE_CONFIGURATION." WHERE configuration_key = 'MODULE_SHIPPING_".$shipping_array[$w]."_TAX_CLASS'");
			$tax = xtc_db_fetch_array($tax_query);
			$tax_class = $tax['configuration_value'];
		}

		$shipping_allowed_query = xtc_db_query("SELECT configuration_value FROM ".TABLE_CONFIGURATION." WHERE configuration_key = 'MODULE_SHIPPING_".$shipping_array[$w]."_ALLOWED'");
		$shipping_allowed = xtc_db_fetch_array($shipping_allowed_query);
		$shipping_allowed_true = xtc_db_num_rows($shipping_allowed);
		
		$content_smarty->assign('COUNTRY_SHIPPING_ALLOWED_TEXT', COUNTRY_SHIPPING_ALLOWED_TEXT);

		$countries_array[$w] = array('SHIPPING_NAME' => $shipping_names[$w],
									 'SHIPPING_LINK' => str_replace(' ', '_', $shipping_names[$w]),
									 'SHIPPING_TOP_LINK' => '<a style="float:right;" href="'.xtc_href_link(FILENAME_CONTENT, 'coID='.$_REQUEST['coID'], 'SSL').'#top">'.xtc_image('admin/images/arrow_up_green.gif', 'TOP', '', '', 'title="'.TOP.'"').'</a>',
									 'SHIPPING_HANDLING' => (($handling['value'] > 0) ? $xtPrice->xtcFormat($handling['value'], true, $tax_class) : ''),
									 'SHIPPING_ALLOWED' => xtc_gcnfic2($shipping_allowed['configuration_value'], true),
									 'SHIPPING_NOZ' => $num_of_zones[$w]-1
									);
									

		for($i = 1; $i < $num_of_zones[$w]; $i++) {
		
		  if ($shipping_array[$w] == 'flat') {
			  $countries_query = xtc_db_query("SELECT configuration_value FROM ".TABLE_CONFIGURATION." WHERE configuration_key = 'MODULE_SHIPPING_".$shipping_array[$w]."_ALLOWED'");
			  $countries = xtc_db_fetch_array($countries_query);
			  $new_countries_list = xtc_gcnfic2($countries['configuration_value'], true);
		  } else {
			  $countries_query = xtc_db_query("SELECT configuration_value FROM ".TABLE_CONFIGURATION." WHERE configuration_key = 'MODULE_SHIPPING_".$shipping_array[$w]."_COUNTRIES_".$i."'");
			  $countries = xtc_db_fetch_array($countries_query);
			  $new_countries_list = xtc_gcnfic2($countries['configuration_value'], true);
		  }
		  if ($shipping_array[$w] == 'flat') {
			  $cost_query = xtc_db_query("SELECT configuration_value FROM ".TABLE_CONFIGURATION." WHERE configuration_key = 'MODULE_SHIPPING_".$shipping_array[$w]."_COST'");
			  $cost = xtc_db_fetch_array($cost_query);
			  $cost_table = $cost['configuration_value'];
		  } elseif ($shipping_array[$w] == 'chp') {
			  $cost1_query = xtc_db_query("SELECT configuration_value FROM ".TABLE_CONFIGURATION." WHERE configuration_key = 'MODULE_SHIPPING_".$shipping_array[$w]."_COST_ECO_".$i."'");
			  $cost1 = xtc_db_fetch_array($cost1_query);
			  $cost2_query = xtc_db_query("SELECT configuration_value FROM ".TABLE_CONFIGURATION." WHERE configuration_key = 'MODULE_SHIPPING_".$shipping_array[$w]."_COST_PRI_".$i."'");
			  $cost2 = xtc_db_fetch_array($cost2_query);
			  $cost3_query = xtc_db_query("SELECT configuration_value FROM ".TABLE_CONFIGURATION." WHERE configuration_key = 'MODULE_SHIPPING_".$shipping_array[$w]."_COST_URG_".$i."'");
			  $cost3 = xtc_db_fetch_array($cost3_query);
			  $cost1_table = preg_split("/[:,]/", $cost1['configuration_value']);
			  $cost2_table = preg_split("/[:,]/", $cost2['configuration_value']);
			  $cost3_table = preg_split("/[:,]/", $cost3['configuration_value']);
		  } elseif ($shipping_array[$w] == 'dhl') {
			  /* SPECIAL QUERIES FOR DHL SHIPPING MODULE */
		  } else {
			  $cost_query = xtc_db_query("SELECT configuration_value FROM ".TABLE_CONFIGURATION." WHERE configuration_key = 'MODULE_SHIPPING_".$shipping_array[$w]."_COST_".$i."'");
			  $cost = xtc_db_fetch_array($cost_query);
			  $cost_table = preg_split("/[:,]/", $cost['configuration_value']);
		  }

		  if ($shipping_array[$w] == 'flat') {
			  $new_weight_cost_list .= '<tr>
										  <td style="background-color:'.($b%4?'#eeeeee':'#ffffff').';width:50%;">'.COUNTRY_COST_FLAT.'</td>
										  <td style="background-color:'.($b%4?'#eeeeee':'#ffffff').';width:50%;text-align:right;"> '. $xtPrice->xtcFormat($cost_table, true, $tax_class) .'</td>
									    </tr>';
		  } elseif ($shipping_array[$w] == 'chp') {
			  for($b = 0; $b < count($cost1_table); $b++) {
			    $new_weight_cost_list .= '<tr>
										    <td style="background-color:#ffffff;width:34%;">'. COUNTRY_COST_BY . $cost1_table[$b] . COUNTRY_WEIGHT .'</td>
										    <td style="background-color:#ffffff;width:33%;">'.ECO.'</td>
										    <td style="background-color:#ffffff;width:33%;text-align:right;"> '. $xtPrice->xtcFormat($cost1_table[$b+1], true, $tax_class) .'</td>
									      </tr>';
				$b++;	
			  }
			  for($b = 0; $b < count($cost2_table); $b++) {
			    $new_weight_cost_list .= '<tr>
										    <td style="background-color:#eeeeee;width:34%;">'. COUNTRY_COST_BY . $cost2_table[$b] . COUNTRY_WEIGHT .'</td>
										    <td style="background-color:#eeeeee;width:33%;">'.PRI.'</td>
										    <td style="background-color:#eeeeee;width:33%;text-align:right;"> '. $xtPrice->xtcFormat($cost2_table[$b+1], true, $tax_class) .'</td>
									      </tr>';
				$b++;	
			  }
			  if ($cost3_table[$b] != '') {
				  for($b = 0; $b < count($cost3_table); $b++) {
				    $new_weight_cost_list .= '<tr>
											    <td style="background-color:#ffffff;width:34%;">'. COUNTRY_COST_BY . $cost3_table[$b] . COUNTRY_WEIGHT .'</td>
											    <td style="background-color:#ffffff;width:33%;">'.URG.'</td>
											    <td style="background-color:#ffffff;width:33%;text-align:right;"> '. $xtPrice->xtcFormat($cost3_table[$b+1], true, $tax_class) .'</td>
										      </tr>';
					$b++;	
				  }
			  }
		  } elseif ($shipping_array[$w] == 'dhl') {
			  /* HTML OUTPUT FOR DHL SHIPPING MODULE */
		  } else {
			  for($b=0; $b < count($cost_table); $b++){
				$new_weight_cost_list .= '<tr>
											<td style="background-color:'.($b%4?'#eeeeee':'#ffffff').';width:50%;">'. COUNTRY_COST_BY . $cost_table[$b] . COUNTRY_WEIGHT .'</td>
											<td style="background-color:'.($b%4?'#eeeeee':'#ffffff').';width:50%;text-align:right;"> '. $xtPrice->xtcFormat($cost_table[$b+1], true, $tax_class).'</td>
										  </tr>';
				$b++;	
			  }
		  }
		  
		  $countries_array[$w]['SHIPPING_MODUL'][$i] = array('COUNTRIES' => $new_countries_list,
		  													 'WEIGHT_COST' => $new_weight_cost_list
		  													);
		  unset($new_countries_list);	
		  unset($new_weight_cost_list);		
		}
	}	
  }

  $content_smarty->assign('modul_list', $shipping_list);
  $content_smarty->assign('shipping_modules', $countries_array);
  $content_smarty->assign('COUNTRY_SHIPPING_TEXT', COUNTRY_SHIPPING);
  $content_smarty->assign('COUNTRY_COST_TEXT', COUNTRY_COST);
  $content_smarty->assign('language', $_SESSION['language']);
  $content_smarty->assign('tpl_path', DIR_WS_BASE.'templates/'.CURRENT_TEMPLATE.'/');
  $content_smarty->caching = 0;
  echo $content_smarty->fetch(CURRENT_TEMPLATE.'/module/shippingTable.html');


?>
