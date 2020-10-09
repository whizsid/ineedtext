<!DOCTYPE HTML>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>


<?php

 /* No cache*/
header("Cache-Control: no-cache, must-revalidate");

session_start();


require_once('db/config.php');

/*classes & libraries*/
require_once 'classes/dbClass.php';
$db = new db_functions();

//Get Different subdomain for customers//
$get_domain_name_data=explode('.',$_SERVER['HTTP_HOST']);
$get_sub_domain =$get_domain_name_data[0];

$partner_response=$db->checkPartnerDomain($get_sub_domain);
$white_lable_enable=$partner_response['white_lable_enable'];
$partner_id=$partner_response['partner_id'];

if($white_lable_enable =='1'){

	$site_title =$db->setValPartner($partner_id,'site_title');
	$portal_base_url=$db->setValPartner($partner_id,'base_url');
	$company_logo=$db->setValPartner($partner_id,'company_logo');
	$company_web=$db->setValPartner($partner_id,'company_web');
	$favicon_icon='partners/'.$db->setValPartner($partner_id,'system_favicon_icon');
	$image_size='';

}else{

	$site_title =$db->setVal('site_title');
	$portal_base_url=$db->setVal('base_url');
	$company_logo=$db->setVal('company_logo');
	$company_web=$db->setVal('company_web');
	$favicon_icon=$db->setVal('system_favicon_icon');
	$image_size='style="width: 160px;height: 80px"';
}

$php_extension = $db->setVal('extentions');


?>

Some string
<?php require_once "header.php"; ?>

Welcome

<?php 

	$string = "Welcome!";

	// Single Line comment and "Double quotes" and 'Single Quotes' /*  */
	// Another Single Line comment 'comment'

	if (true) {
		$string = "Hy!".$string." How are you!";
	}

	"Welcome\" Escaped";

	/**
	 *
	 * Multiline comment with 'quotes' and //sinle line comments
	 */

	$string = "display:none";

	$string = 'Single Quote String';
	if (true){

		$array['index'];

		$array[ 'anotherindex' ];

		$array["otherindex" ];

		$htmlString = "HTML <div id='as'>  Is a good language </div>";


		$mysl_query = "SELECT
			*
			FROM tbl_area 
			WHERE
			";

		$special_chars_only = ",.";

		$css = "display:none";

		$number_only = "1";

		$column_name = "tbl_area.col_name";

		$column_name = "col_name";

		$sub_str = "My name is 'gunadasa' ";

?>

 Third test
<!--div class="ps-product__meta">
                <p>Brand:<a href="#">Sony</a></p>
                <div class="ps-product__rating">
                  <select class="ps-rating" data-read-only="true">
                    <option value="1">1</option>
                    <option value="1">2</option>
                    <option value="1">3</option>
                    <option value="1">4</option>
                    <option value="2">5</option>
                  </select><span>(1 review)</span>
                </div>
              </div-->
<div id="html">Some id</div>

	<!--  In comment


	<div id ="custom_id"> 

			Another one
	</div>
 -->

After Comment
	<script  jnj>

	var item = "<?php echo "My Id"  ?>"
	var item = "Some Javascript item";

	var cssString = "display: none";

	var anotherItem = `Another Item ${ "Some Item"; `Another Item` } Other Item`;
</script>
<?php }  require_once "footer.php" ?>
