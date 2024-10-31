<?php
class rmc_widget extends WP_Widget
{
    public function __construct()
    {
        parent::__construct('rmc_widget', 'Recherche Multi-Champs', array('description' => __('Search via custom fields','recherche-multi-champs')));
    }
    
    public function widget($args, $instance)
    {
        echo $args['before_widget'];
		echo $args['before_title'];
		echo apply_filters('widget_title', $instance['title']);
		echo $args['after_title'];
		echo '<form class="rmc_form" action="" method="post"><input type="hidden" name="search_rmc" value="1">';
		global $wpdb;
		
		$options = array();
		$resultats = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}rmc_options ORDER BY %s", "cle")) ;
		foreach ($resultats as $cv) {
			$options[$cv->cle] = $cv->valeur;
		}
		
		
		
		$resultats = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}rmc_champs ORDER BY %s", "cle")) ;
		foreach ($resultats as $cv) {
			$cle = str_replace('\\','',$cv->cle);
			$vals = array_unique($this->get_meta_values($cle));
			if (sizeof($vals) > 0){
				$width = "100%";
				if ($cv->type_champs == "NUM"){
					$width = "80%";
				}
				echo '<label style="display:block" for="'."rmc_".$cv->id.'">'.str_replace("_"," ",$cle).' :</label>';
				echo '<select id="'."rmc_".$cv->id.'" name="'."rmc_".$cv->id.'" style="float:right;width:'.$width.';margin-top:0px;margin-bottom: 10px"><option value="(rmc_tous)">'.__('All','recherche-multi-champs').'</option>';
				
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
					if (($val2 == "") && ($options['afficher_champs_vide'])){
						$val2 = '(' . __('Empty','recherche-multi-champs') . ')';
					}
					if (($val2 != "")){
						echo "<option value='".str_replace('"','&#34;',str_replace("'","&#39;",$val))."' ".$selected.">".$val2."</option>";
					}
				}
				echo '</select>';
				if ($cv->type_champs == "NUM"){
					$selected_sup = "";
					$selected_inf = "";
					if ((isset($_POST["rmc_".$cv->id."_compare"]))&&($_POST["rmc_".$cv->id."_compare"] == "SUP")){
						$selected_sup = "selected";
					}
					if ((isset($_POST["rmc_".$cv->id."_compare"]))&&($_POST["rmc_".$cv->id."_compare"] == "INF")){
						$selected_inf = "selected";
					}
					echo '<select name="'."rmc_".$cv->id.'_compare" style="float:right;margin-top:0px;width:20%"><option value="EGA">=</option><option value="SUP" '.$selected_sup.'>>=</option><option value="INF" '.$selected_inf.'><=</option></select>';
				}
				echo '<br style="clear:both">';
			}
		}
			
		$rpp = "10"; if ((isset($_POST['rmc_rpp']))&&($_POST['rmc_rpp'] != "")){$rpp = intval(sanitize_text_field($_POST['rmc_rpp']));}
		
		echo '<label style="display:block" for="rmc_rpp">'.__('Results per page','recherche-multi-champs').' :</label>
			<select name="rmc_rpp" style="width:100%;margin-top:0px;margin-bottom: 10px">
				<option value="5" '.(($rpp == "5")? 'selected':'').'>5</option>
				<option value="10" '.(($rpp == "10")? 'selected':'').'>10</option>
				<option value="20" '.(($rpp == "20")? 'selected':'').'>20</option>
				<option value="30" '.(($rpp == "30")? 'selected':'').'>30</option>
				<option value="40" '.(($rpp == "40")? 'selected':'').'>40</option>
				<option value="50" '.(($rpp == "50")? 'selected':'').'>50</option>
			<select>
			<br>';
			
		echo '<input type="submit" value="'.__('Search','recherche-multi-champs').'" style="float:right"/><br style="clear:both">
			</form>';
			
			
		echo $args['after_widget'];
    }
	

	public function form($instance)
	{
		$title = isset($instance['title']) ? $instance['title'] : '';
		echo '<p>
			<label for="'.$this->get_field_name( 'title' ).'">'._e( 'Title:' ).'</label>
			<input class="widefat" id="'.$this->get_field_id( 'title' ).'" name="'.$this->get_field_name( 'title' ).'" type="text" value="'.$title.'" />
			</p>';
	}
	
	public function get_meta_values( $key = '', $status = 'publish' ) {

		global $wpdb;
		if( empty( $key ) )
			return;
		$r = $wpdb->get_col( $wpdb->prepare( "
			SELECT pm.meta_value FROM {$wpdb->postmeta} pm
			LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id
			WHERE pm.meta_key = '%s' 
			AND p.post_status = '%s' 
			AND (p.post_type = 'post' OR p.post_type = 'page' OR p.post_type = 'product')
		", $key, $status ) );
		return $r;
	}
}
?>