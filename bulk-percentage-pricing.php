<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://www.fiverr.com/hussam7ussien
 * @since             1.0.0
 * @package           Woocommerce-bulk-percentage-pricing
 *
 * @wordpress-plugin
 * Plugin Name:       Woocommerce bulk percentage pricing
 * Plugin URI:        https://wordpress.org/plugins/woocommerce-bulk-percentage-pricing
 * Description:       Instead of slow pricing methods, you can now automatically update your store prices for all products at once by percentage.
 * Version:           2.1.3
 * Author:            Hussam Hussien
 * Author URI:        fb.com/hussam7ussien
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       woocommerce-bulk-percentage-pricing
 * Domain Path:       /languages
 */
/**
 * Set up administration
 *
 * @package bulk_percentage_pricing
 * @since 0.1
 */
function wbpp_bulk_percentage_pricing_setup_admin() {
	add_options_page( 'Bulk percentage pricing', 'Bulk percentage pricing', 5, __FILE__, 'wbpp_bulk_percentage_pricing_options_page' );
}

/**
 * Options page
 *
 * @package bulk_percentage_pricing
 * @since 0.1
 */
function wbpp_bulk_percentage_pricing_options_page() {
	wp_enqueue_script('wbpp_bootstrap', plugin_dir_url( __FILE__ ) . 'assets/bootstrap/js/bootstrap.js', array('jquery'));
	wp_enqueue_script('chosen.jquery.min.js','https://cdnjs.cloudflare.com/ajax/libs/chosen/1.4.2/chosen.jquery.min.js', array('jquery'));	
	wp_register_script('wbpp_js', plugin_dir_url( __FILE__ ) . 'assets/javascript.js', array('jquery'));	
	wp_localize_script( 'wbpp_js', 'myAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' )));        
	wp_enqueue_script( 'wbpp_js' );
	wp_enqueue_style( 'wbpp_style', plugin_dir_url( __FILE__ ) . 'assets/style.css');
	wp_enqueue_style( 'wbpp_style_bootstrap', plugin_dir_url( __FILE__ ) . 'assets/bootstrap/css/bootstrap.css');
	wp_enqueue_style( 'wbpp_style_bootstrap_theme', plugin_dir_url( __FILE__ ) . 'assets/bootstrap/css/bootstrap-theme.css');
	wp_enqueue_style( 'chosen.min.css','https://cdnjs.cloudflare.com/ajax/libs/chosen/1.4.2/chosen.min.css');

	$taxonomy = 'product_cat';
	$orderby = 'name';
	$show_count = 0; // 1 for yes, 0 for no
	$pad_counts = 0; // 1 for yes, 0 for no
	$hierarchical = 1; // 1 for yes, 0 for no
	$title = '';
	$empty = 0;

	$args = array(
	'taxonomy' => $taxonomy,
	'orderby' => $orderby,
	'show_count' => $show_count,
	'pad_counts' => $pad_counts,
	'hierarchical' => $hierarchical,
	'title_li' => $title,
	'hide_empty' => $empty
	);
	$all_categories = get_categories( $args );
	$all_products=array();
	$args = array(
	        'post_type' => 'product',
	        'posts_per_page' => -1
	    );
	    $loop = new WP_Query( $args );
	if ( $loop->have_posts() ): while ( $loop->have_posts() ): $loop->the_post();
	global $product;
	$all_products[$product->id]=$product->get_title();
	endwhile; endif; wp_reset_postdata();
		?>
	<div class="wrap">
		<div class="panel panel-default">
			<div class="panel-heading">
					<h3 class="panel-title">Choose between following methods:</h3>
			</div>
			<div class="panel-body">
				<ul class="nav nav-pills">
					<li role="presentation" class="pricing-mode-li active" name="all_products"><a href="#">All Products</a></li>
					<li role="presentation" class="pricing-mode-li" name="specific_categories"><a href="#">Categories</a></li>
					<li role="presentation" class="pricing-mode-li" name="specific_products"><a href="#">Specific Products</a></li>
				</ul>
			</div>
		</div>


	<div class="boxed mode-panel" id="specific_categories">
	<div class="select-container">
	<label>Pleas select category/categories to add</label>
	<select multiple id="add_categories" class="chosen-select">
	<?php
	foreach ($all_categories as $cat)
	{
			echo '<option value="'.$cat->term_id.'">'.$cat->name.'</option>';

	}
	?>
	</select>
	</div>
	</div>


	<div class="boxed mode-panel" id="specific_products">
	<label>Please select product/products to add</label>
	<div class="select-container"> 
	<select multiple id="add_products"  class="chosen-select">
	<?php
	foreach ($all_products as $key => $product)
	{
		echo '<option value="'.$key.'">'.$product.'</option>';
	}
	?>
	</select>
	</div>
	</div>
	<div class="boxed" id="percentage_form">
	<?php if($_SERVER['REQUEST_URI'] !=''): ?>
		<form method="post" action="<?php esc_url( $_SERVER['REQUEST_URI'] ); ?>">
		<?php wp_nonce_field('update-prices'); ?>

		<table class="form-table">

		<tr valign="top">
			<th scope="row"><?php _e('Percentage:') ?><br/><small><?php _e('(Enter pricing percentage)') ?></small></th>
			<td>
				<input type="number" name="percentage" id="percentage" value="0" />%<br />
				
			</td>
		</tr>

		</table>
		<img src="<?php echo  plugin_dir_url( __FILE__ ) . 'assets/images/ajax-loader_2.gif'; ?>" id="loader">
		<p class="submit">
		<input type="submit" name="increase-percentge-submit" id="increase-percentge-submit"  class="percentge-submit" value="<?php _e('Increase Prices') ?>" />
		<input type="submit" name="discount-percentge-submit" id="discount-percentge-submit"  class="percentge-submit" value="<?php _e('Discount Prices') ?>" />
		</p>
		</form>
	<?php endif; ?>
	<div class="updated" style="display:none;">
		
	</div>
	</div>
	</div>
	<?php
}

