<?php
/*
* Plugin Name: Recherche Multi Champs
* Plugin URI: https://wordpress.org/plugins/recherche-multi-champs/
* Description: Créer vos propres champs pour vos articles/pages et proposer une recherche basée sur ces champs à vos visiteurs.
* Version: 1.0.0
* Author: CreaLion.NET
* Author URI: https://crealion.net
* Text Domain: recherche-multi-champs
* Domain Path: /languages
*/
include_once plugin_dir_path( __FILE__ ).'/rmc_widget.php';

function rmc_install(){
	if (!isset($wpdb)) $wpdb = $GLOBALS['wpdb'];
    global $wpdb;
    $wpdb->query($wpdb->prepare("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}rmc_champs (id INT AUTO_INCREMENT PRIMARY KEY, cle VARCHAR(%d) NOT NULL, type_champs VARCHAR(3));", "255"));
    $wpdb->query($wpdb->prepare("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}rmc_options (id INT AUTO_INCREMENT PRIMARY KEY, cle VARCHAR(%d) NOT NULL, valeur TEXT);", "255"));
	$wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->prefix}rmc_options (cle, valeur) VALUES (%s, %s)", 'afficher_champs_articles', 'on'));
	$wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->prefix}rmc_options (cle, valeur) VALUES (%s, %s)", 'afficher_champs_pages', 'on'));
	$wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->prefix}rmc_options (cle, valeur) VALUES (%s, %s)", 'afficher_champs_vide', ''));
	$wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->prefix}rmc_options (cle, valeur) VALUES (%s, %s)", 'couleur_bordure', '#DDDDDD'));
	$wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->prefix}rmc_options (cle, valeur) VALUES (%s, %s)", 'epaisseur_bordure', '0'));
	$wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->prefix}rmc_options (cle, valeur) VALUES (%s, %s)", 'afficher_resultats_champs_vides', ''));
	$wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->prefix}rmc_options (cle, valeur) VALUES (%s, %s)", 'position_resultats', 'below'));
	$wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->prefix}rmc_options (cle, valeur) VALUES (%s, %s)", 'deleteTableOnUninstall', ''));
}

function rmc_deactivate(){
    global $wpdb;
}

function rmc_uninstallPlugin(){
	global $wpdb;
	$rmc_options = rmc_getOptions();
	if ($rmc_options['deleteTableOnUninstall'] == 'on'){
		$wpdb->query("DROP TABLE {$wpdb->prefix}rmc_champs");
		$wpdb->query("DROP TABLE {$wpdb->prefix}rmc_options");
	}
}

function rmc_register_rmc_widget(){
	register_widget('rmc_widget');
}

function rmc_add_admin_menu(){
    $hook = add_menu_page('Recherche Multi Champs', 'Recherche Multi Champs', 'manage_options', 'recherche-multi-champs', 'rmc_menu_html');
	add_action('load-'.$hook, 'rmc_process_action');
}

function rmc_process_action(){
	if (!current_user_can('administrator')){return;}
	global $rmc_options, $wpdb;
	
    if (isset($_POST['rmc_ajouter_champs'])) {
		check_admin_referer('ajouter_champs','rmc_ajouter_champs');
		$nouveau_champs = sanitize_text_field($_POST['rmc_addFieldName']);
        $nouveau_champs = str_replace(" ","_", $nouveau_champs);
        $type_champs = sanitize_text_field($_POST['rmc_addFieldType']);
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}rmc_champs WHERE cle = %s", $nouveau_champs));
        if (is_null($row)) {
            $wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->prefix}rmc_champs (cle, type_champs) VALUES (%s, %s)", $nouveau_champs, $type_champs));
            $myargs = array('post_type' => array('post', 'page'), 'numberposts' => -1);
            $mythe_query = get_posts($myargs);
            foreach ($mythe_query as $post){
            	add_post_meta($post->ID, $nouveau_champs, '', true);
            } 
        }
    }
    
	if (isset($_POST['rmc_supprimer_champs'])) {
		check_admin_referer('supprimer_champs','rmc_supprimer_champs');
        $champs = (int)$_POST['deleteField'];
		$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}rmc_champs WHERE id=%d", $champs));
    }
    
	if (isset($_POST['rmc_enregistrer_options'])) {
		check_admin_referer('enregistrer_options','rmc_enregistrer_options');
        $afficher_champs_articles = substr(sanitize_text_field($_POST['afficher_champs_articles']),0,2);
        $afficher_champs_pages = substr(sanitize_text_field($_POST['afficher_champs_pages']),0,2);
        $afficher_champs_vide = substr(sanitize_text_field($_POST['afficher_champs_vide']),0,2);
        $afficher_resultats_champs_vides = substr(sanitize_text_field($_POST['afficher_resultats_champs_vides']),0,2);
        $deleteTableOnUninstall = substr(sanitize_text_field($_POST['deleteTableOnUninstall']),0,2);
        $couleur_bordure = substr(sanitize_text_field($_POST['couleur_bordure']),0,7);
        $epaisseur_bordure = intval(sanitize_text_field($_POST['epaisseur_bordure']));
        $positions = ["above" => "above","below" => "below"];
        $position_resultats = $positions[sanitize_text_field($_POST['position_resultats'])];
        $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}rmc_options SET valeur = %s WHERE cle = 'afficher_champs_articles'", $afficher_champs_articles));
        $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}rmc_options SET valeur = %s WHERE cle = 'afficher_champs_pages'", $afficher_champs_pages));
        $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}rmc_options SET valeur = %s WHERE cle = 'afficher_champs_vide'", $afficher_champs_vide));
		$wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}rmc_options SET valeur = %s WHERE cle = 'afficher_resultats_champs_vides'", $afficher_resultats_champs_vides));
        $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}rmc_options SET valeur = %s WHERE cle = 'couleur_bordure'", $couleur_bordure));
        $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}rmc_options SET valeur = %s WHERE cle = 'epaisseur_bordure'", $epaisseur_bordure));
        $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}rmc_options SET valeur = %s WHERE cle = 'position_resultats'", $position_resultats));
        $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}rmc_options SET valeur = %s WHERE cle = 'deleteTableOnUninstall'", $deleteTableOnUninstall));
        $rmc_options = [];
    }
}

$rmc_options = array();
function rmc_getOptions(){
	global $rmc_options, $wpdb;
	if (sizeof($rmc_options) == 0){
		$resultats = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}rmc_options ORDER BY %s", "cle")) ;
		foreach ($resultats as $cv) {
			$rmc_options[$cv->cle] = $cv->valeur;
		}
	}
	if (!isset($rmc_options['afficher_resultats_champs_vides'])){
		$wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->prefix}rmc_options (cle, valeur) VALUES (%s, %s)", 'afficher_resultats_champs_vides', ''));
		$rmc_options['afficher_resultats_champs_vides'] = '';
	}
	if (!isset($rmc_options['position_resultats'])){
		$wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->prefix}rmc_options (cle, valeur) VALUES (%s, %s)", 'position_resultats', 'above'));
		$rmc_options['position_resultats'] = 'above';
	}
	if (!isset($rmc_options['deleteTableOnUninstall'])){
		$wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->prefix}rmc_options (cle, valeur) VALUES (%s, %s)", 'deleteTableOnUninstall', ''));
		$rmc_options['deleteTableOnUninstall'] = '';
	}
	return $rmc_options;
}

