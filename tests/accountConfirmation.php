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
	//	$image_size='style="width: 160px;height: 80px"';
}
		
		

///file extension///
$php_extension = $db->setVal('extentions');

$url_only_data=explode('//',rtrim($portal_base_url,'/'));



?>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
    <link rel="icon" type="image/png" href="img/<?php echo $favicon_icon;?>">
    <meta name="viewport" content="width=device-width; initial-scale=1.0; maximum-scale=1.0;"/>
    <title><?php echo $site_title;?> - Account Confirmation</title>
    	<!-- Add jQuery library -->
<script type="text/javascript" src="js/jquery-1.7.2.min-new.js"></script>
</head>

 

<body leftmargin="0" topmargin="0" marginwidth="0" marginheight="0" style="overflow: hidden">

<div id="salesplay-email"
     style="background:linear-gradient(#045690,#0679ca) repeat 0 0 rgba(0,0,0,0) !important; width:100%; height:100%; position:absolute; padding:30px 0px 50px 0px;">
    <div style=" width:600px; max-width:90%; margin:0 auto;margin-top:8%">
        <table style="width:100%; margin:0 auto; background:#FFF; border-radius:6px; min-height:300px; box-shadow:0px 0px 12px rgba(0,0,0,0.1);">
            <thead>
            <tr>
                <td>
                    <p style="text-align:center; padding-left:30px; border-bottom:1px solid #E7E7E7; padding-bottom:0px; margin-top: 0px;">
                        <a href="<?php echo $company_web;?>" title="Go to the official website">
                        <img src="<?php echo $company_logo;?>" <?php echo $image_size;?> alt=""/> </a>
                    </p>
                </td>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td style="padding:0px 30px;">
                    <h1 style="margin-bottom: 35px; font-family: Arial, Helvetica, sans-serif; font-size:28px; color: #175ac7; font-weight:normal; margin-bottom:-10px; margin-top: 0px; text-align:center;">
                        Your account has been successfully confirmed! You can continue to work.</h1>

                </td>
            </tr>
            </tbody>
            <tfoot>
            <tr>
                <td>
                
                 <?php 
		$authToken=$_GET['authToken'];	
		$portal_base_url_key=rtrim($portal_base_url,'/').'/'.'?login_token='.$authToken; 
		
        ?>
        
        <div style="padding:0px;">
                        <p style="margin-bottom: 15px; margin-top: 15px; padding-right: 30px; font-family: Arial, Helvetica, sans-serif; font-size:14px; line-height:20px; text-align:center;">
                            <a style="color:#06C; " href="<?php echo $portal_base_url_key;?>" title="">Continue</a>
                        </p>
                    </div>
                    
                    
        
                    <div style="padding:0px;">
                        <p style="margin-bottom: 15px; margin-top: 15px; padding-right: 30px; font-family: Arial, Helvetica, sans-serif; font-size:14px; line-height:20px; text-align:center;">
                            <a style="color:#06C; " href="<?php echo $portal_base_url_key;?>" title=""><?php echo $url_only_data[1];?></a>
                        </p>
                    </div>
                </td>
            </tr>
            </tfoot>
        </table>
        <!--<p style="text-align:center; font-family: Arial, Helvetica, sans-serif; font-size:14px; color:#FFF; margin-top:15px;">
            Copyright © 2019 <a href="https://www.salesplaypos.com/" title="salesplay"
                                style="color:#FFF !important;">Salesplay </a>
        </p>-->
        
       
			<script type="text/javascript">

                $(document).ready(function() {
                    document.getElementById("main").style.display= 'none';



                });

			   
				window.setTimeout(function(){
                    window.location.href = "<?php echo $portal_base_url_key;?>";
			
				}, 10000);
			
			</script>
			
			
			
    </div>
</div>



</body>
</html>