function wbpp_apply_percentge() {	
		$response['response']='Failed';
		//get operation type
		$operation=$_POST["operation"];
        // sanitize percentage value
        $percentage  =$_POST["percentage"];
		$args = array( 'post_type' => 'product', 'posts_per_page' => -1 );
		if($operation=="specific_products"):
		$args['post__in']=$_POST['values'];
		elseif($operation=="specific_categories"):
		$args['tax_query'] = array(
		array(
			'taxonomy' => 'product_cat',
			'field'    => 'id',
			'terms'    => $_POST['values'],
			),
			);
		endif;
		query_posts( $args );
		while ( have_posts() ) : the_post();
			$product = new WC_Product( get_the_ID() );
			if($_POST['type']=='increase-percentge-submit'):
				$regular_price = $product->regular_price;
				$product_price = $product->price;
				if($percentage>=0):
					$new_regular_price=$regular_price+(($regular_price*$percentage )/100);
					$new_product_price=$product_price+(($product_price*$percentage )/100);
				elseif($percentage<0):
					$percentage*=-1;
					$new_regular_price=$regular_price-(($regular_price*$percentage )/100);
					$new_product_price=$product_price-(($product_price*$percentage )/100);
				endif;	
				update_post_meta(  get_the_ID(), '_regular_price', $new_regular_price );
				update_post_meta(  get_the_ID(), '_price', $new_product_price );
			else:
			if($percentage<0)
				$percentage*=-1;
			$regular_price = $product->regular_price;
			$sale_price=$regular_price-(($regular_price*$percentage )/100);
			update_post_meta(  get_the_ID(), '_sale_price', $sale_price );
			update_post_meta(  get_the_ID(), '_price', $sale_price );
			endif;
		endwhile;
	    header( "Content-Type: application/json" );
	  	$response['response']="SUCCESS - Percentage : ".$percentage."% applied successfully";
	    echo json_encode($response);

	    //Don't forget to always exit in the ajax function.
	    exit();
    }



	function wbpp_apply_percentge1(){

				$response['response']=0;
				//get operation type
				$operation=$_POST["operation"];
		        // sanitize percentage value
		        $percentage  =$_POST["percentage"];
				$args = array( 'post_type' => 'product', 'posts_per_page' => -1 );

				
				query_posts( $args );
				while ( have_posts() ) : the_post();
					$product = new WC_Product( get_the_ID() );
					$response['response']+=1;
					
				endwhile;
			    header( "Content-Type: application/json" );
			    //$response['response']="SUCCESS - Percentage : ".$percentage."% applied successfully";
			    echo json_encode($response);

			    //Don't forget to always exit in the ajax function.
			    exit();

	}


function wbpp_on_activation_wc(){

	$wbpptxt = "
	# WP Maximum Execution Time Exceeded
	<IfModule mod_php5.c>
		php_value max_execution_time 30000
	</IfModule>";

	$htaccess = get_home_path().'.htaccess';
	$contents = @file_get_contents($htaccess);
	if(!strpos($htaccess,$wbpptxt))
	file_put_contents($htaccess,$contents.$wbpptxt);
}



/* On deactivation delete code (dc) from htaccess file */

function wbpp_on_deactivation_dc(){

	$wbpptxt = "
	# WP Maximum Execution Time Exceeded
	<IfModule mod_php5.c>
		php_value max_execution_time 30000
	</IfModule>";

	$htaccess = get_home_path().'.htaccess';
	$contents = @file_get_contents($htaccess);
	file_put_contents($htaccess,str_replace($wbpptxt,'',$contents));
	
}





// Set up plugin
register_activation_hook(   __FILE__, 'wbpp_on_activation_wc' );
register_deactivation_hook( __FILE__, 'wbpp_on_deactivation_dc' );
add_action( 'admin_menu', 'wbpp_bulk_percentage_pricing_setup_admin' );
add_action('wp_ajax_wbpp_apply_percentge', 'wbpp_apply_percentge');

?>