function rmc_menu_html(){
	global $wpdb;
	
	rmc_load_admin_js();
	rmc_load_admin_css();
	
	$tableTypes = [
		"NUM" => __('Numeric','recherche-multi-champs'), 
		"TEX" => __('Text','recherche-multi-champs')
	];
	
	$resultats = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}rmc_champs ORDER BY %s", "cle"));
	$listActualFields = [];
	foreach($resultats as $field){
		$listActualFields[] = [
			rmc_ui_fields([
				'type'			=> 'text',
				'value'			=> str_replace("_", " ", $field->cle)
			]),
			rmc_ui_fields([
				'type'			=> 'text',
				'value'			=> $tableTypes[$field->type_champs]
			]),
			rmc_ui_fields([
				'label'			=> __('Delete', 'recherche-multi-champs'),
				'type'			=> 'button',
				'onclick'		=> 'rmc_deleteField(\''.$field->id.'\')',
				'class'			=> 'button'
			])
		];
	}
	$displayTableActualFields = [
		'<form method="post" action="" id="rmc_formDeleteField">',
		rmc_get_wp_nonce_field('supprimer_champs','rmc_supprimer_champs'),
		rmc_ui_fields([
			'name' 			=> 'deleteField',
			'id' 			=> 'IddeleteField',
			'type' 			=> 'hidden',
			'value' 		=> '-1'
		]),
		rmc_ui_table([
			'columnsName'		=> [__('Fields','recherche-multi-champs'),__('Type', 'recherche-multi-champs'),''],
			'displayFooter'		=> false,
			'tbodyId'			=> 'rmc_listfields',
			'data'				=> $listActualFields
		]),
		'</form>'
	];
	
	$displayFormNewField = [
		'<form method="post" action="" id="rmc_formAddField">',
		rmc_get_wp_nonce_field('ajouter_champs','rmc_ajouter_champs'),
		rmc_ui_fields([
			'name' 			=> 'rmc_addFieldName',
			'label' 		=> __('Name','recherche-multi-champs'),
			'tip'			=> __('The name of the field','recherche-multi-champs'),
			'edit' 			=> true,
			'type' 			=> 'text',
			'class'			=> 'rmc_nochange',
		]),
		rmc_ui_fields([
			'name' 			=> 'rmc_addFieldType',
			'label' 		=> __('Type','recherche-multi-champs'),
			'tip'			=> __('Choose the type "Numeric" to allow your visitors to search >=, <= or = at a number','recherche-multi-champs'),
			'edit' 			=> true,
			'type' 			=> 'select',
			'value'			=> '<option value="TEX">'.__('Text','recherche-multi-champs').'</option><option value="NUM">'.__('Numeric','recherche-multi-champs').'</option>',
			'class'			=> 'rmc_nochange',
		]),
		rmc_ui_fields([
			'type'			=> 'link',
			'onclick'		=> "rmc_cancelEditField('add')",
			'classParent'	=> 'rmc_flexEnd',
			'id'			=> 'rmc_rmc_cancelEditFieldButton',
			'value'			=> '#fields',
			'linktext'		=> __('Cancel','recherche-multi-champs'),
			'brotherfield'	=> rmc_ui_fields([
				'label'			=> __('Add field', 'recherche-multi-champs'),
				'type'			=> 'button',
				'onclick'		=> 'rmc_addField()',
				'class'			=> 'button',
				'id'			=> 'rmc_rmc_addFieldButton',
			])
		]),
		"</form>"
	];
	
	$rmc_options = rmc_getOptions();
	
	$displayParameters1 = [
		rmc_get_wp_nonce_field('enregistrer_options','rmc_enregistrer_options'),
		rmc_ui_fields([
			'name' 			=> 'afficher_champs_articles',
			'label' 		=> __('Display fields in posts when calling shortcode [rmc_shortcode]','recherche-multi-champs'),
			'tip'			=> __('Display fields in posts when calling shortcode [rmc_shortcode]','recherche-multi-champs'),
			'edit' 			=> true,
			'type' 			=> 'checkbox',
			'class'			=> 'rmc_nochange',
			'labelTop'		=> true,
			'value'			=> $rmc_options['afficher_champs_articles']
		]),
		rmc_ui_fields([
			'name' 			=> 'afficher_champs_pages',
			'label' 		=> __('Display fields in pages when calling shortcode [rmc_shortcode]','recherche-multi-champs'),
			'tip'			=> __('Display fields in pages when calling shortcode [rmc_shortcode]','recherche-multi-champs'),
			'edit' 			=> true,
			'type' 			=> 'checkbox',
			'class'			=> 'rmc_nochange',
			'labelTop'		=> true,
			'value'			=> $rmc_options['afficher_champs_pages']
		]),
		rmc_ui_fields([
			'name' 			=> 'afficher_champs_vide',
			'label' 		=> __('Display empty fields when calling shortcode [rmc_shortcode]','recherche-multi-champs'),
			'tip'			=> __('Display empty fields when calling shortcode [rmc_shortcode]','recherche-multi-champs'),
			'edit' 			=> true,
			'type' 			=> 'checkbox',
			'class'			=> 'rmc_nochange',
			'labelTop'		=> true,
			'value'			=> $rmc_options['afficher_champs_vide']
		]),
		rmc_ui_fields([
			'name' 			=> 'afficher_resultats_champs_vides',
			'label' 		=> __('Display results for empty fields when calling shortcode [rmc_search_shortcode]','recherche-multi-champs'),
			'tip'			=> __('Display results for empty fields when calling shortcode [rmc_search_shortcode]','recherche-multi-champs'),
			'edit' 			=> true,
			'type' 			=> 'checkbox',
			'class'			=> 'rmc_nochange',
			'labelTop'		=> true,
			'value'			=> $rmc_options['afficher_resultats_champs_vides']
		]),
		rmc_ui_fields([
			'name' 			=> 'deleteTableOnUninstall',
			'label' 		=> __('Delete all data on uninstall','recherche-multi-champs'),
			'tip'			=> __('Do not activate this setting if you want to install the premium version or if you want to keep your settings / fields for later','recherche-multi-champs'),
			'edit' 			=> true,
			'type' 			=> 'checkbox',
			'class'			=> 'rmc_nochange',
			'labelTop'		=> true,
			'value'			=> $rmc_options['deleteTableOnUninstall']
		]),
	];
	
	$valuePositionResultats = '';
	$positions = [
		"above"		=> __('Above','recherche-multi-champs'),
		"below"		=> __('Below','recherche-multi-champs')
	];
	foreach($positions as $p => $t){
		if ($rmc_options['position_resultats'] == $p){$selected = 'selected';}else{$selected = '';}
		$valuePositionResultats .= '<option value="'.$p.'" '.$selected.'>'.$positions[$p].'</option>';
	}
	
	$displayParameters2 = [
		rmc_ui_fields([
			'name' 			=> 'couleur_bordure',
			'label' 		=> __('Color of form borders','recherche-multi-champs'),
			'tip'			=> __('Color of form borders','recherche-multi-champs'),
			'edit' 			=> true,
			'type' 			=> 'text',
			'class'			=> 'rmc_nochange',
			'value'			=> $rmc_options['couleur_bordure']
		]),
		rmc_ui_fields([
			'name' 			=> 'epaisseur_bordure',
			'label' 		=> __('Thickness of form borders','recherche-multi-champs'),
			'tip'			=> __('Thickness in pixels of form borders','recherche-multi-champs'),
			'edit' 			=> true,
			'type' 			=> 'number',
			'class'			=> 'rmc_nochange',
			'value'			=> $rmc_options['epaisseur_bordure']
		]),
		rmc_ui_fields([
			'name' 			=> 'position_resultats',
			'label' 		=> __('Position of the results','recherche-multi-champs'),
			'tip'			=> __('Position of the results in relation to the form','recherche-multi-champs'),
			'edit' 			=> true,
			'type' 			=> 'select',
			'value'			=> 	$valuePositionResultats,
			'class'			=> 'rmc_nochange',
		]),
		/*get_submit_button(__('Update parameters', 'recherche-multi-champs'),'button')*/
		'<div><p class="submit"><input type="button" class="button" value="'.__('Update settings', 'recherche-multi-champs').'" onclick="rmc_updateParameters(\'1-1\')"></p></div>'
	];
	
	$help = [];
	$help[] = ['h1',__('How to use the plugin?','recherche-multi-champs')];
	$help[] = ['h2',__('1. First of all','recherche-multi-champs')];
	$help[] = ['p',__('You have to create the fields you need. To do this, go to the Fields section and then on the right side of the screen. Enter the public name of the field, choose the type and click on the button to add the field','recherche-multi-champs')];
	$help[] = ['p',__('If you want to allow your visitors to search with a larger or lower comparison on a numeric, choose the numeric type for your field','recherche-multi-champs')];
	$help[] = ['p',__('For example, we want to display a field relating to a brand. This will allow visitors to find information about their favorite brand in your posts, pages or directly find products from that brand.<br>So we create a field called "Famous Brand" with the type "Text":','recherche-multi-champs')];
	$help[] = ['img','img/screen1.png'];
	
	
	$help[] = ['h2',__('2. Fill-in the fields','recherche-multi-champs')];
	$help[] = ['p',__('Now that the fields are created, you need to fill them in in your posts, pages or products. To do this, first check if the custom fields are displayed in your posts, pages and products: When you modify a post or a page, click on the button at the top right with the three vertical dots inside. Then choose "Options". Finally, click on "Custom fields" if it is not checked and accept the reload','recherche-multi-champs')];
	$help[] = ['img','img/screen2.png'];
	$help[] = ['p',__('To enable custom fields in products, click on "Screen Options" button on the top of the screen when you edit a product and then check "Custom Fields"','recherche-multi-champs')];
	$help[] = ['img','img/screen3.png'];
	$help[] = ['p',__('Now, for all your posts, pages and products, you have access to the custom fields at the bottom of the screen when you edit. Enter the value you want for each of them','recherche-multi-champs')];
	$help[] = ['img','img/screen4.png'];
	
	
	$help[] = ['h2',__('3. Display the fields','recherche-multi-champs')];
	$help[] = ['p',__('This step is facultative. If you want to display the fields and associated values to your visitors for the posts, pages and products, you juste have to insert the following shortcode in them: [rmc_shortcode]','recherche-multi-champs')];
	$help[] = ['img','img/screen5.png'];

	$help[] = ['p',__('Then, the results of this shortcode will give something like this for your visitors:','recherche-multi-champs')];
	$help[] = ['img','img/screen6.png'];	
	
	
	$help[] = ['h2',__('4. Insert the form','recherche-multi-champs')];
	$help[] = ['p', __('Now that all your fields are ready, you can offer search forms to your visitors and filter the posts, pages and products with these fields. There are two ways to insert a search form:<br>- Insert a form in the widgets<br>- Insert a form with the shortcode [rmc_search_shortcode]','recherche-multi-champs')];
	$help[] = ['h3',__('- Insert form in widget','recherche-multi-champs')];
	$help[] = ['p', __('Go to the wordpress menu "Appearance > Widgets" and look for the "Recherche Multi Champs" widget in the "Available widgets" section. Click on this widget, choose the sidebar / section you want for this widget, then click on the "Add widget" button. The widget appears on the right of the screen. You can then change its position and title if you wish','recherche-multi-champs')];
	$help[] = ['img','img/screen7.png'];
	$help[] = ['h3',__('- Insert form with the shortcode [rmc_search_shortcode]','recherche-multi-champs')];
	$help[] = ['p', __('If you want to display the search form in a specific location, insert the shortcode [rmc_search_shortcode] in the post, page or product you want. You just copy/paste this shortcode like this:','recherche-multi-champs')];
	$help[] = ['img','img/screen8.png'];
	
	
	$help[] = ['h2',__('4. Customization','recherche-multi-champs')];
	$help[] = ['p',__('This step is optional. <br> - If you want to change the appearance or the position of the results, choose if the empty fields should be considered / displayed or not, go to the "Settings" section of the plugin and make the changes you want','recherche-multi-champs')];
	$help[] = ['img','img/screen9.png'];
	
	$help[] = ['p',__(' - You can also change the size of the search form. To do this, you need to add an attribute to the shortcode like the examples below: <br> [rmc_search_shortcode size="50%"] <br> [rmc_search_shortcode size="250px"]','recherche-multi-champs')];
	$help[] = ['p',__(' - If you have to redirect the search to a specific page, you can use the "action" attribute in the shortcode like this example:','recherche-multi-champs')];
	$help[] = ['img','img/screen10.png'];
	$help[] = ['p',__('All results then will be displayed on page 2. Shortcode must be present on page 2','recherche-multi-champs')];
	
	$displayHelp = '<div class="rmc_adminHelp">';
	foreach($help as $h){
		if ($h[0] == 'img'){
			$displayHelp .= '<img src="'.plugins_url($h[1],__FILE__).'">';	
		}else{
			$displayHelp .= '<' . $h[0] . '>' . $h[1] . '</' . $h[0] . '>';
		}
	}
	$displayHelp .= '</div>';
	
	echo rmc_ui_tabs(
		'<img src="'.plugins_url('/img/logo/rmc_logo_32.png',__FILE__).'" style="display:inline-block;margin-right:8px">'.'Recherche Multi Champs',
		[
			__('Fields', 'recherche-multi-champs'),
			__('Settings', 'recherche-multi-champs'),
			__('Help', 'recherche-multi-champs')
		],
		[
			'dashicons dashicons-list-view',
			'dashicons dashicons-admin-tools',
			'dashicons dashicons-editor-help'
		],
		[	
			[rmc_ui_verticallist([$displayTableActualFields,$displayFormNewField])],
			[rmc_ui_verticallist([$displayParameters1, $displayParameters2])],
			[rmc_ui_verticallist([[$displayHelp]])],
		],
		'champs',
		-1,
		[
			__('Fields', 'recherche-multi-champs'),
			__('Settings', 'recherche-multi-champs'),
			__('Help', 'recherche-multi-champs')
			
		],
		['fields','settings','help']
	);
    
}
function rmc_insert_post($post_id) {
  if ((get_post_type($post_id) == 'post') || (get_post_type($post_id) == 'page')) {
	global $wpdb;
	$resultats = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}rmc_champs ORDER BY %s", "cle")) ;
	foreach ($resultats as $cv) {
		add_post_meta($post_id, $cv->cle, '', true);
	}
  }
  return true;
}

function rmc_insert_post2(){
	rmc_insert_post(get_the_ID());
}

function rmc_shortcode($atts){
	$post_type = get_post_type();
	global $wpdb;
	$rmc_options = rmc_getOptions();
	$result = "";
	if ((($post_type == "post") && ($rmc_options['afficher_champs_articles'] == "on")) || 
		(($post_type == "page") && ($rmc_options['afficher_champs_pages'] == "on"))
		){
		$custom_fields = array_change_key_case(get_post_custom());
		$resultats = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}rmc_champs ORDER BY %s", "cle")) ;
		foreach ($resultats as $cv){
			if (isset($custom_fields[strtolower(stripslashes($cv->cle))][0]) ) {
				if (($rmc_options['afficher_champs_vide'] == true) || ($custom_fields[strtolower($cv->cle)][0] != "")){
					$result .= '<div><b>'.str_replace("_", " ", ucfirst(stripslashes($cv->cle))).':</b> '.ucfirst($custom_fields[strtolower(stripslashes($cv->cle))][0]).'</div>';
				}
			}
		}
	}
	return $result;
}

function rmc_search_shortcode($atts){
	global $wpdb;
	$rmc_options = rmc_getOptions();
	
	$size = "100%";
	if (isset($atts['size'])){
		if ((strlen($atts['size']) < 6) && ((strpos(strtolower($atts['size']),"px") !== false) || (strpos($atts['size'],"%") !== false)))
		$size = $atts['size'];
	}
	$action = "";
	if (isset($atts['action'])){
		$action = $atts['action'];
	}
	$echo = '<form id="rmc_form_id" class="rmc_form" action="'.$action.'" method="post" style="width:'.$size.'"><input type="hidden" name="search_rmc" value="1">';	
	
	$resultats = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}rmc_champs ORDER BY %s", "cle")) ;
	foreach ($resultats as $cv) {
		$cle = str_replace('\\','',$cv->cle);
		$vals = array_unique(rmc_get_meta_values($cle));
		if (sizeof($vals) > 0){
			$width = "100%";
			if ($cv->type_champs == "NUM"){
				$width = "80%";
			}
			$echo .= '<div class="rmc_rowField"><label for="'."rmc_".$cv->id.'" style="display: block">'.str_replace("_"," ",$cle).' :</label>';
			if ($cv->type_champs == "NUM"){
				$selected_sup = "";
				$selected_inf = "";
				if ((isset($_POST["rmc_".$cv->id."_compare"]))&&($_POST["rmc_".$cv->id."_compare"] == "SUP")){
					$selected_sup = "selected";
				}
				if ((isset($_POST["rmc_".$cv->id."_compare"]))&&($_POST["rmc_".$cv->id."_compare"] == "INF")){
					$selected_inf = "selected";
				}
				$echo .= '<select name="'."rmc_".$cv->id.'_compare" style="display:inline-block;margin-top:0px;width:20%"><option value="EGA">=</option><option value="SUP" '.$selected_sup.'>>=</option><option value="INF" '.$selected_inf.'><=</option></select>';
			}
			$echo .= '<select id="'."rmc_".$cv->id.'" name="'."rmc_".$cv->id.'" style="width:'.$width.';margin-top:0px;margin-bottom: 10px"><option value="(rmc_tous)">'.__('All','recherche-multi-champs').'</option>';
			if ($cv->type_champs == "NUM"){
				sort($vals, SORT_NUMERIC);
			}else{
				sort($vals);
			}
			
			foreach ( $vals as $val ){
				$selected = "";
				if ((isset($_POST["rmc_".$cv->id]))&&(str_replace('\\','',$_POST["rmc_".$cv->id])) == $val){
					$selected = "selected";
				}
				$val2 = $val;
				if (($val2 == "") && ($rmc_options['afficher_champs_vide'])){
					$val2 = "(".__('Empty','recherche-multi-champs').")";
				}
				if (($val2 != "")){
					$echo .= "<option value='".str_replace('"','&#34;',str_replace("'","&#39;",$val))."' ".$selected.">".$val2."</option>";
				}
			}
			$echo .= '</select>';
			
			
			
			
			
			
			
			
			
			
			$echo .= '</div>';
		}
	}
	$rpp = "10"; if ((isset($_POST['rmc_rpp']))&&($_POST['rmc_rpp'] != "")){$rpp = intval(sanitize_text_field($_POST['rmc_rpp']));}
	$echo .= '<label style="display:block" for="rmc_rpp">'.__('Results per page','recherche-multi-champs').' :</label>
		<select name="rmc_rpp" style="width:100%;margin-top:0px;margin-bottom: 10px">
			<option value="5" '.(($rpp == "5")? 'selected':'').'>5</option>
			<option value="10" '.(($rpp == "10")? 'selected':'').'>10</option>
			<option value="20" '.(($rpp == "20")? 'selected':'').'>20</option>
			<option value="30" '.(($rpp == "30")? 'selected':'').'>30</option>
			<option value="40" '.(($rpp == "40")? 'selected':'').'>40</option>
			<option value="50" '.(($rpp == "50")? 'selected':'').'>50</option>
		<select>
		<br>';
	$echo .= '<br style="clear:both">
	<input type="submit" value="'.__('Search','recherche-multi-champs').'" style="float:right;margin-bottom:20px"/><br style="clear:both">
	</form>';
	return $echo;
}

function rmc_results_before_content() {
	$custom_content = "";
	
	if ((isset($_POST['search_rmc']))){
		$pageaff = 1; if ((isset($_POST['pageaff']))&&($_POST['pageaff'] != "")){$pageaff = intval(sanitize_text_field($_POST['pageaff']));}
		$pageaffWC = 1; if ((isset($_POST['pageaffWC']))&&($_POST['pageaffWC'] != "")){$pageaffWC = intval(sanitize_text_field($_POST['pageaffWC']));}
		$posts_per_page = get_option('posts_per_page');
		if ((isset($_POST['rmc_rpp']))&&($_POST['rmc_rpp'] != "")){$posts_per_page = intval(sanitize_text_field($_POST['rmc_rpp']));}
		if (($posts_per_page > 50) || ($posts_per_page == -1)){$posts_per_page = 50;}
		$orderby = "date"; 
		$order = "DESC";
		$myargs = array('orderby' => $orderby,
			'order' => $order,
			'posts_per_page' => $posts_per_page,
			'offset' => ($pageaff-1) * $posts_per_page,
			'meta_query' => array('relation' => 'AND'),
			'post_type' => array('post', 'page')
		);
		global $wpdb;
		$resultats = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}rmc_champs ORDER BY %s", "cle")) ;
		$debug = "";
		$nbfiltre = 0;
		$keyfiltre = "";
		$filterWC = array();
		foreach ($resultats as $cv) {
			if (isset($_POST["rmc_".$cv->id])){
				$keyfiltre = str_replace('\\','',$cv->cle);
				$rmc_cv_id = sanitize_text_field($_POST["rmc_".$cv->id]);
				//if ($rmc_cv_id != ""){
					//if ($rmc_cv_id != "(rmc_tous)"){
						$type = "CHAR";
						if ($cv->type_champs == "NUM"){
							$type = "NUMERIC";
						}
						$compare = '=';
						if (isset($_POST["rmc_".$cv->id."_compare"])){
							$rmc_cv_id_compare = sanitize_text_field($_POST["rmc_".$cv->id."_compare"]);
							if ($rmc_cv_id_compare == "SUP"){
								$compare = ">=";
							}
							if ($rmc_cv_id_compare == "INF"){
								$compare = "<=";
							}
						}
						
						if ($rmc_cv_id == "(rmc_tous)"){
							$compare = '!=';
						}
						
						if ($rmc_cv_id != "(rmc_tous)"){
							$myargs["meta_query"][] = array('key' => str_replace('\\','',$cv->cle),'value' => str_replace('\\','',$rmc_cv_id),'compare' => $compare, 'type' => $type);
						}
						$filterWC[str_replace('\\','',$cv->cle)] = array(str_replace('\\','',$rmc_cv_id), $compare);
						
						
						$nbfiltre++;
					//}
				//}
			}
		}

		$mythe_query = get_posts( $myargs );
		$result = 0;
		$resultWC = 0;
		$rmc_options = rmc_getOptions();
		$list = $debug.'<div style="margin: 20px 0 20px 0;border: '.$rmc_options['epaisseur_bordure'].'px solid '.$rmc_options['couleur_bordure'].';padding:10px"><div class="content-headline"><h1 class="entry-headline"><span class="entry-headline-text">'.__('Search results','recherche-multi-champs').'</span></h1></div>';
		foreach ( $mythe_query as $post ) : /*setup_postdata( $post );*/
			if (($result + $resultWC) >= $posts_per_page){break;}
			$custom_fields = array_change_key_case(get_post_custom($post->ID));
			$nofields = true;
			foreach ($resultats as $cv) {
				if (isset($custom_fields[stripslashes(strtolower($cv->cle))])){
					if ($custom_fields[stripslashes(strtolower($cv->cle))][0] != ""){
						$nofields = false;
						break;
					}
				}
			}
			if (($rmc_options['afficher_resultats_champs_vides']) || (!$nofields)){
				$result++;
				$new_content = preg_replace('#\[[^\]]+\]#', '', get_the_excerpt($post->ID));
				$feat_image = wp_get_attachment_image_src( get_post_thumbnail_id($post->ID), 'medium');
				$height = "";
				if ($rmc_options['epaisseur_bordure'] == 0){
					$height = "height:0px;";
				}
				$list .= '<hr style="width:100%;'.$height.'border: '.($rmc_options['epaisseur_bordure']-1).'px solid '.$rmc_options['couleur_bordure'].';clear:both;background-color: '.$rmc_options['couleur_bordure'].'">';
				$list .= '<article class="grid-entry" style="float: left;margin: 0 20px 20px 0;width: 100%;">';
				$list .= '	<a style="float:left;margin-right:10px;" href="' . get_permalink($post->ID) . '">';
				$list .= '		<img src="'.$feat_image[0].'">';
				$list .= '	</a>';
				$list .= '	<span>';
				$list .= '		<a href="'. get_permalink($post->ID) . '">';
				$list .= '			<b>' . ucfirst($post->post_title) . '</b>';
				$custom_fields = array_change_key_case(get_post_custom($post->ID));
				if (get_post_type($post->ID) == 'post'){
					$list .= '		- '.get_the_date('d/m/Y',$post->ID);
				}
				$list .= '			 - <span>'.substr($new_content,0,200).'...</span>';
				$list .= '		</a>';
				$list .= '	</span>';
				$list .= '</article>';
			}
		endforeach; 
		wp_reset_postdata();
		if (($result + $resultWC) == 0){
			$list .= __('No results for this search, please try again with less criteria.','recherche-multi-champs');
		}
		$list .= '<br style="clear:both;"></div>';
		if (($pageaff > 1) || ($pageaffWC > 1)){
			$list .= '<div style="float:left"><form action="" method="post">';
			$list .= '<input type="hidden" name="search_rmc" value="1">';
			if ($pageaff > 1){
				$list .= '<input type="hidden" name="pageaff" value="'.($pageaff - 1).'">';
			}
			if ($pageaffWC > 1){
				$list .= '<input type="hidden" name="pageaffWC" value="'.($pageaffWC - 1).'">';
			}
			$list .= '<input type="hidden" name="rmc_rpp" value="'.$posts_per_page.'">';
			foreach ($resultats as $cv) {
				if (isset($_POST["rmc_".$cv->id])){
					$rmc_cv_id = sanitize_text_field($_POST["rmc_".$cv->id]);
					if ($rmc_cv_id != ""){
						if ($rmc_cv_id != "(rmc_tous)"){
							$list .= '<input type="hidden" name="rmc_'.$cv->id.'" value="'.esc_attr($rmc_cv_id).'">';
						}
					}
					if (isset($_POST["rmc_".$cv->id."_compare"])){
						$rmc_cv_id_compare = sanitize_text_field($_POST["rmc_".$cv->id."_compare"]);
						$list .= '<input type="hidden" name="rmc_'.$cv->id.'_compare" value="'.esc_attr($rmc_cv_id_compare).'">';
					}
				}
			}
			$list .= '<input type="submit" value="'.__('Previous page','recherche-multi-champs').'"></form></div>';
		}
		if (($result + $resultWC) == $posts_per_page){
			$list .= '<div style="float:right"><form action="" method="post">';
			$list .= '<input type="hidden" name="search_rmc" value="1">';
			if ((($result + $resultWC) == $posts_per_page) || ($result == $posts_per_page)) {
				$list .= '<input type="hidden" name="pageaff" value="'.($pageaff + 1).'">';
			}
			if ($resultWC == $posts_per_page){
				$list .= '<input type="hidden" name="pageaffWC" value="'.($pageaffWC + 1).'">';
			}
			$list .= '<input type="hidden" name="rmc_rpp" value="'.$posts_per_page.'">';
			foreach ($resultats as $cv) {
				if (isset($_POST["rmc_".$cv->id])){
					$rmc_cv_id = sanitize_text_field($_POST["rmc_".$cv->id]);
					if ($rmc_cv_id != ""){
						if ($rmc_cv_id != "(rmc_tous)"){
							$list .= '<input type="hidden" name="rmc_'.$cv->id.'" value="'.esc_attr($rmc_cv_id).'">';
						}
					}
					if (isset($_POST["rmc_".$cv->id."_compare"])){
						$rmc_cv_id_compare = sanitize_text_field($_POST["rmc_".$cv->id."_compare"]);
						$list .= '<input type="hidden" name="rmc_'.$cv->id.'_compare" value="'.esc_attr($rmc_cv_id_compare).'">';
					}
				}
			}
			$list .= '<input type="submit" value="'.__('Next page','recherche-multi-champs').'"></form></div>';
		}
		$list .= '<br style="clear:both;">';
		$custom_content .= $list;
		$custom_content = nl2br($custom_content);
		$custom_content = str_replace("\r","",$custom_content);
		$custom_content = str_replace("\n","",$custom_content);
		$custom_content = str_replace("'","&#39;",$custom_content);
		wp_reset_query();
		unset($_POST["search_rmc"]);

		$position_resultats = $rmc_options['position_resultats'];
		$rmc_positionJS = '';
		if ($position_resultats == 'below'){$rmc_positionJS = '.nextSibling';}
		
		
		echo "<script>
		var div = document.createElement('div');
		div.innerHTML = '$custom_content';
		var child = document.getElementById('rmc_form_id');
		if (child){
			child.parentNode.insertBefore(div, child$rmc_positionJS);
		}else{
			child = document.getElementById('primary');
			if (!child){child = document.getElementById('content-wrap');}
			if (!child){child = document.getElementById('main');}
			if (!child){child = document.getElementById('content');}
			if (!child){child = document.getElementsByClassName('main-container')[0];}
			if (!child){
				child = document.getElementById('left-area');
				if (child){
					var i = 0;
					while((i <= child.childNodes.length) && (child.childNodes[i].nodeType != '1')){
						i++;
					}
					child.insertBefore(div, child.childNodes[1]);
				}else{
					console.log('Aucun emplacement trouvé pour afficher les résultats');
				}
			}else{
				child.childNodes[0].parentNode.insertBefore(div, child.childNodes[0]);
			}
		}
		</script>";
	}
}

function rmc_get_meta_values( $key = '', $status = 'publish' ) {
	global $wpdb;
	if( empty( $key ) )
		return;
	$r = $wpdb->get_col( $wpdb->prepare( "
		SELECT pm.meta_value FROM {$wpdb->postmeta} pm
		LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id
		WHERE pm.meta_key = '%s' 
		AND p.post_status = '%s' 
		AND (p.post_type = 'post' OR p.post_type = 'page')
	", $key, $status ) );
	return $r;
}

$rmc_idTabs = 0;
function rmc_ui_tabs($tabs_title, $tabs_menu, $tabs_icons, $tabs_content, $table, $idInTable, $tips, $anchors){
	global $rmc_idTabs;
	global $SAFE_DATA;
	$rmc_idTabs++;
	$tab_active = '';
	if (isset($SAFE_DATA['tab'])){
		$tab_active = $SAFE_DATA['tab'];
	}
	
	$display = rmc_displayAdminNotice(-1) . '
	<div class="rmc_tabs_section" id="rmc_tabs_'.$rmc_idTabs.'"><div id="rmc_form">
		<div class="rmc_tabs_title">
			<div>' . $tabs_title . '</div>
			<div>
	';

	if ($idInTable != -1){
		$display .= '
				<a href="?page=recherche-multi-champs&rmc_act=trash'.ucfirst($table).'&'.$table.'='.$idInTable.'">'.__('Move to trash','recherche-multi-champs').'</a>' . 
				//get_submit_button(__('Update', 'recherche-multi-champs'),'primary large','updateSpace',true,['id' => 'updateSpace']) .
				'<p class="submit"><input type="button" name="'.$action.'" id="'.$action.'" class="button button-primary button-large" value="'.__('Update', 'recherche-multi-champs').'"></p>
		';
	}

	$display .= '

			</div>
		</div>
		<div class="rmc_tabs_container">
			<div class="rmc_menu_container">
				<ul>';
					foreach($tabs_menu as $index => $menu){
						$anchor = sanitize_title($anchors[$index]);
						if (($tab_active == '') && ($index == 0)){$tab_active = $anchor;}
						($tab_active == $anchor) ? $class = 'class="rmc_tabs_active rmc_tabLabel"' : $class = 'class="rmc_tabLabel"';
						$display .= '<li '.$class.' rmc_title="'.$tips[$index].'"><a href="#'.$anchor.'" onclick="rmc_show_tab(\''.$rmc_idTabs.'-'.$index.'\',this,false)"><span class="'.$tabs_icons[$index].'"></span><span class="rmc_tabText">'.$menu.'</span></a></li>';
					}
					$display .= '
				</ul>
			</div>
			<div class="rmc_content_container">';
				foreach($tabs_content as $index => $content){
					$anchor = sanitize_title($anchors[$index]);
					($tab_active == $anchor) ? $class = 'rmc_tabs_content_active' : $class = '';
					$display .= '<div class="rmc_tabs_content '.$class.'" id="'.$rmc_idTabs.'-'.$index.'">';
					//$class = 'rmc_width' . floor(100 / sizeof($content));
					for($i = 0; $i < sizeof($content); $i++){
						//$display .= '<div class="'.$class.'">' . $content[$i] . '</div>';
						$display .= $content[$i];
					}
					
					$display .= '</div>';
				}
				$display .= '
			</div>
		</div>
		<div class="rmc_tabs_tips" id="rmc_tabs_tips_'.$rmc_idTabs.'">'.__('Info bar. Displays information when you edit a field','recherche-multi-champs').'</div>
	</div></div>
	<script>
		jQuery(document).ready(function(jQuery){
			rmc_initListenerTips('.$rmc_idTabs.');
		})
	</script>';
	return $display;
}

function rmc_ui_fields($param){
	$p = [
		'name' => '',
		'label' => '',
		'edit' => false,
		'type' => 'text',
		'value' => '',
		'placeholder' => '',
		'onchange' => '',
		'required' => '',
		'tip' => '',
		'id' => '',
		'min' => '',
		'max' => '',
		'step' => '',
		'brotherfield' => '',
		'class' => '',
		'classParent' => '',
		'title' => '',
		'rmc_title' => '',
		'data' => '',
		'datalist' => '',
		'labelTop' => false,
		'target' => '_self',
		'linktext' => '',
		'onclick' => ''
	];
	$p = array_merge($p, $param);
	
	$classParent = 'rmc_fieldsContent';
	if ($p['classParent'] != ''){
		$classParent = $p['classParent'];
	}
	
	$classTopField = '';
	if (strpos($p['class'],'rmc_marginTopColumn') > -1){
		$p['class'] = str_replace('rmc_marginTopColumn','',$p['class']);
		$classTopField = 'rmc_marginTopColumn';
	}
	
	$p['class'] .= ' rmc_tipOnFocus';
	
	if ($p['rmc_title'] != ''){
		$p['class'] .= ' rmc_tabLabel';
		$p['title'] .= __('See the details below in the info bar','recherche-multi-champs');
	}
	
	$p['id'] = (($p['id'] == '')&&($p['name'] != '')) ? 'rmc_' . $p['name'] : $p['id'];
	$p['tip'] = htmlspecialchars($p['tip'], ENT_QUOTES, 'UTF-8');
	if ($p['type'] == 'WPEditor'){
		$display = '<div class="rmc_labelWPeditor"><label>' . $p['label'] . '</label>';
		$display .= '<span class="dashicons dashicons-editor-help" rmc_title="'.$p['tip'].'"></span></div>';
		$display .= '<div id="rmc_parentWPEditor_'.$p['name'].'" class="rmc_width100 rmc_mt10"></div>';
		echo '<div class="rmc_hiddenWPEditor">';
		wp_editor(stripcslashes($p['value']),$p['name'],array( 'editor_height' => '310'));
		
		echo '</div>';
		$display .= '<script>
						jQuery(document).ready(function(jQuery){
							var content = "'.$p['value'].'";
							document.getElementById("rmc_parentWPEditor_'.$p['name'].'").innerHTML = "";
							document.getElementById("rmc_parentWPEditor_'.$p['name'].'").appendChild(document.getElementById("wp-'.$p['name'].'-wrap"));
						})
					</script>';
	}elseif ($p['type'] == "hidden"){
		$display = '<input type="'.$p['type'].'" id="'.$p['id'].'" name="'.$p['name'].'" value="'.$p['value'].'">';
	}else{
		if (($p['type'] == 'text') || ($p['type'] == 'textarea') || ($p['type'] == 'email')){
			$p['value'] = rmc_removeslashes($p['value']);
		}elseif ($p['type'] == 'number'){
			$p['value'] = (floatval($p['value']) + 0);
		}
		$class = '';
		if (($p['type'] == 'textarea') || ($p['type'] == 'ul')){
			$class = 'class="rmc_fieldLabelTop"';
		}
		($p['labelTop']) ? $rmc_field = '' . $classTopField : $rmc_field = 'rmc_field ' . $classTopField;
		$display = '<div class="'.$rmc_field.'">';
		if (($p['type'] != 'button') && ($p['type'] != 'reset')){
			if (($p['label'] != '') && (!$p['labelTop'])){
				$display .= '<label for="'.$p['id'].'" '.$class.'>' . $p['label'] . '</label>';	
			}
			$display .= '<div class="'.$classParent.'">';
		}
		
		if ($p['type'] == 'link'){
			$display .= '<a href="'.$p['value'].'" id="'.$p['id'].'" class="'.$p['class'].'" target="'.$p['target'].'" onclick="'.$p['onclick'].'"'." rmc-data='".$p['data']."'".'>'.rmc_removeslashes($p['linktext']).'</a>';	
		}elseif ($p['type'] == 'select'){
			if ($p['labelTop']){
				$p['value'] = '<option value="">'.$p['label'].'</option>' . $p['value'];
			}
			//if (strpos($p['value'],'selected>') === false){$p['class'] = str_replace('rmc_filteractivated','',$p['class']);}
			$display .= '<select id="'.$p['id'].'" name="'.$p['name'].'" onchange="'.$p['onchange'].'" '.$p['required'].' class="'.$p['class'].'" title="'.$p['title'].'">'.$p['value'].'</select>';
		}elseif ($p['type'] == 'ul'){
			$display .= '<ul id="'.$p['id'].'" class="'.$p['class'].'">'.$p['value'].'</ul>';
		}elseif ($p['type'] == 'textarea'){
			$display .= '<textarea id="'.$p['id'].'" name="'.$p['name'].'" '.$p['required'].' placeholder="'.$p['placeholder'].'" rows="'.$p['min'].'" class="'.$p['class'].'">'.$p['value'].'</textarea>';
			if ($p['tip'] == ''){$display .= '<span>&nbsp;</span>';}
		}elseif ($p['type'] == 'button'){
			$display .= '<button type="button" id="'.$p['id'].'" onclick="'.$p['onclick'].'" class="'.$p['class'].'">'.$p['label'].'</button>';
		}elseif ($p['type'] == 'reset'){
			$display .= '<button type="reset" id="'.$p['id'].'" class="'.$p['class'].'">'.$p['label'].'</button>';
		}elseif ($p['type'] == 'checkbox'){
			if ($p['value']){$checked = 'checked';}else{$checked = '';}
			$display .= '<input type="'.$p['type'].'" id="'.$p['id'].'" name="'.$p['name'].'" '.$checked.' '.$p['required'] .' class="'.$p['class'].'" title="'.$p['title'].'" rmc_title="'.$p['rmc_title'].'">';
			$display .= '<label for="'.$p['id'].'" '.$class.'>' . $p['label'] . '</label>';

		}elseif ($p['edit']){
			if ($p['labelTop']){$placeholder = 'placeholder="'.$p['label'].'"';}else{$placeholder = 'placeholder="'.$p['placeholder'].'"';}
			//if (($p['type'] == 'number')&&($p['value'] == 0)){$p['class'] = str_replace('rmc_filteractivated','',$p['class']);}
			if ($p['datalist'] != ''){$list = ' list="'.$p['datalist'].'" ';}else{$list = '';}
			$display .= '<input type="'.$p['type'].'" id="'.$p['id'].'" name="'.$p['name'].'" value="'.$p['value'].'" '.$p['required'] .' class="'.$p['class'].'" title="'.$p['title'].'" '.$placeholder.$list;
			if ($p['min'] != ''){$display .= ' min="' . $p['min'] . '"';}
			if ($p['max'] != ''){$display .= ' max="' . $p['max'] . '"';}
			if ($p['step'] != ''){$display .= ' step="' . $p['step'] . '"';}
			if ($p['step'] == '1'){$display .= ' pattern="\d+"';}
			if ($p['onchange'] != ''){$display .= ' onchange="' . $p['onchange'] . '"';}
			$display .= '>';
		}else{
			if (($p['type'] == 'datetime') && ($p['id'] == '')){
				$p['id'] = 'rmc_' . rand(1,999999);
				$p['class'] .= ' rmc_datetime';
			}
			$display .= '<span id="'.$p['id'].'" class="rmc_noedit '.$p['class'].'">'.$p['value'].'</span>';
			if ($p['type'] == 'datetime'){
				$display .= '<script>jQuery(document).ready(function(jQuery){rmc_toLocaleDateTimeString(document.getElementById("'.$p['id'].'"));})</script>';
			}
		}
		
		$display .= $p['brotherfield'];
		
		if ($p['tip'] != ''){
			$class = 'dashicons dashicons-editor-help';
			/*if (($p['type'] == 'textarea') || ($p['type'] == 'ul')){
				$class .= ' rmc_fieldTipTop';
			}*/
			$display .= '<span class="'.$class.'" title="'.__('See the details below in the info bar','recherche-multi-champs').'" rmc_title="';
			if ($p['label'] != ''){
				$display .= '<b>'.$p['label'].'</b>: ';
			}
			$display .= $p['tip'].'"></span>';
		}
		if (($p['type'] != 'button') && ($p['type'] != 'reset')){
			$display .= '</div>';
		}
		$display .= '</div>';
	}
	if ($p['datalist'] != ''){
		$display .= '<datalist id="'.$p['datalist'].'"></datalist>';
	}
	return $display;
}

function rmc_ui_verticallist($tabs){
	$display = '';
	$class = 'rmc_width' . floor(100 / sizeof($tabs));
	foreach($tabs as $tab){
		$display .= '<div class="'.$class.'"><div class="rmc_ui_verticallist">';
		foreach($tab as $elt){
			if ((strpos($elt,'type="hidden"') > -1) || (strpos($elt,'<form') > -1) || (strpos($elt,'</form') > -1)){
				$display .= $elt;
			}else{
				$display .= '<div>'.$elt.'</div>';
			}
		}
		$display .= '</div></div>';
	}
	return $display;
}

function rmc_ui_table($param){
	$p = [
		'name' 			=> '',
		'caption'		=> '',
		'class' 		=> '',
		'id'			=> '',
		'tip'			=> '',
		'tbodyId'		=> '',
		'displayFooter'	=> true,
		'columnsName'	=> [],
		'data'			=> [[]],
		'attributes'	=> [],		
	];
	$p = array_merge($p, $param);
	
	$display = '';
	if ($p['name'] != ''){$display .= '<h2>'.ucfirst($p['name']).'</h2>';}
	$display .= '<table class="rmc_table rmc_widefat rmc_striped '.$p['class'].'" id="'.$p['id'].'">';
	if ($p['caption'] != ''){
		$display .= '<caption>'.$p['caption'];
		if ($p['tip'] != ''){
			$display .= '<span class="dashicons dashicons-editor-help" title="'.__('See the details below in the info bar','recherche-multi-champs').'" rmc_title="'.$p['tip'].'"></span>';
		}
		$display .= '</caption>';
	}
	$display .= '<thead><tr>';
	foreach($p['columnsName'] as $index => $column){
		if ($column != '--row'){
			$attr = '';
			if (isset($p['attributes'][$index])){$attr = $p['attributes'][$index];}
			$display .= '<th '.$attr.'>'.$column.'</th>';
		}
	}
	$display .= '</tr></thead><tbody id="'.$p['tbodyId'].'">';
	foreach($p['data'] as $row){
		$emptydata = false;
		foreach($row as $index => $data){
			if (substr($data,0,5) == '--row'){
				$display .= '<tr class="rmc_'.substr($data, 2).'">';
				$emptydata = true;
			}else{
				if ($index == 0){$display .= '<tr>';}
				if (($data == '')&&(!$emptydata)){$data = '—';}
				$display .= '<td rmc-title="'.$p['columnsName'][$index].'">'.$data.'</td>';
			}
		}
		$display .= '</tr>';
	}
	$display .= '</tbody>';
	if ($p['displayFooter']){
		$display .= '<tfoot><tr>';
		foreach($p['columnsName'] as $column){
			$display .= '<th>'.$column.'</th>';
		}
		$display .= '</tr></tfoot>';
	}
	$display .= '</table>';
	return $display;
}

function rmc_get_wp_nonce_field($action,$nonceName='_wpnonce'){
	$nonce = wp_create_nonce($action);
	return '<input type="hidden" id="'.$nonceName.'" name="'.$nonceName.'" value="'.$nonce.'" />
			<input type="hidden" name="_wp_http_referer" value="'.$_SERVER['REQUEST_URI'].'" />';
}

function rmc_load_admin_js(){
	if (!current_user_can('administrator')){return;}
	wp_enqueue_script('recherche-multi-champs-admin-js',	plugins_url('rmc_admin.js', 								__FILE__), array('jquery'),'1.0.0',true);
	/*
	wp_enqueue_script('rmc-booking-chart-js', 				plugins_url('js/Chart.bundle.min.js', 						__FILE__), array('jquery'),'1.0.0',true);
	wp_enqueue_script('rmc-booking-taginput-js', 			plugins_url('js/jquery.taginput.src.js', 					__FILE__), array('jquery'),'1.0.0',true);
	wp_enqueue_script('rmc-booking-fullcalendarcore', 		plugins_url('js/fullcalendar-4.3.1/core/main.min.js', 		__FILE__), array('jquery'),'4.3.1',true);
	wp_enqueue_script('rmc-booking-fullcalendardaygrid', 	plugins_url('js/fullcalendar-4.3.1/daygrid/main.min.js', 	__FILE__), array('jquery'),'4.3.1',true);
	wp_enqueue_script('rmc-booking-fullcalendarlist', 		plugins_url('js/fullcalendar-4.3.1/list/main.min.js', 		__FILE__), array('jquery'),'4.3.1',true);
	wp_enqueue_script('rmc-booking-fullcalendartimegrid', 	plugins_url('js/fullcalendar-4.3.1/timegrid/main.min.js', 	__FILE__), array('jquery'),'4.3.1',true);
	wp_enqueue_script('rmc-booking-fullcalendarlocaleall', 	plugins_url('js/fullcalendar-4.3.1/core/locales-all.min.js',__FILE__), array('jquery'),'4.3.1',true);
	*/
	wp_localize_script('recherche-multi-champs-admin-js', 'WPJS', array(
		'pluginsUrl' 					=> plugins_url('',__FILE__),
		'rmc_TConfirmDeleteItem' 		=> __('Are you sure you want to delete this item?', 'recherche-multi-champs'),
		'rmc_TPleaseFillLabel' 			=> __('Please fill in the label', 'recherche-multi-champs'),
		'rmc_TPleaseFillMaxQty' 		=> __('Please fill in the maximum quantity', 'recherche-multi-champs'),
		'rmc_TPleaseFillEndDate' 		=> __('Please fill in the end date', 'recherche-multi-champs'),
		));
}

function rmc_load_admin_css(){
	if (!current_user_can('administrator')){return;}
	wp_enqueue_style('recherche-multi-champs-admin-css', plugins_url('rmc_admin.css', __FILE__));
}

function rmc_removeslashes($text){
	$text = stripslashes($text);
	$text = htmlspecialchars($text);
	return $text;
}

$rmc_adminNotice = [];
function rmc_addAdminNotice($text,$level,$type=''){
	global $rmc_adminNotice;
	$rmc_adminNotice[] = array("text" => $text, "level" => $level, "type" => $type);
}

function rmc_displayAdminNotice($level=-1){
	global $rmc_adminNotice;
	$aff = '';
	foreach($rmc_adminNotice as $notice){
		$class = "rmc_adminNotice";
		$img = '<img src="'.plugins_url( 'img/check.png', __FILE__ ).'"> ';
		if ($notice["type"] == "error"){
			$class = "rmc_adminNoticeError";
			$img = '';
		}
		if ($notice["type"] == "warning"){
			$class = "rmc_adminNoticeWarning";
			$img = '';
		}
		if (($level == -1)||($level == $notice["level"])){
			$aff .= '<div class="'.$class.'"><div>' . $img . $notice["text"] . '</div><div><img class="rmc_btnClose" onclick="rmc_closeAdminNotice(this)" src="'.plugins_url( 'img/close.png', __FILE__ ).'"></div></div>';
			unset($notice);
		}
	}
	return $aff;
}

function rmc_woocommerce_metaquery( $query, $query_vars, $data_store_cpt ) {
    global $wpdb;
	$resultats = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}rmc_champs ORDER BY %s", "cle")) ;
    foreach ($resultats as $cv) {
		if (!empty($query_vars[(stripslashes($cv->cle))])){
			$data = $query_vars[(stripslashes($cv->cle))];
			$query['meta_query'][] = array(
				'key' => (stripslashes($cv->cle)),
				'value' => esc_attr($data[0]),
				'compare' => esc_attr($data[1])
			);
		}
	}
	return $query;
}

function rmc_load_plugin_textdomain() {
    load_plugin_textdomain( 'recherche-multi-champs', NULL, 'recherche-multi-champs/languages' );
}
add_action( 'plugins_loaded', 'rmc_load_plugin_textdomain' );


add_action('wp_footer','rmc_results_before_content');
add_action('wp_insert_post', 'rmc_insert_post');
add_action('edit_form_after_editor', 'rmc_insert_post2');
if (is_admin() === true) {
	register_activation_hook(__FILE__, 'rmc_install');
	register_deactivation_hook(__FILE__, 'rmc_deactivate');
	register_uninstall_hook(__FILE__, 'rmc_uninstallPlugin');
	add_action('admin_menu', 'rmc_add_admin_menu');
}
add_action('widgets_init', 'rmc_register_rmc_widget');
add_shortcode('rmc_shortcode', 'rmc_shortcode');
add_shortcode('rmc_search_shortcode', 'rmc_search_shortcode');


?>