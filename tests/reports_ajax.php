
<style>

.table-responsive {
  overflow-x: auto;
  min-height: 0.01%;
}
@media screen and (max-width: 767px) {
  .table-responsive {
    width: 100%;
    margin-bottom: 15px;
    overflow-y: hidden;
    -ms-overflow-style: -ms-autohiding-scrollbar;
    border: 1px solid #dddddd;
  }
  .table-responsive > .table {
    margin-bottom: 0;
  }
  .table-responsive > .table > thead > tr > th,
  .table-responsive > .table > tbody > tr > th,
  .table-responsive > .table > tfoot > tr > th,
  .table-responsive > .table > thead > tr > td,
  .table-responsive > .table > tbody > tr > td,
  .table-responsive > .table > tfoot > tr > td {
    white-space: nowrap;
  }
  .table-responsive > .table-bordered {
    border: 0;
  }
  .table-responsive > .table-bordered > thead > tr > th:first-child,
  .table-responsive > .table-bordered > tbody > tr > th:first-child,
  .table-responsive > .table-bordered > tfoot > tr > th:first-child,
  .table-responsive > .table-bordered > thead > tr > td:first-child,
  .table-responsive > .table-bordered > tbody > tr > td:first-child,
  .table-responsive > .table-bordered > tfoot > tr > td:first-child {
    border-left: 0;
  }
  .table-responsive > .table-bordered > thead > tr > th:last-child,
  .table-responsive > .table-bordered > tbody > tr > th:last-child,
  .table-responsive > .table-bordered > tfoot > tr > th:last-child,
  .table-responsive > .table-bordered > thead > tr > td:last-child,
  .table-responsive > .table-bordered > tbody > tr > td:last-child,
  .table-responsive > .table-bordered > tfoot > tr > td:last-child {
    border-right: 0;
  }
  .table-responsive > .table-bordered > tbody > tr:last-child > th,
  .table-responsive > .table-bordered > tfoot > tr:last-child > th,
  .table-responsive > .table-bordered > tbody > tr:last-child > td,
  .table-responsive > .table-bordered > tfoot > tr:last-child > td {
    border-bottom: 0;
  }
}
</style>
<?php

//error_reporting(E_ALL);
 //ini_set('display_errors', 1);


session_start();
$session_id = session_id();
header("Cache-Control: no-cache, must-revalidate");



include '../db/config.php';


function deviceName($key,$user_name,$is_centralise){
	
	if($is_centralise=='1'){
		$q="SELECT g.`group_name` AS f FROM `admin_manage_centralized_device_group` g WHERE g.`group_id`='$key'";
		}else{
			
		$q="SELECT device_name AS f FROM `admin_users_subscription` s WHERE s.key_id='$key' AND  s.user_name='$user_name'";	
			}
	
	
	$ex_name=mysql_query($q);
	$rowc=mysql_fetch_array($ex_name);
	return $rowc[f];
	
	}


function date_range_array($start_date,$end_date,$profile_currency) {

	$step = '+'.local("1 day").'';
	$output_format = 'Y-m-d';
    $dates = array();
    $current = strtotime($start_date);
    $end_date = strtotime($end_date);

    while( $current <= $end_date ) {

        //$dates[] = date($output_format, $current);
		//create arrays with zero data// 
		$date=date($output_format, $current);

		$dates[$date]=array(local("Date")=>$date,local("Total Sales")."(".$profile_currency.")"=>0.00,local("Total Stock Returns")."(".$profile_currency.")"=>0.00,
		local("Total GRNs")."(".$profile_currency.")"=>0.00,local("Total Discounts")."(".$profile_currency.")"=>0.00);
		
		
        $current = strtotime($step, $current);
    }

    return $dates;
}


function getCustomerTimeZone($key){
	
	$getInfo=mysql_query("SELECT time_zone AS f FROM `authentication`a WHERE a.key_id='$key'");
	
	$row=mysql_fetch_array($getInfo);
	
	return $row[f];
		
	}
	


function getCustomerCurrentDateTime($key){
	
$time_zone =getCustomerTimeZone($key);
	
if(strlen(trim($time_zone))==0){
	$time_zone='Asia/Colombo';
	}	
	
//get required country time//
$now = new DateTime(null, new DateTimeZone($time_zone));
//$country_current_date=$now->format('Y-m-d'); 	
$country_current_date_time=$now->format('Y-m-d H:i:s'); 	

	
	return $country_current_date_time;
	
	
	}
	


function getLicenseKey($group_id){
	
	$data=mysql_query("SELECT a.`stores_location` ,a.`user_name`,b.`key_id`,a.group_name  FROM `admin_manage_centralized_device_group` a,`admin_manage_centralized_device_group_bucket` b
	 WHERE a.`group_id`=b.`group_id` AND b.`group_id`='$group_id' LIMIT 1");
	
	$row=mysql_fetch_array($data);
	$response[0]=$row[key_id];
	$response[1]=$row[stores_location];
	$response[2]=$row[user_name];
	$response[3]=$row[group_name];
	
	return $response;
	
	}


function getStoresLocation($group_id){
	
	
	$getData=mysql_query("SELECT g.`stores_location` AS f FROM `admin_manage_centralized_device_group` g WHERE g.`group_id`='$group_id'");
	$row=mysql_fetch_array($getData);
	
	return $row[f];
	
	
	
	}

function isGroupID($group_id){
	
	$getData=mysql_query("SELECT * FROM `admin_manage_centralized_device_group` g WHERE g.`group_id`='$group_id'");
	
	if(mysql_num_rows($getData)>0){
		return true;
		}else{
			return false;
			}
	
	
	}



$type = $_GET['type'];
$from_date_only=$_GET['from_date'];
$to_date_only=$_GET['to_date'];
$from_date = $from_date_only.' 00:00:00';
$to_date = $to_date_only.' 23:59:59';
$def_key = $_SESSION['default_key'];//Normal-App key,Centralise-Group ID (Integer value)
$user_name = $_SESSION['user_name'];
$is_centralise=0;

$a_query = "SELECT  device_name,is_device FROM admin_users_subscription
WHERE `key_id` = '$def_key'
AND user_name = '$user_name'";

$query_results=mysql_query($a_query);
while($row=mysql_fetch_array($query_results)){
	$device_name = $row[device_name];
	$is_device = $row[is_device];
}


//****** Centralise Check *********************//
if(isGroupID($def_key)){//K
	
	 $reponseData=getLicenseKey($def_key);
	 $group_license_key=$reponseData[0];
	 $group_stores_location=$reponseData[1];
	 $master_username=$reponseData[2];
	 $device_name=$reponseData[3];
	 $is_centralise=1;
	 $is_device =1;
	

	
	}//K





if($is_device =='1'){
	
	$device_key=$def_key;
	if($is_centralise=='1'){
		$device_key=$group_license_key;
		}
	
	
	
	
	}else{
		
		//SPT5,SPT4
		$a=explode(',',$def_key);
		$device_key=$a[0];
		}







//find currency//

if($is_centralise=='1'){
	$q1="SELECT `profile_currency` AS f FROM `admin_manage_centralized_profile` p WHERE p.key_id='$master_username' AND p.group_id='$def_key' LIMIT 1";
	
	}else{

   $q1="SELECT  profile_currency AS f FROM `device_backup_profile` WHERE `key` = '$device_key' LIMIT 1";
	}
	
	
$ex_currency=mysql_query($q1);
$rowc=mysql_fetch_array($ex_currency);
$profile_currency = $rowc[f];

//customer current date//
$current_date=date('Y-m-d',strtotime(getCustomerCurrentDateTime($device_key)));


/**

 * Define currency and number format.

 */

// currency format, â¬ with < 0 being in red color
//$currencyFormat = '#,#0.## \â¬;[Red]-#,#0.## \â¬';

// number format, with thousands separator and two decimal points.
//$numberFormat = '#,#0.##;[Red]-#,#0.##';


$excel_footer_data=array();
//set price column cell alignment right-> header array keys//
$footer_data_price_alignment_right_columns=array();
$footer_display=0;



$MULTI_Terminals = str_replace('"',"'",str_replace(array('[', ']'), '',$_GET['MULTI_Terminals']));
$MULTI_CENTRALISE_KEYS = str_replace('"',"'",str_replace(array('[', ']'), '',$_GET['MULTI_CENTRALISE_KEYS']));

if($is_centralise=='1'){
$product_table='admin_manage_centralized_products';
$customer_table='admin_manage_centralized_customer';
$emp_type_table='admin_manage_centralized_employee_types';
$employees_table='admin_manage_centralized_employees';
$inv_master_key_column='group_id';
$product_master_key_column='key_id';
$product_master_key_value=$master_username;


$centralise_group_name='GM.`group_name` AS group_name,';
$centralise_group_table=',`admin_manage_centralized_device_group` GM';
$centralise_condition='i.`group_id`=GM.`group_id` AND';
$centralise_condition_='s.`group_id`=GM.`group_id` AND';

$where = "AND p.`".$product_master_key_column."` ".local("IN")." (".$MULTI_CENTRALISE_KEYS.") ";
$where1 = "AND TM.`".$product_master_key_column."` ".local("IN")." (".$MULTI_CENTRALISE_KEYS.") ";
$where2 = "AND i.`group_id`=p.`group_id`"; 
$group_by = "`group_id`";


}else{

$where2 = "AND i.`key`=p.`key`"; 
$product_table='device_backup_product';	
$customer_table='device_backup_customer';
$emp_type_table='device_backup_employee_types';
$employees_table='device_backup_employees';
$inv_master_key_column='key';
$product_master_key_column='key';
$product_master_key_value=$def_key;
$group_by = "`".local("key")."`";
$group_by1 = "`key_id`";
	}
	
	
	
$main_colum_name='TM.`device_name` AS terminal_name,';
$main_terminal_table=',`admin_users_subscription` TM';
$main_terminal_condition="i.`key`=TM.`key_id` AND TM.`user_name`='$user_name' AND ";
$main_terminal_condition_="s.`key`=TM.`key_id` AND TM.`user_name`='$user_name' AND ";

switch ($type){
	
	case 'revanue' :

	

		 $key_query = local("SELECT")."  ".$centralise_group_name.$main_colum_name."invoice_itemcode,p.product_name,p.`product_category`,SUM(invoice_qty) AS qty,SUM(invoice_qty * invoice_item_cost) AS cost,SUM((invoice_price * invoice_qty)) AS item_value, 
		SUM(invoice_discount) AS discount, invoice_tax_value,
		SUM(((invoice_price * invoice_qty) - invoice_discount) * invoice_tax_value / 100) AS tax
		FROM device_backup_invoice i, ".$product_table." p".$main_terminal_table.$centralise_group_table ."
		WHERE ".$main_terminal_condition.$centralise_condition." ".local("i.")."`".$inv_master_key_column."` ".local("IN")." (".$MULTI_Terminals.") 
        
        ".$where."
        
        ".$where2."
        AND i.`invoice_delete_flag`='0' AND i.invoice_itemcode = p.product_code AND i.`invoice_crn_item_status` =0
		AND DATE(invoice_date) BETWEEN '$from_date' AND '$to_date'
		GROUP BY i.".$group_by.",invoice_itemcode";
		//
		
		$report_header_array=array(local("Product Name"),local("Product Code"),local("Product Category"),local("Qty"), local("Total Cost")."(".$profile_currency.")",local("Total Value Based on The Price")."(".$profile_currency.")", local("Total Discounts")."(".$profile_currency.")", local("Total Tax")."(".$profile_currency.")",local("Total Sales Value")."(".$profile_currency.")", local("Total Profit")."(".$profile_currency.")");
		//set price column cell alignment right-> header array keys//
		$price_alignment_right_columns=array(4,5,6,7,8,9);
		$total_value_shows=array(3,4,5,6,7,8,9);
		
	    $someArray = [];
        
 
		$query_results=mysql_query($key_query);		
        
        
        ?>
                    
                        <table id="example_1" class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr>
                                    <?php if($is_centralise=='1'){ ?>
                                    <th> <?php echo local("Group Name")  ?> </th>
                                    <?php } ?>
                                    <th> <?php echo local("Terminal Name")  ?> </th>
                                    <th> <?php echo local("Product Name")  ?> </th>
                                    <th> <?php echo local("Product Code")  ?> </th>
                                    <th> <?php echo local("Product Category")  ?> </th>
                                    <th> <?php echo local("Qty")  ?> </th>
                                    <th> <?php echo local("Total Cost")  ?>(<?php echo $profile_currency; ?>) </th>
                                    <th> <?php echo local("Total Value Based on The Price")  ?>(<?php echo $profile_currency; ?>) </th>
                                    <th> <?php echo local("Total Discounts")  ?>(<?php echo $profile_currency; ?>) </th>
                                    <th> <?php echo local("Total Tax")  ?>(<?php echo $profile_currency; ?>) </th>
                                    <th> <?php echo local("Total Sales Value")  ?>(<?php echo $profile_currency; ?>) </th>
                                    <th> <?php echo local("Total Profit")  ?>(<?php echo $profile_currency; ?>) </th>
				                </tr>
                            </thead>
                            <tbody>
                            <?php

                                $qt = 0;
                                $cst = 0;
                                $i_value = 0;
                                $dis = 0;
                                $tx = 0;
                                $rn = 0;
                                $po = 0;
                            while($row=mysql_fetch_array($query_results)){

                                $group_name = $row[group_name];
                                $terminal_name = $row[terminal_name];
                                $invoice_itemcode = $row[invoice_itemcode];
                                $product_name = $row[product_name];
                                $product_category = $row[product_category];
                                $qty = $row[qty];
                                $cost = number_format($row[cost],2);
                                $item_value = number_format($row[item_value],2);
                                $discount = number_format($row[discount],2);
                                $tax = number_format($row[tax],2);

                                $revanue = number_format($row[item_value] - $row[discount],2); 
                                $profit = number_format(($row[item_value] - $row[discount]) - $row[cost],2);

                                $cst = floatval($cst)+floatval($row[cost]);
                                $qt = floatval($qt)+floatval($row[qty]);
                                $i_value = floatval($i_value)+floatval($row[item_value]);
                                $dis = floatval($dis)+floatval($row[discount]);
                                $tx = floatval($tx)+floatval($row[tax]);
                                $rn = floatval($rn)+floatval($row[item_value] - $row[discount]);
                                $po = floatval($po)+floatval(($row[item_value]) - floatval($row[discount]) - floatval($row[cost]));
                                
                                
                               /* array_push($someArray, array["Product Name" => $product_name,"Product Code" => $invoice_itemcode,"Product Category"=>$product_category, "Qty" => $qty, "Total Cost(".$profile_currency.")" => $cost,
                                        "Total Value Based on The Price(".$profile_currency.")" => $item_value, "Total Discounts(".$profile_currency.")" => $discount, "Total Tax(".$profile_currency.")" => $tax,
                                        "Total Sales Value(".$profile_currency.")" => $revanue, "Total Profit(".$profile_currency.")" => $profit
                                ]);*/
                            ?>  
            
            
                                    </tr>  
                                        <?php if($is_centralise=='1'){ ?>
                                        <td><?php echo $group_name; ?></td>
                                        <?php } ?>
                                        <td><?php echo $terminal_name; ?></td>    
                                        <td><?php echo $product_name; ?></td>    
                                        <td><?php echo $invoice_itemcode; ?></td>    
                                        <td><?php echo $product_category; ?></td>    
                                        <td style="text-align:right;"><?php echo $qty; ?></td>    
                                        <td style="text-align:right;"><?php echo $cost; ?></td>    
                                        <td style="text-align:right;"><?php echo $item_value; ?></td>    
                                        <td style="text-align:right;"><?php echo $discount; ?></td>    
                                        <td style="text-align:right;"><?php echo $tax; ?></td>    
                                        <td style="text-align:right;"><?php echo $revanue; ?></td>    
                                        <td style="text-align:right;"><?php echo $profit; ?></td>  
                                    </tr>
            
            
		                  <?php } ?>
                       </tbody>
                        <tfoot>
                                    </tr>  
                                        <?php if($is_centralise=='1'){ ?>
                                        <th></th>
                                        <?php } ?>
                                        <td><?php echo local("Total")  ?></td>    
                                        <td></td>    
                                        <td></td>    
                                        <td></td>    
                                        <td style="text-align:right;"><?php echo $qt; ?></td>    
                                        <td style="text-align:right;"><?php echo number_format($cst,2); ?></td>    
                                        <td style="text-align:right;"><?php echo number_format($i_value,2); ?></td>    
                                        <td style="text-align:right;"><?php echo number_format($dis,2); ?></td>      
                                        <td style="text-align:right;"><?php echo number_format($tx,2); ?></td>    
                                        <td style="text-align:right;"><?php echo number_format($rn,2); ?></td>    
                                        <td style="text-align:right;"><?php echo number_format($po,2); ?></td>  
                                    </tr>
                        </tfoot>    
                       </table>
	       
	   <?php
	break;
	
	
        case 'full' :
	
		$report_header_add_array=array();
		if($is_centralise=='1'){
			
			$key_query = "SELECT DISTINCT GM.`group_name` AS group_name, `location_name` AS stores_location,su.supplier_name,grn_id AS grn_no,  p.product_category, CONCAT('',p.product_code) AS product_code,p.product_name,
            `grn_qty` AS grn_qty, IF(stock_grn_type='TRANS_IN',stock_purchased_qty,0) AS tansfer_in,(stock_purchased_qty - (stock_in_qty+stock_sold_qty+stock_return_qty)) AS transfer_out,`inhand_qty` AS inhand_stock,`unit_cost` AS stock_cost,selling_price AS product_price,
            (`inhand_qty`) * unit_cost AS total_cost,
            (`inhand_qty`) * p.selling_price AS total_sales_value,DATE(s.`create_date`) AS stock_datetime,stock_sold_qty,stock_return_qty
            FROM `admin_manage_centralized_stock` s, `admin_manage_centralized_products` p, `admin_manage_centralized_suppliers` su,
            `admin_manage_stock_location` l ,`admin_manage_centralized_device_group` GM
            WHERE   p.group_id IN (".$MULTI_Terminals.") AND su.`group_id` IN (".$MULTI_Terminals.")
            AND l.`master_username`='$master_username'
            AND s.`supplier_code` = su.`supplier_id` 
            AND s.`product_code` = p.product_code
            AND p.`group_id`=GM.`group_id`
            AND s.stores_location=l.`master_username`
            AND DATE(s.`create_date`) BETWEEN '$from_date' AND '$to_date'
            ORDER BY location_name,CAST(`grn_id` AS UNSIGNED) DESC";

            //set price column cell alignment right-> header array keys//
                    $price_alignment_right_columns=array(6,10,11);
                    $total_value_shows=array(7,8,10,11);

            $report_header_add_array[]=local("Stores Location");

			}else{
             $key_query = "SELECT DISTINCT TM.`device_name` AS terminal_name,su.supplier_name,s.grn_no,  p.product_category, CONCAT('',p.product_code) AS product_code,p.product_name,
            (stock_purchased_qty) AS grn_qty, IF(stock_grn_type='TRANS_IN',stock_purchased_qty,0) AS tansfer_in,(stock_purchased_qty - (stock_in_qty+stock_sold_qty+stock_return_qty)) AS transfer_out,stock_in_qty AS inhand_stock, stock_cost, p.product_price,
            (stock_in_qty) * stock_cost AS total_cost,
            (stock_in_qty) * p.product_price AS total_sales_value,DATE(stock_datetime) as stock_datetime,stock_sold_qty,stock_return_qty
            FROM device_backup_stock s, device_backup_product p, device_backup_supplier su,`admin_users_subscription` TM
            WHERE s.`key` IN (".$MULTI_Terminals.") AND p.`key` IN (".$MULTI_Terminals.") AND su.`key` IN (".$MULTI_Terminals.")
            AND s.stock_supplier_id = su.supplier_id 
            AND s.`key`=su.`key`
            AND s.`key`=p.`key`
            AND p.`key`=TM.`key_id`
            AND TM.`user_name`='$user_name'
            AND s.stock_item_code = p.product_code
            AND DATE(stock_datetime) BETWEEN '$from_date' AND '$to_date'
            ORDER BY CAST(`grn_no` AS UNSIGNED) DESC";
            //set price column cell alignment right-> header array keys//
            $price_alignment_right_columns=array(5,9,10);
            $total_value_shows=array(6,7,9,10);
                }
//
        //echo $key_query;
            $report_header_array=$report_header_add_array+ array(local("Supplier Name"),local("GRN Number"),local("Product Category"),local("Product Name"),local("Product Code"),local("Product Price")."(".$profile_currency.")",local("GRN Qty"),local("Inhand Stock"),local("Stock In Date"),local("Total Cost")."(".$profile_currency.")",local("Projected Sales Income")."(".$profile_currency.")");
		  //exit();
		   $query_results=mysql_query($key_query);
		

			
			?>
        
                             <table id="example_2" class="table table-striped table-bordered table-hover">
                                <thead>
                                    <tr><?php if($is_centralise=='1'){ ?>
                                    <th> <?php echo local("Group Name")  ?> </th>
                                    <th> <?php echo local("Stores Location")  ?> </th>
                                    <?php }else{ 
                                        if($MULTI_TERMINALS_AVAILABLE =='1' ||  $is_centralise !='1'){ 
                                        ?>
                                    <th> <?php echo local("Terminal Name")  ?> </td>    
                                        <?php } }?>
                                    <th> <?php echo local("Supplier Name")  ?> </th>
                                    <th> <?php echo local("GRN Number")  ?> </th>
                                    <th> <?php echo local("Product Category")  ?> </th>
                                    <th> <?php echo local("Product Name")  ?> </th>
                                    <th> <?php echo local("Product Code")  ?> </th>
                                    <th> <?php echo local("Product Price")  ?>(<?php echo $profile_currency; ?>) </th>
                                    <th> <?php echo local("GRN Qty")  ?> </th>
                                    <th> <?php echo local("Transfer In")  ?></th>
                                    <th> <?php echo local("Sold Qty")  ?> </th>
                                    <th> <?php echo local("Return Qty")  ?> </th>
                                    <th> <?php echo local("Transfer Out")  ?> </th>
                                    <th> <?php echo local("Inhand Stock")  ?> </th>
                                    <th> <?php echo local("Stock In Date")  ?> </th>
                                    <th> <?php echo local("Product Cost")  ?>(<?php echo $profile_currency; ?>) </th>
                                    <th> <?php echo local("Total Cost")  ?>(<?php echo $profile_currency; ?>) </th>
                                    <th> <?php echo local("Projected Sales Income")  ?>(<?php echo $profile_currency; ?>) </th>
				                </tr>
                            </thead>
                            <tbody>
                                
                                
                                <?php		 
                                        $grnqty = 0;
                                        $inhandstock = 0;
                                        $totalcost = 0;
                                        $totalsalesvalue = 0;
                                        $totalTarnIn = 0;
                                        $totalTarnOut = 0;
        
                                        while($row=mysql_fetch_array($query_results)){

                                        $supplier_name = $row[supplier_name];
                                        $product_category = $row[product_category];
                                        $product_code = $row[product_code];
                                        $product_name = $row[product_name];
                                        $product_price = $row[product_price];
                                        $grn_qty = $row[grn_qty];
                                        $inhand_stock = $row[inhand_stock];
                                        $stock_cost = $row[stock_cost];
                                        $total_cost = $row[total_cost];
                                        $total_sales_value = $row[total_sales_value];
                                        $stock_datetime = $row[stock_datetime];
                                        $grn_no = $row[grn_no];
                                        $tansfer_in = $row[tansfer_in];
                                        $totalTarnIn = $totalTarnIn + $row[tansfer_in];
                                        $tansfer_out = $row[transfer_out];
                                        $totalTarnOut = $totalTarnOut + $row[transfer_out];
                                        $stock_sold_qty = $row[stock_sold_qty];
                                        $stock_return_qty = $row[stock_return_qty];
                                          
                                        $grnqty = $grnqty + $row[grn_qty];
                                        $inhandstock = $inhandstock + $row[inhand_stock];
                                        $totalcost = $totalcost + $row[total_cost];
                                        $totalsalesvalue = $totalsalesvalue + $row[total_sales_value];    
                                            echo '<tr>';
                                        if($is_centralise=='1'){
                                            echo '<td>'.$row[group_name].'</td>';
                                            echo '<td>'.$row[stores_location].'</td>';
                                            }else{
                                            if($MULTI_TERMINALS_AVAILABLE =='1' ||  $is_centralise !='1'){ 
                                            echo '<td>'.$row[terminal_name].'</td>';
                                            }
                                        }
                                ?>      
                                        <td><?php echo $supplier_name; ?></td>    
                                        <td><?php echo $grn_no; ?></td>    
                                        <td><?php echo $product_category; ?></td>    
                                        <td><?php echo $product_name; ?></td>    
                                        <td><?php echo $product_code; ?></td>    
                                        <td style="text-align:right;"><?php echo number_format($product_price,2); ?></td>    
                                
                                        <td style="text-align:right;"><?php echo $grn_qty; ?></td>    
                                        <td style="text-align:right;"><?php echo $tansfer_in; ?></td>    
                                        <td style="text-align:right;"><?php echo $stock_sold_qty; ?></td>    
                                        <td style="text-align:right;"><?php echo $stock_return_qty; ?></td>    
                                        <td style="text-align:right;"><?php if(empty($tansfer_out)){ echo 0;}else{ echo $tansfer_out;} ?></td>    
                                        <td style="text-align:right;"><?php echo $inhand_stock; ?></td>    
                                        <td><?php echo $stock_datetime; ?></td>    
                                        <td style="text-align:right;"><?php echo number_format($stock_cost,2); ?></td>    
                                        <td style="text-align:right;"><?php echo number_format($total_cost,2); ?></td>    
                                        <td style="text-align:right;"><?php echo number_format($total_sales_value,2); ?></td>  
                                      </tr>
                                <?php        
                                    }
                                 ?>
                            </tbody>
                            <tfoot>
                                        <tr>
                                        <?php if($is_centralise=='1'){ ?>
                                        <th> </th>
                                        <th> </th>
                                        <?php }else{ if($MULTI_TERMINALS_AVAILABLE =='1' ||  $is_centralise !='1'){ ?>
                                         <td></td>    
                                        <?php }} ?>
                                        <td></td>    
                                        <td><?php echo local("Totals")  ?></td>    
                                        <td></td>    
                                        <td></td>    
                                        <td></td>    
                                        <td style="text-align:right;"></td>    
                                        <td style="text-align:right;"></td>    
                                        <td style="text-align:right;"></td>          
                                        <td style="text-align:right;"></td>          
                                        <td style="text-align:right;"></td>          
                                        <td style="text-align:right;"></td>    
                                        <td style="text-align:right;"></td>    
                                        <td style="text-align:right;"></td>    
                                        <td style="text-align:right;"></td>    
                                        <td style="text-align:right;"></td>    
                                        <td style="text-align:right;"></td>  
                                      </tr>     
                            </tfoot>
                </table>
<?php

            
			
			

		break;
			

	
	case 'aging' :
		
		$report_header_add_array=array();
		if($is_centralise=='1'){
			
			 $key_query = "SELECT GM.`group_name` AS group_name,`location_name` AS stores_location,su.supplier_name,  p.product_category, p.product_code,p.product_name, selling_price AS product_price,
inhand_qty AS inhand_stock, DATE(s.`create_date`) AS stock_datetime,DATEDIFF(NOW(), s.`create_date`) AS age
FROM admin_manage_centralized_stock s, admin_manage_centralized_products p, admin_manage_centralized_suppliers su,
`admin_manage_stock_location` l,`admin_manage_centralized_device_group` GM
WHERE  
p.group_id IN (".$MULTI_Terminals.") AND su.`group_id` IN (".$MULTI_Terminals.")
AND l.`master_username`='$master_username'
AND s.`supplier_code` = su.`supplier_id` 
AND s.`product_code` = p.product_code
AND s.stores_location=l.`loc_id`
AND p.`group_id`=GM.`group_id`
AND p.`group_id`=su.`group_id`
AND inhand_qty >0
AND DATE(s.`create_date`) BETWEEN '$from_date' AND '$to_date'
GROUP BY s.id
ORDER BY location_name,p.product_category,p.product_name ASC";


$report_header_add_array[]=local("Stores Location");
$price_alignment_right_columns=array(3);
$total_value_shows=array(4);			
			}else{
		
		
		 $key_query = "SELECT TM.`device_name` AS terminal_name,su.supplier_name,  p.product_category, p.product_code,p.product_name, product_price,
        stock_in_qty AS inhand_stock, DATE(stock_datetime) AS stock_datetime,DATEDIFF(NOW(), stock_datetime) AS age
        FROM device_backup_stock s, device_backup_product p, device_backup_supplier su,`admin_users_subscription` TM
        WHERE 
        s.`key` IN (".$MULTI_Terminals.") AND p.`key` IN (".$MULTI_Terminals.") AND su.`key` IN (".$MULTI_Terminals.")
        AND s.stock_supplier_id = su.supplier_id AND stock_finish = 0 AND s.stock_in_qty > 0
        AND s.stock_item_code = p.product_code
        AND p.`key`=TM.`key_id`
        AND s.`key`=p.`key_id`
        AND s.`key`=su.`key_id`
        AND TM.`user_name`='$user_name'
		AND DATE(stock_datetime) BETWEEN '$from_date' AND '$to_date'
		group by s.id
		ORDER BY p.product_category,p.product_name ASC";
		$price_alignment_right_columns=array(3);
		$total_value_shows=array(4);
		
			}
		$report_header_array=$report_header_add_array + array(local("Product Category"),local("Product Name"),local("Product Code"),local("Product Price")."(".$profile_currency.")",local("Inhand Stock"),local("Stock In Date"),local("Age Days"));
			//set price column cell alignment right-> header array keys//
			
		
		$query_results=mysql_query($key_query);
		?>	
			<table id="example_3" class="table table-striped table-bordered table-hover">
                <thead>
                                <tr><?php if($is_centralise=='1'){ ?>
                                    <th> <?php echo local("Group Name")  ?> </th>
                                    <th> <?php echo local("Stores Location")  ?> </th>
                                    <?php }else{ ?>
                                    <th> <?php echo local("Terminal Name")  ?> </td>    
                                        <?php } ?>
                                    <th> <?php echo local("Product Category")  ?> </th>
                                    <th> <?php echo local("Product Name")  ?> </th>
                                    <th> <?php echo local("Product Code")  ?> </th>
                                    <th> <?php echo local("Product Price")  ?>(<?php echo $profile_currency; ?>) </th>
                                    <th> <?php echo local("Inhand Stock")  ?> </th>
                                    <th> <?php echo local("Stock In Date")  ?> </th>
                                    <th> <?php echo local("Age Date")  ?> </th>
				                </tr>
                            </thead>
                            <tbody>
                                <?php	

                                        while($row=mysql_fetch_array($query_results)){
                                        $supplier_name = $row[supplier_name];
                                            $product_category = $row[product_category];
                                            $product_code = $row[product_code];
                                            $product_name = $row[product_name];
                                            $product_price = $row[product_price];
                                            $inhand_stock = $row[inhand_stock];
                                            $age = $row[age];
                                            $stock_datetime = $row[stock_datetime];

                                            echo '<tr>';
                                            if($is_centralise=='1'){
                                                echo '<td>'.$row[group_name].'</td>';
                                                echo '<td>'.$row[stores_location].'</td>';
                                                }else{
                                                echo '<td>'.$row[terminal_name].'</td>';

                                            }
                                ?>       
                                  
                                        <td><?php echo $product_category; ?></td>    
                                        <td><?php echo $product_name; ?></td>    
                                        <td><?php echo $product_code; ?></td>    
                                        <td><?php echo $product_price; ?></td>    
                                        <td><?php echo $inhand_stock; ?></td>    
                                        <td><?php echo $stock_datetime; ?></td>       
                                        <td><?php echo $age; ?></td>       
                                    </tr> 
                                <?php        
                                    }
                                 ?>
		                      </tbody>
                            </table>
		<?php	


		break;	
	
		
	case 'trend' :

			
			$key_query = local("SELECT")." ".$centralise_group_name.$main_colum_name." p.product_name,p.product_code,p.product_category,SUM(i.invoice_qty) AS qty
			FROM device_backup_invoice i, ".$product_table." p".$main_terminal_table.$centralise_group_table ."
			WHERE  i.`".$inv_master_key_column."` ".local("IN")." (".$MULTI_Terminals.") AND p.`".$inv_master_key_column."` ".local("IN")." (".$MULTI_Terminals.") AND
			i.invoice_itemcode = p.product_code
            AND i.`".$inv_master_key_column."`= p.`".$inv_master_key_column."`
			AND invoice_itemtype_number = 1
			AND invoice_delete_flag='0'
            AND invoice_crn_item_status in (0,2)
            ".$where."
            AND ".$main_terminal_condition.$centralise_condition."
            DATE(invoice_date) BETWEEN '$from_date' AND '$to_date'
			GROUP BY i.".$group_by.",product_code
			ORDER BY SUM(invoice_qty) DESC";


			$report_header_array=array(local("Product Name"),local("Product Code"),local("Product Category"),local("Sold Qty"));
			//set price column cell alignment right-> header array keys//
			$price_alignment_right_columns=array();
			$total_value_shows=array(3);
		      
			$query_results=mysql_query($key_query);
			?>
            <table id="example_4" class="table table-striped table-bordered table-hover">
                <thead>
                                <tr><?php if($is_centralise=='1'){ ?>
                                    <th> <?php echo local("Group Name")  ?> </th>
                                    <?php } ?>
                                    <th> <?php echo local("Terminal Name")  ?> </th>
                                    <th> <?php echo local("Product Name")  ?> </th>
                                    <th> <?php echo local("Product Code")  ?> </th>
                                    <th> <?php echo local("Product Category")  ?> </th> 
                                    <th> <?php echo local("Sold Qty")  ?> </th>
				                </tr>
                            </thead>
                            <tbody>
                                <?php	
				

                                        while($row=mysql_fetch_array($query_results)){
                                            
                                            $group_name = $row[group_name];
                                            $terminal_name = $row[terminal_name];
                                            $product_category = $row[product_category];
                                            $product_code = $row[product_code];
                                            $product_name = $row[product_name];
                                            $qty = $row[qty];
                                            
                                ?>      <tr>
                                <?php if($is_centralise=='1'){ ?>
                                        <td><?php echo $group_name; ?></td>
                                        <?php } ?>
                                        <td><?php echo $terminal_name; ?></td>    
                                        <td><?php echo $product_category; ?></td>    
                                        <td><?php echo $product_code; ?></td>    
                                        <td><?php echo $product_name; ?></td>    
                                        <td><?php echo $qty; ?></td>     
                                        </tr>  
                                <?php        
                                    }
                                 ?>
                                </tbody>
                </table>



            <?php
		
			break;		
		
	
	case 'invoice_summary' :
	
        $cus = str_replace('"', "'",str_replace(['[', ']'], '', $_'.local("GET").'['cus']));
        
        if($is_centralise=='1'){
        
            $key_query="SELECT GM.`group_name` AS group_name,TM.`device_name` AS terminal_name, i.invoice_main_number, 
                (i.invoice_total_discount + i.invoice_full_discount ) AS full_discount, i.invoice_total_discount AS itemwise_total_discounts,
                 i.invoice_full_discount AS invoice_total_discounts, i.invoice_total,i.invoice_date,i.invoice_time,

                 IF(LENGTH(c.`customer_name`)>0,c.`customer_name`,i.invoice_customer_id ) AS invoice_customer_id, 

                 i.invoice_payment_method, IF(i.invoice_payment_method='Credit Card',i.invoice_payment_card_no, 
                 IF(i.invoice_payment_method='Cheque',i.invoice_payment_cheque_no, IF(i.invoice_payment_method='Credit',i.invoice_payment_credit_days,''))) AS payment_reference,
                  i.invoice_charge_total_tax, i.invoice_charge_total_charge,i.invoice_cashier_name

                  FROM `admin_users_subscription` TM,`admin_manage_centralized_device_group` GM ,device_backup_invoice i 

                  LEFT JOIN `admin_manage_centralized_customer` c  ON i.`group_id`=c.`group_id` AND i.key=c.key_id AND i.`invoice_customer_id`=c.`customer_id`

                  WHERE i.`key`=TM.`key_id` AND TM.`user_name`='$user_name' 
                  AND i.`group_id`=GM.`group_id` 

                  AND i.`invoice_delete_flag`='0' AND i.`invoice_itemtype_number`=1 

                  AND i.`group_id` IN (".$MULTI_Terminals.") AND i.`key` IN (".$MULTI_CENTRALISE_KEYS.") 
                 AND DATE(i.invoice_date) BETWEEN '$from_date' AND '$to_date' 
                 AND i.invoice_customer_id IN (".$cus.") 
                 GROUP BY i.group_id,i.invoice_main_number";
            
            
        }
        else{
		$key_query="SELECT TM.`device_name` AS terminal_name, i.invoice_main_number, (i.invoice_total_discount + i.invoice_full_discount ) AS full_discount, 
        i.invoice_total_discount AS itemwise_total_discounts, i.invoice_full_discount AS invoice_total_discounts, 
        i.invoice_total,i.invoice_date,i.invoice_time,IF(LENGTH(c.`customer_name`)>0,c.`customer_name`,i.invoice_customer_id ) AS invoice_customer_id, i.invoice_payment_method, 
        IF(i.invoice_payment_method='Credit Card',i.invoice_payment_card_no, IF(i.invoice_payment_method='Cheque',i.invoice_payment_cheque_no,
         IF(i.invoice_payment_method='Credit',i.invoice_payment_credit_days,''))) AS payment_reference,
          i.invoice_charge_total_tax, i.invoice_charge_total_charge,i.invoice_cashier_name 
          FROM `admin_users_subscription` TM ,
          device_backup_invoice i LEFT JOIN `device_backup_customer` c ON i.`key`=c.`key` AND i.`invoice_customer_id`=c.`customer_id`
          WHERE i.`key`=TM.`key_id` AND TM.`user_name`='$user_name' 
          AND i.`invoice_delete_flag`='0' 
          AND i.`invoice_itemtype_number`=1 
          AND i.`key` IN (".$MULTI_Terminals.") 
          AND DATE(i.invoice_date) BETWEEN '$from_date' AND '$to_date'  
          AND i.invoice_customer_id IN (".$cus.") 
          GROUP BY i.`key`,i.invoice_main_number ORDER BY CONCAT(invoice_date,' ',STR_TO_DATE(invoice_time, '%l:%i %p' )) DESC";
        }
        
      
		$report_header_array=array("Invoice Number","Invoice Amount(".$profile_currency.")", "Total Discounts(".$profile_currency.")", "Itemwise Discounts(".$profile_currency.")", "Invoice Discounts(".$profile_currency.")","Invoice Charge Total Tax(".$profile_currency.")","Invoice Charge Total Charge(".$profile_currency.")","Payment Method","Payment Ref No","Customer ID","Invoice Date","Invoice Time","Invoice Cashier");
        
        
        
        
        
	//set price column cell alignment right-> header array keys//
		$price_alignment_right_columns=array(1,2,3,4,5,6);
		$total_value_shows=array(1,2,3,4,5,6);
		$query_results=mysql_query($key_query);
		
		$Total_grand_total=0.00;
		$Total_cash_sale=0.00;
		$Total_chq_sale=0.00;
		$Total_credit_card_sale=0.00;
		$Total_staff_sale=0.00;
		$Total_credits_sale=0.00;
        
        ?>
        <table id="example_5" class="table table-striped table-bordered table-hover">
                <thead>
                                <tr><?php if($is_centralise=='1'){ ?>
                                    <th> Group Name </th>
                                    <?php } if($MULTI_TERMINALS_AVAILABLE =='1' ||  $is_centralise !='1'){ ?>
                                    <th> Terminal Name </th>
                                    <?php } ?>
                                    <th> Invoice Number </th>
                                    <th> Invoice Amount(<?php echo $profile_currency; ?>) </th>
                                    <th> Total Discounts(<?php echo $profile_currency; ?>) </th>
                                    <th> Itemwise Discounts(<?php echo $profile_currency; ?>) </th>
                                    <th> Invoice Discounts(<?php echo $profile_currency; ?>) </th>
                                    <th> Invoice Charge Total Tax(<?php echo $profile_currency; ?>) </th>
                                    <th> Invoice Charge Total Charge(<?php echo $profile_currency; ?>) </th>
                                    <th> Payment Method </th>
                                    <th> Customer </th>
                                    <th style="min-width: 80px;"> Invoice Date </th>
                                    <th> Invoice Time </th>
                                    <th> Invoice Cashier </th>
				                </tr>
                            </thead>
                            <tbody>
        
        <?php
        $invoicetotal = 0;    
        $fulldiscount = 0;    
        $itemwisetotaldiscounts = 0;    
        $invoicetotaldiscounts = 0;    
        $invoicechargetotaltax = 0;       
        $invoicechargetotalcharge = 0; 
        
		while($row=mysql_fetch_array($query_results)){
			
		$invoice_main_number = $row[invoice_main_number];
		$full_discount= $row[full_discount];
		$itemwise_total_discounts= $row[itemwise_total_discounts];
		$invoice_total_discounts= $row[invoice_total_discounts];
		$invoice_total = $row[invoice_total];
		$invoice_date = $row[invoice_date];
		$invoice_time = $row[invoice_time];
		$invoice_customer_id = $row[invoice_customer_id];
		$invoice_payment_method = $row[invoice_payment_method];
		$payment_reference = $row[payment_reference];
		$invoice_charge_total_tax = $row[invoice_charge_total_tax];
		$invoice_charge_total_charge = $row[invoice_charge_total_charge];
		$invoice_cashier_name = $row[invoice_cashier_name];
		$group_name = $row[group_name];
        $terminal_name = $row[terminal_name];
            
		$Total_grand_total=$Total_grand_total+$invoice_total;
		
	   if($invoice_payment_method=='Cash'){
		   
		$Total_cash_sale=$Total_cash_sale + $invoice_total;
		
		}
		elseif($invoice_payment_method=='Cheque'){
			
			$Total_chq_sale=$Total_chq_sale + $invoice_total;
			}
		elseif($invoice_payment_method=='Credit Card'){
			
			$Total_credit_card_sale=$Total_credit_card_sale + $invoice_total;
			}
		elseif($invoice_payment_method=='Credit'){
			
			$Total_credits_sale=$Total_credits_sale + $invoice_total;
			}
		elseif($invoice_payment_method=='Staff'){
			
			$Total_staff_sale=$Total_staff_sale + $invoice_total;
			}
	
		else if($invoice_payment_method=='Multi'){//m
			$Q_payments=mysql_query("SELECT p.`payment_type`  AS payment_type,SUM(p.`payment_amount`) AS pay_amount,GROUP_CONCAT(p.`payment_reference` SEPARATOR '/') AS pay_ref
FROM `device_backup_invoice_payment` p WHERE p.`".$inv_master_key_column."` IN (".$MULTI_Terminals.") AND p.`invoice_number`='$invoice_main_number' 
GROUP BY p.`invoice_number`,p.`payment_type`");
			
			$a='';
			while($rowp=mysql_fetch_array($Q_payments)){//w1			
			$payment_type=$rowp['payment_type'];
			$pay_amount=$rowp['pay_amount'];
			$pay_ref=$rowp['pay_ref'];
			
			
			///payment type total//
		if($payment_type=='Cash'){
		     $Total_cash_sale=$Total_cash_sale + $pay_amount;
		}
		elseif($payment_type=='Cheque'){
			
			$Total_chq_sale=$Total_chq_sale + $pay_amount;
			}
		elseif($payment_type=='Credit Card'){
			
			$Total_credit_card_sale=$Total_credit_card_sale + $pay_amount;
			}
		elseif($payment_type=='Credit'){
			
			$Total_credits_sale=$Total_credits_sale + $pay_amount;
			}
		elseif($payment_type=='Staff'){
			
			$Total_staff_sale=$Total_staff_sale + $pay_amount;
			}
			
		
			
			
			}//w1
			
			}//m
		
                                        $invoicetotal = floatval($invoicetotal)+floatval($invoice_total);    
                                        $fulldiscount = floatval($fulldiscount)+floatval($full_discount);    
                                        $itemwisetotaldiscounts = floatval($itemwisetotaldiscounts)+floatval($itemwise_total_discounts);    
                                        $invoicetotaldiscounts = floatval($invoicetotaldiscounts)+floatval($invoice_total_discounts);    
                                        $invoicechargetotatax = floatval($invoicechargetotaltax)+floatval($invoice_charge_total_tax);       
                                        $invoicechargetotalcharge = floatval($invoicechargetotalcharge)+floatval($invoice_charge_total_charge);    
		?>
		
		                              <tr><?php if($is_centralise=='1'){ ?>
                                        <td><?php echo $group_name; ?></td>
                                        <?php } if($MULTI_TERMINALS_AVAILABLE =='1' ||  $is_centralise !='1'){ ?>
                                        <td><?php echo $terminal_name; ?></td> 
                                        <?php } ?>  
		                                <td><?php echo $invoice_main_number; ?></td>    
                                        <td style="text-align:right;"><?php echo number_format($invoice_total,2); ?></td>    
                                        <td style="text-align:right;"><?php echo number_format($full_discount,2); ?></td>    
                                        <td style="text-align:right;"><?php echo number_format($itemwise_total_discounts,2); ?></td>    
                                        <td style="text-align:right;"><?php echo number_format($invoice_total_discounts,2); ?></td>    
                                        <td style="text-align:right;"><?php echo number_format($invoice_charge_total_tax,2); ?></td>       
                                        <td style="text-align:right;"><?php echo number_format($invoice_charge_total_charge,2); ?></td>       
                                        <td><?php echo $invoice_payment_method; ?></td>       
                                        <td><?php echo $invoice_customer_id; ?></td>       
                                        <td style="text-align:right; min-width: 80px;"><?php echo $invoice_date; ?></td>       
                                        <td style="text-align:right;"><?php echo $invoice_time; ?></td>       
                                        <td><?php echo $invoice_cashier_name; ?></td>    
                                    </tr>
		




        <?php
		}
        ?>
                                
                                </tbody>
                                <tfoot>
                                    <tr><?php if($is_centralise=='1'){ ?>
                                        <td></td>
                                        <?php }if($MULTI_TERMINALS_AVAILABLE =='1' ||  $is_centralise !='1'){  ?>
		                                <td></td>    
                                        <?php } ?>
		                                <td>Total</td>    
                                        <td style="text-align:right;"><?php echo number_format($invoicetotal,2); ?></td>    
                                        <td style="text-align:right;"><?php echo number_format($fulldiscount,2); ?></td>    
                                        <td style="text-align:right;"><?php echo number_format($itemwisetotaldiscounts,2); ?></td>    
                                        <td style="text-align:right;"><?php echo number_format($invoicetotaldiscounts,2); ?></td>    
                                        <td style="text-align:right;"><?php echo number_format($invoicechargetotaltax,2); ?></td>       
                                        <td style="text-align:right;"><?php echo number_format($invoicechargetotalcharge,2); ?></td>       
		                                <td></td>    
                                        <td></td>       
                                        <td></td>       
                                        <td></td>       
  
                                    </tr>
                                </tfoot>
                </table>
                                <?php
	
	
		
	
	break;	
	
	
	
	case 'invoice_detail' :
	
		$qty_ = 0;
		$cst = 0;
        $itemvalue = 0;
        $itemdiscount = 0;
		$unittax = 0;
        
        
		$key_query="SELECT  ".$centralise_group_name.$main_colum_name."i.`key`,i.invoice_main_number,p.`product_name`,i.invoice_itemcode,p.`product_category`,SUM(i.invoice_qty) AS qty,
		SUM(i.invoice_qty * i.invoice_item_cost) AS cost,SUM(i.invoice_price * i.invoice_qty) AS item_value, 
		SUM(((i.invoice_price * i.invoice_qty) - i.invoice_discount) * i.invoice_tax_value / 100) AS unit_tax,
		i.invoice_discount AS item_discount,
		i.invoice_customer_id,i.invoice_date,i.invoice_time,i.invoice_cashier_name, i.invoice_payment_method
		FROM device_backup_invoice i,`".$product_table."` p".$main_terminal_table.$centralise_group_table ."
		 WHERE ".$main_terminal_condition.$centralise_condition."i.invoice_itemcode=p.`product_code` AND i.invoice_itemtype_number = 1 AND
         i.`".$inv_master_key_column."` IN (".$MULTI_Terminals.")  AND
         p.`".$inv_master_key_column."` IN (".$MULTI_Terminals.")  
         ".$where."  
         ".$where2."  
         AND i.`invoice_delete_flag`='0' AND
         DATE(i.invoice_date) BETWEEN '$from_date' AND '$to_date' AND i.`invoice_crn_item_status` =0
		GROUP BY i.".$group_by.",i.invoice_number,i.`invoice_line_no`,i.invoice_itemcode
		ORDER BY CONCAT(invoice_date,' ',STR_TO_DATE(invoice_time, '%l:%i %p' )) DESC";
        
        //
        $query_results=mysql_query($key_query);
        
        
		?>                    <div class="table-responsive"> 
                              <table id="example_6" class="table table-striped table-bordered table-hover">
                                    <thead>
                                        <tr><?php if($is_centralise=='1'){ ?>
                                            <th> Group Name </th>
                                            <?php } if($MULTI_TERMINALS_AVAILABLE =='1' ||  $is_centralise !='1'){ ?>
                                            <th> Terminal Name </th>
                                            <?php } ?>
                                            <th> Invoice Number </th>
                                            <th> Product Code </th>
                                            <th> Product Name </th>
                                            <th> Product Category </th>
                                            <th> Sold Qty </th>
                                            <th> Total Cost(<?php echo $profile_currency; ?>) </th>
                                            <th> Total Value Based on List Price(<?php echo $profile_currency; ?>) </th>
                                            <th> Item Discount(<?php echo $profile_currency; ?>) </th>
                                            <th> Item Unit Tax(<?php echo $profile_currency; ?>) </th>
                                            <th> Payment Method </th>
                                            <th> Customer ID </th>
                                            <th> Invoice Date </th>
                                            <th> Invoice Time </th>
                                            <th> Invoice Cashier </th>
                                        </tr>
                                    </thead>
                                    <tbody>                         
                                <?php	
                                            while($row=mysql_fetch_array($query_results)){
                                                $group_name = $row[group_name];
                                                $terminal_name = $row[terminal_name];
                                        		$invoice_payment_method = $row[invoice_payment_method];
                                        		$invoice_main_number = $row[invoice_main_number];
                                                $product_name = $row[product_name];
                                                $invoice_itemcode = $row[invoice_itemcode];
                                                $product_category = $row[product_category];
                                                $qty = $row[qty];
                                                $cost = $row[cost];
                                                $item_value = $row[item_value];
                                                $unit_tax = $row[unit_tax];
                                                $item_discount= $row[item_discount];	
                                                $invoice_date = $row[invoice_date];
                                                $invoice_time = $row[invoice_time];
                                                $invoice_customer_id = $row[invoice_customer_id];
                                                $invoice_cashier_name = $row[invoice_cashier_name];
                                                //Add Total Invoicewise percentage discount to items//
                                                $inv_device_key=$row['key'];
                                                $getTotalDiscount_percentage_value=mysql_query("SELECT invoice_reference_value AS f FROM `device_backup_invoice` WHERE 
                                                `invoice_main_number` ='$invoice_main_number' AND 
                                                `invoice_itemtype_number`=3 AND `invoice_value_type`='Percentage' AND `key`='$inv_device_key' LIMIT 1");
                                              if(mysql_num_rows($getTotalDiscount_percentage_value)>0){
                                                    $rowD=mysql_fetch_array($getTotalDiscount_percentage_value);
                                                    $inv_percentage_value=$rowD[f];	

                                                    $get_itemwise_discount=(($item_value - $item_discount) * $inv_percentage_value )/100;

                                                    //New Itemwise Discount//
                                                    $item_discount =$item_discount+$get_itemwise_discount;


                                                }
                                                
                                            $qty_ = floatval($qty)+floatval($qty_);
                                            $cst = floatval($cost)+floatval($cst);
                                            $itemvalue = floatval($item_value)+floatval($itemvalue);
                                            $itemdiscount  = floatval($item_discount)+floatval($itemdiscount);
                                            $unittax = floatval($item_discount)+floatval($unittax);
			
                                ?>        
                                <tr>    <?php if($is_centralise=='1'){ ?>
                                        <td><?php echo $group_name; ?></td>
                                        <?php } if($MULTI_TERMINALS_AVAILABLE =='1' ||  $is_centralise !='1'){ ?>
                                        <td><?php echo $terminal_name; ?></td> 
                                        <?php } ?>
                                        <td><?php echo $invoice_main_number; ?></td>    
                                        <td><?php echo $product_name; ?></td>    
                                        <td><?php echo $invoice_itemcode; ?></td>    
                                        <td><?php echo $product_category; ?></td>    
                                        <td style="text-align:right;"><?php echo number_format($qty,2); ?></td>    
                                        <td style="text-align:right;"><?php echo number_format($cost,2); ?></td>       
                                        <td style="text-align:right;"><?php echo number_format($item_value,2); ?></td>       
                                        <td style="text-align:right;"><?php echo number_format($item_discount,2); ?></td>       
                                        <td style="text-align:right;"><?php echo number_format($unit_tax,2); ?></td>       
                                        <td><?php echo $invoice_payment_method; ?></td>    
                                        <td><?php echo $invoice_customer_id; ?></td>       
                                        <td><?php echo $invoice_date; ?></td>       
                                        <td><?php echo $invoice_time; ?></td>       
                                        <td><?php echo $invoice_cashier_name; ?></td>     
                                </tr>
                                <?php        
                                    }
                                 ?>
                                </tbody>
                                <tfoot>
                                <tr>    <?php if($is_centralise=='1'){ ?>
                                        <td></td>
                                        <?php } if($MULTI_TERMINALS_AVAILABLE =='1' ||  $is_centralise !='1'){ ?>
                                        <td></td>
                                        <?php  } ?>
                                        <td>Total</td>    
                                        <td></td>    
                                        <td></td>    
                                        <td></td>    
                                        <td style="text-align:right;"><?php echo number_format($qty_,2); ?></td>    
                                        <td style="text-align:right;"><?php echo number_format($cst,2); ?></td>       
                                        <td style="text-align:right;"><?php echo number_format($itemvalue,2); ?></td>       
                                        <td style="text-align:right;"><?php echo number_format($itemdiscount,2); ?></td>       
                                        <td style="text-align:right;"><?php echo number_format($unittax,2); ?></td>       
                                        <td></td>    
                                        <td></td>       
                                        <td></td>       
                                        <td></td>       
                                        <td></td>     
                                </tr>  
                                </tfoot>  
                </table>    
                              </div>
	<?php
	break;	
	
	
	
	case 'tax' :
			
				if($is_centralise=='1'){
					$t="SELECT 	GROUP_CONCAT(tax_code, '') AS t FROM `admin_manage_centralized_products_tax` WHERE ".$product_master_key_column."  IN (".$MULTI_CENTRALISE_KEYS.") ";
					}else{
				$t = "SELECT 	GROUP_CONCAT(tax_code, '') as t FROM device_backup_tax WHERE ".$product_master_key_column." IN (".$MULTI_Terminals.")
				AND tax_code <> 'SELECT'";
					}
				$query_results=mysql_query($t);
				while($row=mysql_fetch_array($query_results)){
						
					$t = $row[t];
				}
			
				 $key_query = "SELECT  ".$centralise_group_name.$main_colum_name." invoice_itemcode,invoice_main_number,
				 IF(invoice_itemtype_number = 1,(((invoice_price * invoice_qty) - invoice_discount) * invoice_tax_value / 100),'0') AS unit_tax,
				invoice_date,invoice_time,
				IF(invoice_itemtype_number = 4,invoice_charge_total_tax,'0') AS total_invoice_tax
				
				FROM device_backup_invoice i ".$main_terminal_table.$centralise_group_table ."
				 WHERE  ".$main_terminal_condition.$centralise_condition." i.`".$inv_master_key_column."` IN (".$MULTI_Terminals.")  
                ".$where1." 
                AND invoice_delete_flag='0' AND invoice_tax_value>0.00 AND DATE(invoice_date) BETWEEN '$from_date' AND '$to_date' and
				invoice_itemtype_number IN (1,4)";

				$query_results=mysql_query($key_query);
            
                ?>
				<table id="example_7" class="table table-striped table-bordered table-hover">
                <thead>
                                <tr><?php if($is_centralise=='1'){ ?>
                                    <th> Group Name </th>
                                    <?php } if($MULTI_TERMINALS_AVAILABLE =='1' ||  $is_centralise !='1'){?>
                                    <th> Terminal Name </th>
                                    <?php } ?>
                                    <th> Invoice Product Code </th>
                                    <th> Invoice Number </th>
                                    <th> Invoice Date </th>
                                    <th> Invoice Time </th>
                                    <th> Total Unit Tax(<?php echo $profile_currency; ?>) </th>
                                    <th> Total Invoice Tax(<?php echo $profile_currency; ?>) </th>
				                </tr>
                            </thead>
                            <tbody>
                               
                                <?php	

                                        while($row=mysql_fetch_array($query_results)){
                                        		$invoice_itemcode = $row[invoice_itemcode];
                                                $invoice_main_number = $row[invoice_main_number];
                                                $unit_tax = $row[unit_tax];
                                                $invoice_date = $row[invoice_date];
                                                $invoice_time = $row[invoice_time];
                                                $total_invoice_tax = $row[total_invoice_tax];
                                                $group_name = $row[group_name];
                                                $terminal_name = $row[terminal_name];


			
                                ?>      <tr><?php if($is_centralise=='1'){ ?>
                                        <td><?php echo $group_name; ?></td>
                                        <?php } if($MULTI_TERMINALS_AVAILABLE =='1' ||  $is_centralise !='1'){?>
                                        <td><?php echo $terminal_name; ?></td> 
                                        <?php } ?>
                                        <td><?php echo $invoice_itemcode; ?></td>    
                                        <td><?php echo $invoice_main_number; ?></td>      
                                        <td><?php echo $invoice_date; ?></td>    
                                        <td><?php echo $invoice_time; ?></td>    
                                        <td><?php echo number_format($unit_tax,2); ?></td>             
                                        <td><?php echo number_format($total_invoice_tax,2); ?></td>             
                                        </tr>  
                                <?php        
                                    }
                                 ?>
                                </tbody>
                </table>
                <?php
                break;
						
			
			

			
			
			case 'return' :
			
			    $report_header_add_array=array();
				if($is_centralise=='1'){
					$key_query = "SELECT  ".$centralise_group_name.$main_colum_name." l.`location_name` AS return_from,`return_id`,return_code, p.`product_name` AS return_product_name, return_cost, return_unit_value, return_qty, return_date,s.`supplier_name`
FROM `admin_manage_centralized_stock_return` r,`admin_manage_centralized_products` p,`admin_manage_centralized_suppliers` s,
`admin_manage_stock_location` l,`admin_manage_centralized_device_group` GM,`admin_users_subscription` TM
WHERE   r.`return_code`=p.`product_code` AND r.`return_supplier_id`=s.`supplier_id` AND r.`stores_location`=l.`loc_id`
AND r.`key_id` IN (".$MULTI_CENTRALISE_KEYS.") AND p.`key_id` IN (".$MULTI_CENTRALISE_KEYS.") AND s.`key_id` IN (".$MULTI_CENTRALISE_KEYS.")  AND l.`master_username`='$master_username' AND p.`group_id`=GM.`group_id`
 AND DATE(return_date) BETWEEN '$from_date' AND '$to_date' AND r.`key_id`=TM.`key_id` AND TM.`user_name`='$user_name'
ORDER BY r.`stores_location`,return_date ASC";
					$report_header_add_array[]="Return From";
					$price_alignment_right_columns=array(4,5);
                    $total_value_shows=array(6);
					
					}else{
										
					$key_query = "SELECT ".$main_colum_name."`return_id`,return_code, return_product_name, return_cost, return_unit_value, return_qty, return_date,s.`supplier_name`
FROM device_backup_return r,`device_backup_supplier` s,`admin_users_subscription` TM
WHERE r.`return_supplier_id`=s.`supplier_id`
AND r.`key`=TM.`key_id` AND TM.`user_name`='$user_name' AND 
r.`key` IN (".$MULTI_Terminals.") AND s.`key` IN (".$MULTI_Terminals.") AND DATE(return_date) BETWEEN '$from_date' AND '$to_date'
ORDER BY return_date ASC ";
 					//set price column cell alignment right-> header array keys//
					$price_alignment_right_columns=array(3,4);
					$total_value_shows=array(5);
					}

$report_header_array=$report_header_add_array + array("Return ID","Return Product Code","Product Name","Product Cost(".$profile_currency.")","Return Unit Value(".$profile_currency.")","Return Qty","Return Date","Return To");
					
				
					$query_results=mysql_query($key_query);
            ?>
    
            <table id="example_8" class="table table-striped table-bordered table-hover">
                <thead>
                                <tr><?php if($is_centralise=='1'){ ?>
                                    <th> Group Name </th>
                                    <?php } if($MULTI_TERMINALS_AVAILABLE =='1' ||  $is_centralise !='1'){ ?>
                                    <th> Terminal Name </th>
                                    <?php } if($is_centralise=='1'){ ?>
                                    <th> Return From </th>
                                    <?php } ?>
                                    <th> Return ID </th>
                                    <th> Return Product Code </th>
                                    <th> Product Name </th>
                                    <th> Product Cost(<?php echo $profile_currency; ?>) </th>
                                    <th> Return Unit Value(<?php echo $profile_currency; ?>) </th>
                                    <th> Return Qty </th>
                                    <th> Return Date </th>
                                    <th> Return To </th>
				                </tr>
                            </thead>
                            <tbody>
                               
                                <?php	
        
                                        $returncost = floatval($returncost)+floatval($return_cost);
                                        $returnunitvalue = floatval($returnunitvalue)+floatval($return_unit_value);
                                        $return_qty = floatval($returnqty)+floatval($return_qty);
                                        $i=1;
                                        while($row=mysql_fetch_array($query_results)){
                                                    
                                        $return_id = $row[return_id];
                                        $return_code = $row[return_code];
                                        $return_product_name = $row[return_product_name];
                                        $return_cost = $row[return_cost];
                                        $return_unit_value = $row[return_unit_value];
                                        $return_qty = $row[return_qty];
                                        $return_date = $row[return_date];
                                        $return_date = $row[return_date];
                                        $supplier_name =$row[supplier_name];
                                        $group_name = $row[group_name];
                                        $terminal_name = $row[terminal_name];
                                    echo '<tr>';
                                        if(strlen($return_id)==0){
                                            $return_id=$i;
                                            $i++;
                                            }
                                     if($is_centralise=='1'){ ?>
                                        <td><?php echo $group_name; ?></td>
                                        <?php } if($MULTI_TERMINALS_AVAILABLE =='1' ||  $is_centralise !='1'){ ?>
                                        <td><?php echo $terminal_name; ?></td> 
                                <?php   }
                                        if($is_centralise=='1'){
                                        echo '<td>'.$row[return_from].'</td>';
                                        }


			
                                ?>        
                                        <td><?php echo $return_id; ?></td>    
                                        <td><?php echo $return_code; ?></td>      
                                        <td><?php echo $return_product_name; ?></td>      
                                        <td style="text-align:right;"><?php echo number_format($return_cost,2); ?></td>    
                                        <td style="text-align:right;"><?php echo number_format($return_unit_value,2); ?></td>    
                                        <td style="text-align:right;"><?php echo number_format($return_qty,2); ?></td>             
                                        <td><?php echo $return_date; ?></td>             
                                        <td><?php echo $supplier_name; ?></td>     
                                    </tr>
                                <?php        
                                    }
                                 ?>
                                </tbody>
                                <tfoot>
                                    <tr><?php if($is_centralise=='1'){ ?>
                                        <td></td>
                                        <?php } if($MULTI_TERMINALS_AVAILABLE =='1' ||  $is_centralise !='1'){ ?>
                                        <td></td>
                                        <?php } if($is_centralise=='1'){ ?>
                                        <td></td>
                                        <?php } ?>
                                        <td>Total</td>    
                                        <td></td>      
                                        <td></td>      
                                        <td style="text-align:right;"></td>    
                                        <td style="text-align:right;"></td>    
                                        <td style="text-align:right;"></td>             
                                        <td></td>             
                                        <td></td>     
                                    </tr>
                                </tfoot>
                </table>
            <?php
		
			break;
						
	
				
			
		
		case 'categorywise_sale' :
					
			$key_query = "SELECT ".$centralise_group_name.$main_colum_name."p.product_category,SUM(s.invoice_qty) AS qty,SUM(s.`invoice_price` * s.`invoice_qty`) AS total_sale,
SUM(s.`invoice_item_cost` * s.`invoice_qty`) AS total_cost
FROM device_backup_invoice s, ".$product_table." p".$main_terminal_table.$centralise_group_table."
WHERE  s.`".$inv_master_key_column."` IN (".$MULTI_Terminals.") AND p.`".$inv_master_key_column."` IN (".$MULTI_Terminals.") AND
s.invoice_itemcode = p.product_code
AND invoice_itemtype_number = 1
AND invoice_delete_flag='0'
 ".$where."
AND ".$main_terminal_condition_.$centralise_condition_."
DATE(invoice_date) BETWEEN '$from_date' AND '$to_date'
GROUP BY s.".$group_by.",p.`product_category`
ORDER BY SUM(invoice_qty) DESC";

$report_header_array=array("Product Category","Sold Qty","Product Cost(".$profile_currency.")","Product Sales(".$profile_currency.")");
//set price column cell alignment right-> header array keys//
$price_alignment_right_columns=array(3,4);
$total_value_shows=array(1,3);
			$query_results=mysql_query($key_query);
			?>
                        <table id="example_9" class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr><?php if($is_centralise=='1'){ ?>
                                    <th> Group Name </th>
                                    <?php }  if($MULTI_TERMINALS_AVAILABLE =='1' ||  $is_centralise !='1'){?>
                                    <th> Terminal Name </th>
                                    <?php } ?>
                                    <th> Product Category </th>
                                    <th> Sold Qty </th>
                                    <th> Product Cost(<?php echo $profile_currency; ?>) </th>
                                    <th> Product Sales(<?php echo $profile_currency; ?>) </th>
				                </tr>
                            </thead>
                            <tbody>
                               
                                <?php	
                                        $qty_ = 0;
                                        $totalcost = 0;
                                        $totalsale = 0;
                                        while($row=mysql_fetch_array($query_results)){
                                        $group_name = $row[group_name];
                                        $terminal_name = $row[terminal_name];            
                                        $product_category = $row[product_category];
                                        $qty = $row[qty];
                                        $total_sale = $row[total_sale];
                                        $total_cost = $row[total_cost];
                                        
                                        $qty_ = floatval($qty_)+floatval($qty);
                                        $totalcost = floatval($totalcost)+floatval($total_cost);
                                        $totalsale = floatval($totalsale)+floatval($total_sale);   
                                            
                                ?>        
                                        <tr><?php if($is_centralise=='1'){ ?>
                                        <td><?php echo $group_name; ?></td>
                                        <?php }  if($MULTI_TERMINALS_AVAILABLE =='1' ||  $is_centralise !='1'){?>
                                        <td><?php echo $terminal_name; ?></td>   
                                        <?php } ?>    
                                        <td><?php echo $product_category; ?></td>
                                        <td style="text-align:right;"><?php echo $qty; ?></td>                
                                        <td style="text-align:right;"><?php echo number_format($total_cost,2); ?></td>      
                                        <td style="text-align:right;"><?php echo number_format($total_sale,2); ?></td>   
                                            
                                        </tr>
                                <?php        
                                    }
                                 ?>
                            </tbody>
                            <tfoot>
                                        <tr>
                                        <?php if($is_centralise=='1'){ ?>
                                        <td></td>
                                        <?php }  if($MULTI_TERMINALS_AVAILABLE =='1' ||  $is_centralise !='1'){?>
                                        <td></td>
                                        <?php } ?>    
                                        <td>Total</td>    
                                        <td style="text-align:right;"><?php echo $qty_; ?></td>                
                                        <td style="text-align:right;"><?php echo number_format($totalcost,2); ?></td>      
                                        <td style="text-align:right;"><?php echo number_format($totalsale,2); ?></td>    
                                        </tr>
                            </tfoot>
                        </table>
                
            <?php 
		
			break;		
		
	
	
	
	case 'deleted_invoice' :
	

		
				$key_query = "SELECT  ".$centralise_group_name.$main_colum_name."i.invoice_main_number,p.`product_name`,i.invoice_itemcode,p.`product_category`,SUM(i.invoice_qty) AS qty,
		SUM(i.invoice_qty * i.invoice_item_cost) AS cost,SUM(i.invoice_price * i.invoice_qty) AS item_value, 
		SUM(((i.invoice_price * i.invoice_qty) - i.invoice_discount) * i.invoice_tax_value / 100) AS unit_tax,
		SUM(i.`invoice_discount`) AS item_discount,
		i.invoice_full_discount ,
		i.invoice_total,i.invoice_date,i.invoice_time,i.invoice_customer_id, i.invoice_payment_method,		
		IF(i.invoice_payment_method='Credit Card',i.invoice_payment_card_no,
		IF(i.invoice_payment_method='Cheque',i.invoice_payment_cheque_no,
		IF(i.invoice_payment_method='Credit',i.invoice_payment_credit_days,''))) AS payment_reference,
		i.invoice_charge_total_tax, i.invoice_charge_total_charge,i.invoice_cashier_name
		FROM device_backup_invoice i,`".$product_table."` p".$main_terminal_table.$centralise_group_table."
        
		 WHERE i.invoice_itemcode=p.`product_code` AND i.invoice_itemtype_number = 1 AND i.`invoice_delete_flag`='1'
          ".$where."
          ".$where2."
AND ".$main_terminal_condition.$centralise_condition."
         
		  i.`".$inv_master_key_column."` IN (".$MULTI_Terminals.") AND 
          DATE(i.invoice_date) BETWEEN '$from_date' AND '$to_date'
		GROUP BY i.".$group_by.",i.invoice_number,i.`invoice_line_no`,i.invoice_itemcode";
	
		$query_results=mysql_query($key_query);
		
        ?>
         <table id="example_10" class="table table-striped table-bordered table-hover">
                <thead>
                                <tr><?php if($is_centralise=='1'){ ?>
                                    <th> Group Name </th>
                                    <?php }  if($MULTI_TERMINALS_AVAILABLE =='1' ||  $is_centralise !='1'){?>
                                    <th> Terminal Name </th>
                                    <?php } ?>
                                    <th> Invoice Number </th>
                                    <th> Product Name </th>
                                    <th> Product Code </th>
                                    <th> Product Category </th>
                                    <th> Sold Qty </th>
                                    <th> Total Cost(<?php echo $profile_currency; ?>) </th>
                                    <th> Total Value Based on List Price(<?php echo $profile_currency; ?>) </th>
                                    <th> Item Discount(<?php echo $profile_currency; ?>) </th>
                                    <th> Total Unit Tax(<?php echo $profile_currency; ?>) </th>
                                    <th> Invoice Total Discount(<?php echo $profile_currency; ?>) </th>
                                    <th> Invoice Total Value(<?php echo $profile_currency; ?>) </th>
                                    <th> Invoice Charge Total Tax(<?php echo $profile_currency; ?>) </th>
                                    <th> Invoice Charge Total Charge(<?php echo $profile_currency; ?>) </th>
                                    <th> Payment Method </th>
                                    <th> Payment Ref No </th>
                                    <th> Customer ID </th>
                                    <th> Invoice Date </th>
                                    <th> Invoice Time </th>
                                    <th> Invoice Cashier </th>
				                </tr>
                            </thead>
                            <tbody>
                               
                                <?php	
                                        
                                
                                        while($row=mysql_fetch_array($query_results)){
                                        $group_name = $row[group_name];
                                        $terminal_name = $row[terminal_name];             
                                        $invoice_main_number = $row[invoice_main_number];
                                        $product_name = $row[product_name];
                                        $invoice_itemcode = $row[invoice_itemcode];
                                        $product_category = $row[product_category];
                                        $qty = $row[qty];
                                        $cost = $row[cost];
                                        $item_value = $row[item_value];
                                        $unit_tax = $row[unit_tax];
                                        $item_discount= $row[item_discount];
                                        $invoice_total = $row[invoice_total];
                                        $invoice_date = $row[invoice_date];
                                        $invoice_time = $row[invoice_time];
                                        $invoice_customer_id = $row[invoice_customer_id];
                                        $invoice_payment_method = $row[invoice_payment_method];
                                        $payment_reference = $row[payment_reference];
                                        $invoice_charge_total_tax = $row[invoice_charge_total_tax];
                                        $invoice_charge_total_charge = $row[invoice_charge_total_charge];
                                        $invoice_full_discount = $row[invoice_full_discount];
                                        $invoice_cashier_name = $row[invoice_cashier_name];


                                        if($invoice_payment_method=='Multi'){
                                            $Q_payments=mysql_query("SELECT GROUP_CONCAT(DISTINCT p.`payment_type` SEPARATOR '/') AS f FROM `device_backup_invoice_payment` p WHERE p.".$group_by.",p.`".$inv_master_key_column."` IN (".$MULTI_Terminals.") AND p.`invoice_number`='$invoice_main_number' LIMIT 1");
                                            $rowp=mysql_fetch_array($Q_payments);			
                                            $invoice_payment_method=$rowp['f'];

                                            }


                                ?>      <tr>
                                        <?php if($is_centralise=='1'){ ?>
                                        <td><?php echo $group_name; ?></td>
                                        <?php }  if($MULTI_TERMINALS_AVAILABLE =='1' ||  $is_centralise !='1'){?>
                                        <td><?php echo $terminal_name; ?></td>
                                        <?php } ?>
                                        <td><?php echo $invoice_main_number; ?></td>    
                                        <td><?php echo $product_name; ?></td>                
                                        <td><?php echo $invoice_itemcode; ?></td>      
                                        <td><?php echo $product_category; ?></td>    
                                        <td><?php echo $qty; ?></td>    
                                        <td><?php echo $cost; ?></td>    
                                        <td><?php echo $item_value; ?></td>    
                                        <td><?php echo $item_discount; ?></td>    
                                        <td><?php echo $unit_tax; ?></td>    
                                        <td><?php echo $invoice_full_discount; ?></td>    
                                        <td><?php echo $invoice_total; ?></td>    
                                        <td><?php echo $invoice_charge_total_tax; ?></td>    
                                        <td><?php echo $invoice_charge_total_charge; ?></td>    
                                        <td><?php echo $invoice_payment_method; ?></td>    
                                        <td><?php echo $invoice_payment_card_no; ?></td>    
                                        <td><?php echo $invoice_customer_id; ?></td>    
                                        <td><?php echo $invoice_date; ?></td>    
                                        <td><?php echo $invoice_time; ?></td>    
                                        <td><?php echo $invoice_cashier_name; ?></td>    
                                <?php        
                                    }
                                 ?>
                                </tbody>
                </table>
    

    <?php
		
	
	
	break;		
	
					
		
	
	case 'other_cash' :
	
		if($is_centralise=='1'){
			$required_column='`group_id`';
			}else{
				
				$required_column='`key`';
				}
	
		$to_date=$from_date;
		$key_query="SELECT   ".$centralise_group_name.$main_colum_name."".$required_column." AS key1,
		  transaction_type,
		  IF(`transaction_type`=1,'Deposit','Withdraw') AS transaction_type_desc,
		  `transaction_reason`,
		  `other_description`,
		  `transaction_amount`,
		  `cashier_username`,
		    transaction_date
		FROM
		  `device_backup_cash_transactions` p,`admin_manage_centralized_device_group` GM,`admin_users_subscription` TM
		WHERE p.`group_id`=GM.`group_id` AND p.`key`=TM.`key_id` AND `".$inv_master_key_column."` IN (".$MULTI_Terminals.")  AND DATE(`transaction_date`)='$from_date' 
		ORDER BY key,`transaction_date` ASC";
	
		if($is_centralise=='1'){
			$report_header_array_value='local("Device Name"),';
			$column_index=2;
			}else{
				$column_index=1;
				
				}
		$report_header_array=array($report_header_array_value."Transaction Name","Transaction Amount(".$profile_currency.")","Transaction Type","Transaction Description","Cashier","Transaction Time");
		//set price column cell alignment right-> header array keys//
		$price_alignment_right_columns=array($column_index);
		$total_value_shows=array($column_index);
			
		$query_results=mysql_query($key_query);
		
		$Total_deposit=0.00;
		$Total_withdraw=0.00;

        //find total cash sale///
		$Q_cash_sale_1=mysql_query("SELECT SUM(p.invoice_total) AS f FROM (SELECT  i.invoice_total 	FROM device_backup_invoice i WHERE i.`invoice_delete_flag`='0' AND i.invoice_payment_method='Cash' AND i.`".$inv_master_key_column."` IN (".$MULTI_Terminals.")  AND DATE(i.invoice_date) ='$from_date'
GROUP BY i.".$group_by.",i.invoice_number) AS p");
			$row1=mysql_fetch_array($Q_cash_sale_1);
			$get_cash_sale_1=$row1[f];

$Q_cash_sale_2=mysql_query("SELECT SUM(q.pay_amount) AS f FROM (SELECT SUM(p.`payment_amount`) AS pay_amount
FROM `device_backup_invoice_payment` p WHERE p.`".$inv_master_key_column."` IN (".$MULTI_Terminals.") AND DATE(p.`invoice_datetime`)='$from_date' 
AND p.`payment_type` ='Cash' GROUP BY p.".$group_by.",p.`invoice_number`,p.`payment_type`) AS q");
            $row2=mysql_fetch_array($Q_cash_sale_2);
			$get_cash_sale_2=$row2[f];


			if(strlen($get_cash_sale_1)==0){
				$get_cash_sale_1=0.00;
				}
			if(strlen($get_cash_sale_2)==0){
				$get_cash_sale_2=0.00;
				}
		
		//Total cash  balance amount//
		$Total_cash_balance=($get_cash_sale_1+$get_cash_sale_2+$Total_deposit) - $Total_withdraw;
		
        ?>                    <div class="table-responsive"> 
                              <table id="example_11" class="table table-striped table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <?php if($is_centralise=='1'){ ?>
                                            <th> Group Name </th>
                                            <?php }  if($MULTI_TERMINALS_AVAILABLE =='1' ||  $is_centralise !='1'){?>
                                            <th> Terminal Name </th>
                                            <?php } ?>
                                            <th> Device Name </th>
                                            <th> Transaction Name </th>
                                            <th> Transaction Amount(<?php echo $profile_currency; ?>) </th>
                                            <th> Transaction Type </th>
                                            <th> Transaction Description </th>
                                            <th> Cashier </th>
                                            <th> Transaction Time </th>
                                        </tr>
                                    </thead>
                                    <tbody>                         
                                <?php	
        
                                    $amount = 0;
                                            while($row=mysql_fetch_array($query_results)){
                                                    $group_name = $row[group_name];
                                                    $terminal_name = $row[terminal_name];        
                                        		    $transaction_device_name =deviceName($row['key1'],$user_name,$is_centralise);
                                                    $transaction_type = $row[transaction_type];
                                                    $transaction_type_desc= $row[transaction_type_desc];
                                                    $transaction_reason = $row[transaction_reason];
                                                    $other_description = $row[other_description];
                                                    $transaction_amount = $row[transaction_amount];
                                                    $cashier_username = $row[cashier_username];
                                                    //$transaction_time = date('Y-m-d h:i A',strtotime($row[transaction_date]));
                                                    $transaction_time = date('h:i A',strtotime($row[transaction_date]));


                                                   if($transaction_type=='1'){		   
                                                    $Total_deposit=$Total_deposit + $transaction_amount;		
                                                    }else{			
                                                    $Total_withdraw=$Total_withdraw + $transaction_amount;				
                                                        }

                                                    
                                                    if($is_centralise=='1'){
                                                       $dataArray1['Device Name']=$transaction_device_name;
                                                    }
                                                
			                                         $amount = $amount+$transaction_amount;
                                ?>        
                                <tr>    <?php if($is_centralise=='1'){ ?>
                                        <td><?php echo $group_name; ?></td>
                                            <?php }  if($MULTI_TERMINALS_AVAILABLE =='1' ||  $is_centralise !='1'){?>
                                        <td><?php echo $terminal_name; ?></td>
                                            <?php } ?>
                                        <td><?php echo $transaction_device_name; ?></td>    
                                        <td><?php echo $transaction_reason; ?></td>    
                                        <td><?php echo $transaction_amount; ?></td>    
                                        <td><?php echo $transaction_type_desc; ?></td>    
                                        <td><?php echo $other_description; ?></td>    
                                        <td><?php echo $cashier_username; ?></td>    
                                        <td><?php echo $transaction_time; ?></td>    
    
                                </tr>
                                <?php        
                                    }
                                 ?>
                                        
                                <tr>
                                        <?php if($is_centralise=='1'){ ?>
                                        <td></td>    
                                        <?php }  if($MULTI_TERMINALS_AVAILABLE =='1' ||  $is_centralise !='1'){?>
                                        <td></td> 
                                        <?php } ?>
                                        <td></td>    
                                        <td>total cash sale</td>      
                                        <td><?php echo $amount;?></td>      
                                        <td>      
                                        <td>      
                                        <td>      
                                        <td>      
    
                                </tr>
                                </tbody>
                </table>    
                              </div>
	<?php
        
	
	
	break;	
	
	
		case 'creditors_report' :
		
			
						  $key_query1 = "SELECT ".$centralise_group_name.$main_colum_name."c.`customer_name`,c.`customer_address`,c.`customer_phone`,s.`previous_outstanding`,s.`transaction_type` AS Transaction_type
,s.settle_amount AS transaction_amount,s.`settle_date` AS transaction_date,
s.`payment_type`,s.`payment_reference`,s.`cheque_date`,
IF(s.transaction_type='INVOICE',s.`previous_outstanding` + s.`settle_amount`,s.`previous_outstanding` - s.`settle_amount`) AS New_Outstanding
FROM `device_backup_credit_settlements` s,`".$customer_table."` c".$main_terminal_table.$centralise_group_table." WHERE 
s.customer_id=c.`customer_id` AND s.`key`=c.`key` AND s.".$inv_master_key_column." IN (".$MULTI_Terminals.") 
AND c.".$product_master_key_column." IN (".$MULTI_Terminals.") AND
".$main_terminal_condition_.$centralise_condition_."
 DATE(settle_date) BETWEEN '$from_date' AND '$to_date'
ORDER BY s.`customer_id`,s.`settle_date` ASC";

		
		$credit_results=mysql_query($key_query1);
		$previousoutstanding = 0;	
		$transactionamount = 0;	
        $NewOutstanding = 0;
			?>
                        <table id="example_12" class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr><?php if($is_centralise=='1'){ ?>
                                            <th> Group Name </th>
                                            <?php }  if($MULTI_TERMINALS_AVAILABLE =='1' ||  $is_centralise !='1'){?>
                                            <th> Terminal Name </th>
                                            <?php } ?>
                                    <th> Customer Name </th>
                                    <th> Customer Address </th>
                                    <th> Customer Phone </th>
                                    <th> Previous Outstanding(<?php echo $profile_currency; ?>) </th>
                                    <th> Transaction Type </th>
                                    <th> Transaction Amount(<?php echo $profile_currency; ?>) </th>
                                    <th> Transaction Date </th>
                                    <th> Payment Type </th>
                                    <th> Payment Reference </th>
                                    <th> Cheque Date </th>
                                    <th> New Outstanding (<?php echo $profile_currency; ?>)</th>
				                </tr>
                            </thead>
                            <tbody>
                                        
                                <?php	
                                        while($row=mysql_fetch_array($credit_results)){
                                                    
                                        $customer_name = $row[customer_name];
                                        $customer_address = $row[customer_address];
                                        $customer_phone = $row[customer_phone];
                                        $previous_outstanding = $row[previous_outstanding];
                                        $transaction_type = $row[Transaction_type];
                                        $transaction_amount = $row[transaction_amount];
                                        $transaction_date = $row[transaction_date];
                                        $payment_type = $row[payment_type];
                                        $payment_reference = $row[payment_reference];
                                        $cheque_date = $row[cheque_date];
                                        $New_Outstanding = $row[New_Outstanding];
                                        $group_name = $row[group_name];
                                        $terminal_name = $row[terminal_name]; 
                                                
                                        $previousoutstanding = floatval($previousoutstanding)+floatval($previous_outstanding);
                                        if($transaction_type == 'INVOICE'){   
                                        $transactionamount = floatval($transactionamount)+floatval($transaction_amount);
                                        }else{
                                        $transactionamount = floatval($transactionamount)-floatval($transaction_amount);
                                        }
                                        $NewOutstanding = floatval($NewOutstanding)+floatval($New_Outstanding);
                                            
                                ?>        
                                        <tr><?php if($is_centralise=='1'){ ?>
                                        <td><?php echo $group_name; ?></td>
                                            <?php }  if($MULTI_TERMINALS_AVAILABLE =='1' ||  $is_centralise !='1'){?>
                                        <td><?php echo $terminal_name; ?></td>
                                            <?php } ?>
                                        <td><?php echo $customer_name; ?></td>    
                                        <td><?php echo $customer_address; ?></td>                
                                        <td><?php echo $customer_phone; ?></td>      
                                        <td><?php echo number_format($previous_outstanding,2); ?></td>    
                                        <td><?php echo $transaction_type; ?></td>    
                                        <td><?php echo number_format($transaction_amount,2); ?></td>    
                                        <td><?php echo $transaction_date; ?></td>        
                                        <td><?php echo $payment_type; ?></td>    
                                        <td><?php echo $payment_reference; ?></td>    
                                        <td><?php echo $cheque_date; ?></td>    
                                        <td><?php echo $New_Outstanding; ?></td>    
                                        </tr>
                                <?php        
                                    }
                                 ?>
                            </tbody>
                            <tfoot>
                                         <tr>
                                        <?php if($is_centralise=='1'){ ?>
                                        <td></td>    
                                        <?php }  if($MULTI_TERMINALS_AVAILABLE =='1' ||  $is_centralise !='1'){?>
                                        <td></td> 
                                        <?php } ?>     
                                        <td>Total</td>    
                                        <td></td>                
                                        <td></td>      
                                        <td><?php  number_format($previousoutstanding,2); ?></td>    
                                        <td></td>    
                                        <td><?php echo number_format($transactionamount,2); ?></td>    
                                        <td></td>    
                                        <td></td>    
                                        <td></td>    
                                        <td></td>    
                                        <td><?php echo number_format($NewOutstanding-$previousoutstanding,2); ?></td>     
                                        </tr>
                            </tfoot>
                        </table>
                
            <?php 
			
		
			break;		
			
			
		case 'outlets_sales' :
		
		//Get POS list - Attached to the user//
		if($is_centralise=='1'){
			$device_list_query="SELECT DISTINCT group_id AS f FROM `admin_manage_centralized_device_group` g 
			WHERE g.`group_id`IN (".$MULTI_Terminals.")";			
			}else{
			$device_list_query="SELECT DISTINCT key_id  AS f FROM `admin_users_subscription` s 
		WHERE s.`key_id`IN (".$MULTI_Terminals.")";
				
				}
		//echo $device_list_query;
		
		$customer_list=mysql_query($device_list_query);
		
		$pos_array=array();
		$i=0;
		$all_keys='';
		while($rowk=mysql_fetch_array($customer_list)){//w1
			$pos_key_id=$rowk[f];
			$pos_array[$i]=$pos_key_id;
			
			$all_keys .="'".$pos_key_id."',";
			$i++;
			
			}//w1
		
		
		$all_keys =rtrim($all_keys,",");
		
			
		 /********************************** Headers ***************************************************/	
		//get all category list//	
		if($is_centralise=='1'){
			$category_query="SELECT DISTINCT `category` AS f FROM `admin_manage_centralized_category` c 
WHERE c.`key_id`='$master_username'  ORDER BY category ASC";		
			
			}else{

			$category_query="SELECT DISTINCT `category_name` AS f FROM `device_backup_category` c 
		WHERE c.`key` IN (".$all_keys.") AND category_name <>'SELECT' ORDER BY category_name ASC";	
				
				}
		$getCategories=mysql_query($category_query);
		
		$report_header_array[0]="Outlet Name";
		$k=1;		
		$price_alignment_right_columns=array();
		$total_value_shows=array();
		$product_catgeory_array=array();
		
		
		$k=2;
		$report_header_array[$k]="Total Sales";
		$total_value_shows[]=$k;
		$price_alignment_right_columns[]=$k;
		
		
		while($rowC=mysql_fetch_array($getCategories)){//w2
		
		$category_name=$rowC['f'];
		$report_header_array[$k]=$category_name;
		$product_catgeory_array[$k-1]=$category_name;
		//set price column cell alignment right-> header array keys//
		$price_alignment_right_columns[]=$k;
		$total_value_shows[]=$k;
		   
		$k++;		
		}//w2
		
		
		/*********************************************************************************************************/
		
		
		
		$data_id=0;
		foreach($pos_array as $key_val=>$pos_key){//f1
		
		$sales_data_array=array();
		$discount_data_array=array();
		$return_data_array=array();
		
		$pos_device_name=deviceName($pos_key,$user_name,$is_centralise);
		
		$sales_data_array['Outlet Name']=$pos_device_name;
		$discount_data_array['Outlet Name']=$pos_device_name;
		$return_data_array['Outlet Name']=$pos_device_name;
		
		$outletwise_category_line_total=0.00;
		$outletwise_discount_line_total=0.00;
	    $outletwise_return_line_total=0.00;
		foreach($product_catgeory_array as $id_key=> $product_category_name){//f2
		
				 $key_query = "SELECT  
		SUM((invoice_price * invoice_qty) - invoice_discount) AS category_sale, 
		SUM(invoice_discount) AS category_discount
		FROM device_backup_invoice i, ".$product_table." p".$main_terminal_table.$centralise_group_table."
		WHERE i.`".$inv_master_key_column."` IN (".$MULTI_Terminals.") AND p.`".$product_master_key_column."` IN (".$MULTI_Terminals.")
		 AND i.`invoice_delete_flag`='0' 
         ".$where."
         AND ".$main_terminal_condition.$centralise_condition."
          i.invoice_itemcode = p.product_code
		AND DATE(invoice_date) BETWEEN '$from_date' AND '$to_date'
		AND p.`product_category`='$product_category_name'
		GROUP BY i.".$group_by.",i.`".$inv_master_key_column."`,p.`product_category`";
		
			
				$query_results=mysql_query($key_query);		
				$row=mysql_fetch_array($query_results);	
				$category_sale = $row[category_sale];
				$category_discount = $row[category_discount];
				
				if(strlen($category_sale)==0){
					$category_sale =0.00;
					}
				
				if(strlen($category_discount)==0){
					$category_discount =0.00;
					}
				

				//credit note//
				$returnResults=mysql_query("SELECT   IFNULL(SUM(credit_note_price),0.00) AS credit_note_returns
FROM `device_backup_credit_note` i,`device_backup_invoice` c, ".$product_table." p".$main_terminal_table.$centralise_group_table."
WHERE i.`".$inv_master_key_column."` IN (".$MULTI_Terminals.") AND p.`".$product_master_key_column."` IN (".$MULTI_Terminals.") AND c.`".$inv_master_key_column."` IN (".$MULTI_Terminals.")
 AND i.`credit_note_item_code` = p.product_code AND i.`credit_note_invoice_number`=c.`invoice_main_number`
AND i.`credit_note_item_code`=c.`invoice_itemcode`  AND i.`credit_note_flag_delete`='0' 
".$where."
         AND ".$main_terminal_condition.$centralise_condition."
 p.`product_category`='$product_category_name' AND i.`credit_note_type`='CN'
AND DATE(c.`invoice_date`) BETWEEN '$from_date' AND '$to_date'
");

				$rowR=mysql_fetch_array($returnResults);	
				$credit_note_returns = $rowR[credit_note_returns];
				if(strlen($credit_note_returns)==0){
					$credit_note_returns =0.00;
					}
					
					//cash refund
					$cashRefundResults=mysql_query("SELECT   IFNULL(SUM(credit_note_price),0.00) AS cash_returns
FROM `device_backup_credit_note` i,`device_backup_invoice` c, ".$product_table." p".$main_terminal_table.$centralise_group_table."
WHERE i.`".$inv_master_key_column."` IN (".$MULTI_Terminals.") AND p.`".$product_master_key_column."`IN (".$MULTI_Terminals.") AND c.`".$inv_master_key_column."` IN (".$MULTI_Terminals.")
 AND i.`credit_note_item_code` = p.product_code AND i.`credit_note_invoice_number`=c.`invoice_main_number`
AND i.`credit_note_item_code`=c.`invoice_itemcode`
  AND i.`credit_note_flag_delete`='0' 
 ".$where."
         AND ".$main_terminal_condition.$centralise_condition."
p.`product_category`='$product_category_name' AND i.`credit_note_type`='CR'
AND DATE(c.`invoice_date`) BETWEEN '$from_date' AND '$to_date'
");

				$rowR=mysql_fetch_array($cashRefundResults);	
				$cash_returns = $rowR[cash_returns];
				if(strlen($cash_returns)==0){
					$cash_returns =0.00;
					}
			
				///Cash Refund Reduce from Category Sales//
				$outletwise_category_line_total = $outletwise_category_line_total - $cash_returns;
					
					
				//array data set//
				$sales_data_array[$product_category_name]=($category_sale - $cash_returns - $credit_note_returns);
				$discount_data_array[$product_category_name]=$category_discount;
				$outletwise_category_line_total = $outletwise_category_line_total + $category_sale;
				$outletwise_discount_line_total=$outletwise_discount_line_total+ $category_discount;	
					
			
				$return_data_array[$product_category_name]=$credit_note_returns + $cash_returns;
				$outletwise_return_line_total=$outletwise_return_line_total + $credit_note_returns + $cash_returns;



		
		}//f2
		
		$sales_data_array['Total Sales']=$outletwise_category_line_total;
		$discount_data_array['Total Sales']=$outletwise_discount_line_total;
		$return_data_array['Total Sales']=$outletwise_return_line_total;
		
		
		$data[]=$sales_data_array;
		$discount_data[]=$discount_data_array;
		$credit_note_data[]=$return_data_array;
		
		
		
		
		}//f1
		
		
		
		
		
		$getheaderCount =sizeof($report_header_array);
		//Initialise category varible for counting discount and credit notes//
		for($k=1;$k < ($getheaderCount-1);$k++){//f3	
		
		//Variable : Discount_Catgeory_name
		$categry_name_variable=$report_header_array[$k];
		$categry_name_variable1='Discount_'.$categry_name_variable;
		$categry_name_variable2='Return_'.$categry_name_variable;
		$$categry_name_variable1=0.00;//$Discount_OTHER=0.00
		$$categry_name_variable2=0.00;//$Return_OTHER=0.00
		}//f3
		
		 //$categry_name_variable.' :'.$Discount_VHJNJ;
		

		
			
		//Get Total Discount of All Categories//
		foreach($discount_data as $key=>$dataArray_1){//f2
			
			
			for($k=1;$k < ($getheaderCount-1);$k++){//f3	
			
			$categry_name_1=$report_header_array[$k];
			$categry_name_variable1='Discount_'.$categry_name_1;
			$DiscountValue=$dataArray_1[$categry_name_1];
			
			//$Discount_OTHER = $Discount_OTHER + 500.00;
			$$categry_name_variable1 = $$categry_name_variable1 + $DiscountValue;
			
	
			}//f3
				
		}//f2
		
		
		//Get Total Credit Notes of All Categories//
		foreach($credit_note_data as $key=>$dataArray_2){//f2
			
			
			for($k=1;$k < ($getheaderCount-1);$k++){//f3	
			
			$categry_name_2=$report_header_array[$k];
			$categry_name_variable2='Return_'.$categry_name_2;
			$CreditNoteValue=$dataArray_2[$categry_name_2];
			
			//$Return_OTHER = $Return_OTHER + 100.00;
			$$categry_name_variable2 = $$categry_name_variable2 + $CreditNoteValue;
			
	
			}//f3
				
		}//f2
		
		
		
		//Create Discount & Credit Note Single Array//
		$Discount_Total_Array=array();
		$Return_Total_Array=array();
		
		$Discount_Total_Array[0]="Discount";
		$Return_Total_Array[0]="Return (Customer)";
		for($k=1;$k < ($getheaderCount - 1);$k++){//f3	
		
		//varibles : Discount_Catgeory_Name & Return_Category_Name
		$categry_name_variable=$report_header_array[$k];
		$categry_name_variable1='Discount_'.$categry_name_variable;
		$categry_name_variable2='Return_'.$categry_name_variable;
		
		$Discount_Total_Array[$k]=$$categry_name_variable1;//$Discount_OTHER
		$Return_Total_Array[$k]=$$categry_name_variable2;//$Return_OTHER
		

		}//f3
		
		
		
	
	
		
		$filename = "SPL_Outlets_Sales_Summary _". $current_date . ".xls";
		$report_name='Outlets Daily Summary Report';
	    $worksheet_name='Outlets Daily Summary';
		$device_name='ALL OUTLETS';
        //print_r($data);
$startDataPoint=0;	
        
        ?>
         <table id="example_13" class="table table-striped table-bordered table-hover">
            <thead>


            <?php
            //foreach($report_header_array as $key=>$dataArray){//f2
                    $outlet_name='Outlet Name';
                    $total_sales='Total Sales';

                    '.local("echo").'   '<tr>';
                ?>
                    <th> <?php echo $outlet_name; ?> </th>
                    <th> <?php echo $total_sales; ?> </th>
             
                <?php  
        
                    $array1 = $report_header_array;
                    $array2 = array( $outlet_name, $total_sales);
                    $result = array_diff($array1, $array2);

                   foreach ( $result as $key => $value )
                        {?>
                            <th> <?php echo $value; ?> </th>
                        <?php       
                        }
                    echo '</tr>';
                    //if($key == 0){break;}

            //}//f2?>
            </thead>
            <tbody>
                <?php
                foreach($data as $key=>$dataArray){//f2

                        echo   '<tr>';
                    $outlet_name=$dataArray['Outlet Name'];
                    $total_sales=$dataArray['Total Sales'];
                    $array3 = $dataArray;
                    $result2 = array_diff($array3, $array2);
                    ?>
                    
                        <td> <?php echo $outlet_name; ?> </td>
                        <td> <?php echo $total_sales; ?> </td>
                    <?php       foreach ( $result as $key => $value )
                            {?>
                                <td> <?php echo number_format($result2[$value],2); ?> </td>
                            <?php       
                            }
                        echo '</tr>';
                        //if($key == 0){break;}

                }//f2?>
            </tbody>
        </table>
       
    <?php                
	   /*
        foreach ($data as $key => $value){
            echo "Key: ".$key[0]." Data: ".$value[0]."<br />";
        }*/
	
	break;	
	
	

	
	case 'daily_stock_valuation' :

	$report_header_array_set=array();
	if($is_centralise=='1'){
		$getStocklocations=mysql_query("SELECT GROUP_CONCAT(l.loc_id) AS f FROM `admin_manage_stock_location` l 
		WHERE l.master_username='$master_username'");
		$rowS=mysql_fetch_array($getStocklocations);
		$tocklocations=$rowS['f'];
		
		$key_query = "SELECT ".$centralise_group_name.$main_colum_name."l.location_name AS stores_location_name,p.`product_category`,s.`item_name`,s.`item_code`,s.`opening_stock`,s.`grn_qty`,s.`sold_qty`,s.`return_qty`,s.`closing_stock`,s.`selling_price`
,s.`total_sales` FROM `device_daily_stock_transactions` s,`admin_manage_centralized_products` p ,`admin_manage_stock_location` l,`admin_manage_centralized_device_group` GM,`admin_users_subscription` TM
WHERE s.`item_code`=p.`product_code` AND s.stores_location=l.loc_id WHERE s.`item_code`=p.`product_code` AND s.stores_location=l.loc_id AND p.`group_id`=GM.`group_id` AND p.`key_id`=TM.`key_id`
 AND s.stores_location IN ($tocklocations) AND p.`key_id` IN (".$MULTI_CENTRALISE_KEYS.") AND DATE(s.`transaction_date`)='$from_date_only'
ORDER BY s.`stores_location`,p.`product_category`,s.`item_name` ASC";

		$report_header_array_set[]="Stores Location,";
//set price column cell alignment right-> header array keys//
		$price_alignment_right_columns=array(9,10);
		$total_value_shows=array(4,5,6,7,8,10);
		}
		else {
		 $key_query = "SELECT ".$centralise_group_name.$main_colum_name."p.`product_category`,s.`item_name`,s.`item_code`,s.`opening_stock`,s.`grn_qty`,s.`sold_qty`,s.`return_qty`,s.`closing_stock`,s.`selling_price`
,s.`total_sales` FROM `device_daily_stock_transactions` s,`device_backup_product` p,`admin_users_subscription` TM WHERE s.`license_key`=p.`key` AND s.`item_code`=p.`product_code`
 AND s.`license_key` IN (".$MULTI_Terminals.") AND p.`key` IN (".$MULTI_Terminals.") AND DATE(s.`transaction_date`)='$from_date_only'
 AND p.`key`=TM.`key_id`
ORDER BY s.`license_key`,p.`product_category`,s.`item_name` ASC";
//set price column cell alignment right-> header array keys//
		$price_alignment_right_columns=array(8,9);
		$total_value_shows=array(3,4,5,6,7,9);
		}

		$query_results=mysql_query($key_query);	
		?>
		
		<table id="example_14" class="table table-striped table-bordered table-hover">
                <thead>
                                <tr>
                                    <?php if($is_centralise=='1'){ ?>
                                    <th> Group Name </th>
                                    <?php }  if($MULTI_TERMINALS_AVAILABLE =='1' ||  $is_centralise !='1'){?>
                                    <th> Terminal Name </th>
                                    <?php }if($is_centralise=='1'){ ?>
                                    <th> Stores Location </th>
                                    <?php } ?>
                                    
                                    <th> Product Category </th>
                                    <th> Product Name </th>
                                    <th> Product Code </th>
                                    <th> Opening Stock </th>
                                    <th> Total GRN </th>
                                    <th> Total Sold </th>
                                    <th> Total Return </th>
                                    <th> Closing Stock</th>
                                    <th> Selling Price(<?php echo $profile_currency; ?>) </th>
                                    <th> Total Sales(<?php echo $profile_currency; ?>) </th>
				                </tr>
                            </thead>
                            <tbody>
                               
                                <?php	
                                        $grnqty = 0;
                                        $soldqty = 0;
                                        $returnqty = 0;
                                        $closingstock = 0;
                                        $sellingprice = 0;
                                        $totalsales = 0;
        
                                        while($row=mysql_fetch_array($query_results)){
                                                    
                                        $stores_location_name = $row[stores_location_name];
                                        $product_category = $row[product_category];
                                        $item_name = $row[item_name];
                                        $item_code = $row[item_code];
                                        $opening_stock = $row[opening_stock];
                                        $grn_qty = $row[grn_qty];
                                        $sold_qty = $row[sold_qty];
                                        $return_qty = $row[return_qty];
                                        $closing_stock = $row[closing_stock];
                                        $selling_price = $row[selling_price];
                                        $total_sales = $row[total_sales];
                                        $group_name = $row[group_name];
                                        $terminal_name = $row[terminal_name];
                                        $cashRefundResults=mysql_query("SELECT   IFNULL(SUM(credit_note_price),0.00) AS credit_note_returns
                                        FROM `device_backup_credit_note` i,`device_backup_invoice` c
                                        WHERE i.`".$inv_master_key_column."` IN (".$MULTI_Terminals.")   AND c.`".$inv_master_key_column."` IN (".$MULTI_Terminals.") 
                                        AND i.`credit_note_invoice_number`=c.`invoice_main_number`
                                        AND i.`credit_note_item_code`=c.`invoice_itemcode`
                                         AND i.`credit_note_flag_delete`='0' 
                                        AND i.`credit_note_type`='CR'
                                        AND i.`credit_note_item_code`='$item_code'
                                        AND DATE(c.`invoice_date`) ='$from_date_only'");
                                        $rowR=mysql_fetch_array($cashRefundResults);	
                                        $cash_returns = $rowR[cash_returns];

                                        $total_sales =$total_sales - $cash_returns;
                                            
                                        echo '<tr>';
                                        if($is_centralise=='1'){
                                        echo '<td>'.$group_name.'</td>';
                                        }  if($MULTI_TERMINALS_AVAILABLE =='1' ||  $is_centralise !='1'){
                                        echo '<td>'.$terminal_name.'</td>';    
                                        }if($is_centralise=='1'){    
                                        echo '<td>'.$stores_location_name.'</td>';
                                        }
                                            
                                        $grnqty = floatval($grnqty)+floatval($grn_qty);
                                        $soldqty = floatval($soldqty)+floatval($sold_qty);
                                        $returnqty = floatval($returnqty)+floatval($return_qty);
                                        $closingstock = floatval($closingstock)+floatval($closing_stock);
                                        $sellingprice = floatval($sellingprice)+floatval($selling_price);
                                        $totalsales = floatval($totalsales)+floatval($total_sales);    

                                ?>       
                                        <td><?php echo $product_category; ?></td>    
                                        <td><?php echo $item_name; ?></td>                
                                        <td><?php echo $item_code; ?></td>      
                                        <td><?php echo $opening_stock; ?></td>    
                                        <td style="text-align:right"><?php echo number_format($grn_qty,2); ?></td>    
                                        <td style="text-align:right"><?php echo number_format($sold_qty,2); ?></td>    
                                        <td style="text-align:right"><?php echo number_format($return_qty,2); ?></td>    
                                        <td style="text-align:right"><?php echo number_format($closing_stock,2); ?></td>    
                                        <td style="text-align:right"><?php echo number_format($selling_price,2); ?></td>    
                                        <td style="text-align:right"><?php echo number_format($total_sales,2); ?></td> 
                                        </tr>
                                <?php        
                                    }
                                 ?>
                                </tbody>
                                <tfoot>
                                        <tr>  
                                        <?php if($is_centralise=='1'){ ?>
                                        <td></td>
                                    <?php }  if($MULTI_TERMINALS_AVAILABLE =='1' ||  $is_centralise !='1'){?> 
                                        <td></td>
                                        <?php } if($is_centralise=='1'){ ?>
                                        <td></td>
                                    <?php } ?>
                                        <td>Total</td>    
                                        <td></td>                
                                        <td></td>      
                                        <td></td>    
                                        <td style="text-align:right"><?php echo number_format($grnqty,2); ?></td>    
                                        <td style="text-align:right"><?php echo number_format($soldqty,2); ?></td>    
                                        <td style="text-align:right"><?php echo number_format($returnqty,2); ?></td>    
                                        <td style="text-align:right"><?php echo $closingstock; ?></td>    
                                        <td style="text-align:right"><?php echo number_format($sellingprice,2); ?></td>    
                                        <td style="text-align:right"><?php echo number_format($totalsales,2); ?></td> 
                                        </tr>
                                </tfoot>
                                       
                </table>
	
		
		
<?php

	
	
	break;
	case 'monthly_outlet' :
	
		//Excel Header Array//////////
		$report_header_array=array("Date","Total Sales(".$profile_currency.")","Total Stock Returns(".$profile_currency.")",
		"Total GRNs(".$profile_currency.")","Total Discounts(".$profile_currency.")");
		//set price column cell alignment right-> header array keys//
		$price_alignment_right_columns=array(1,2,3,4);
		$total_value_shows=array(1,2,3,4);
	
		//All date with zero required values ->2-D Array//
	    $data=date_range_array($from_date_only,$to_date_only,$profile_currency);;

	   
	   //find sales & discounts  -> update array//
	   $monthly_invoice_data=mysql_query("SELECT DATE(inv_data.invoice_date) AS invoice_date,
IFNULL(SUM(inv_data.invoice_amount),0.00) AS total_sale,
IFNULL(SUM(inv_data.full_discount),0.00) AS total_discount
 FROM 
(SELECT ".$centralise_group_name.$main_colum_name."DATE(invoice_date) AS invoice_date,invoice_total AS invoice_amount, 
(i.invoice_total_discount + i.invoice_full_discount ) AS full_discount FROM device_backup_invoice i".$main_terminal_table.$centralise_group_table."
WHERE i.`".$inv_master_key_column."` IN (".$MULTI_Terminals.")  AND i.`invoice_delete_flag`='0'
 ".$where."
AND ".$main_terminal_condition.$centralise_condition."
 DA 
BETWEEN '$from_date' AND '$to_date'  GROUP BY i.".$group_by.",i.`key`,i.invoice_main_number) AS inv_data
GROUP BY i.".$group_by.",inv_data.invoice_date");

        
		$data_count_invoice=mysql_num_rows($monthly_invoice_data);

		if($data_count_invoice>0){//i
		
			while($rowI=mysql_fetch_array($monthly_invoice_data)){//w
				
					$invoice_date=$rowI['invoice_date'];
					$total_sale=$rowI['total_sale'];
					$total_discount=$rowI['total_discount'];
					
					//get cash refund this invoice date//
					//cash refund
					$cashRefundResults=mysql_query("SELECT   IFNULL(SUM(credit_note_price),0.00) AS cash_returns
FROM `device_backup_credit_note` i,`device_backup_invoice` c
WHERE i.`".$inv_master_key_column."` IN (".$MULTI_Terminals.")  AND c.`".$inv_master_key_column."` IN (".$MULTI_Terminals.")
AND i.`credit_note_invoice_number`=c.`invoice_main_number`
AND i.`credit_note_item_code`=c.`invoice_itemcode`  AND i.`credit_note_flag_delete`='0' 
AND i.`credit_note_type`='CR'
AND DATE(c.`invoice_date`) ='$invoice_date'");
				$rowR=mysql_fetch_array($cashRefundResults);	
				$cash_returns = $rowR[cash_returns];
					
					
					
					
					//update array//
					$data[$invoice_date]["Total Sales(".$profile_currency.")"]=($total_sale - $cash_returns);
					$data[$invoice_date]["Total Discounts(".$profile_currency.")"]=$total_discount;
				
				
				}//w
					
			}
			
			
		//find GRN  -> update array//
		if($is_centralise=='1'){
			$stores_location=getStoresLocation($def_key);
			$stock_query="SELECT DATE(create_date) AS grn_date,SUM(grn_qty * unit_cost) AS total_grn_cost
FROM `admin_manage_centralized_stock` s 
WHERE s.`stores_location` IN ('$stores_location') AND DATE(create_date) BETWEEN '$from_date' AND '$to_date'
GROUP BY DATE(create_date)";
			}else{
			$stock_query="SELECT DATE(stock_datetime) AS grn_date,SUM(stock_purchased_qty * stock_cost) AS total_grn_cost
FROM device_backup_stock s WHERE s.`key` IN (".$MULTI_Terminals.") AND DATE(stock_datetime) BETWEEN '$from_date' AND '$to_date'
GROUP BY DATE(stock_datetime)";	
				
				}
		
		
		
	   $monthly_grn_data=mysql_query($stock_query);
		$data_count_grn=mysql_num_rows($monthly_grn_data);
		
		if($data_count_grn>0){//i
		
			while($rowG=mysql_fetch_array($monthly_grn_data)){//w
				
					$grn_date=$rowG['grn_date'];
					$total_grn_cost=$rowG['total_grn_cost'];
					
					//update array//
					$data[$grn_date]["Total GRNs(".$profile_currency.")"]=$total_grn_cost;
				
				
				}//w
					
			}//i
			
			
		/////find RETURN  -> update array//
		if($is_centralise=='1'){
		$return_query="SELECT DATE(return_date) AS return_date, SUM(return_cost * return_qty) AS return_cost
FROM `admin_manage_centralized_stock_return`
WHERE `stores_location` ='$stores_location' AND DATE(return_date) BETWEEN '$from_date' AND '$to_date'
GROUP BY DATE(return_date) ASC";
		}else{
		$return_query="SELECT DATE(return_date) AS return_date, SUM(return_unit_value * return_qty) AS return_cost
FROM device_backup_return
WHERE `key` IN (".$MULTI_Terminals.") AND DATE(return_date) BETWEEN '$from_date' AND '$to_date'
GROUP BY DATE(return_date) ASC";	
			
			}
			
			
	   $monthly_return_data=mysql_query($return_query);
		$data_count_return=mysql_num_rows($monthly_return_data);
		
		if($data_count_return>0){//i
		
			while($rowR=mysql_fetch_array($monthly_return_data)){//w
				
					$return_date=$rowR['return_date'];
					$return_cost=$rowR['return_cost'];
					
					//update array//
					$data[$return_date]["Total Stock Returns(".$profile_currency.")"]=$return_cost;
				
				
				}//w
					
			}//i
		?>
        <table id="example_15" class="table table-striped table-bordered table-hover">
            <thead>
        <?php
        //var_dump($data);
        foreach ($data as $key => $value){
             //echo "Key: ".$key." Data: ".$value."<br />";
            
                
                   echo '</tr>';
                   foreach ( $value as $key => $value )
                        {?>
                            <th> <?php echo $key; ?> </th>
                        <?php       
                        }
                    echo '</tr>';
                    if($key == 0){break;}
                    
                
        }?>
            </thead>
            <tbody>
         <?php
        //var_dump($data);
        foreach ($data as $key => $value){
             //echo "Key: ".$key." Data: ".$value."<br />";
            
                
                   echo '</tr>';
                   foreach ( $value as $key => $value )
                        {?>
                            <td> <?php echo $value; ?> </td>
                        <?php       
                        }
                    echo '</tr>';
                    
                    
                
        }?>        
            </tbody>
            </table>
        <?php        
	   
	   	if($data_count_return == 0 && $data_count_grn ==0 && $data_count_invoice ==0){
			//empty array//
			unset($data);
			//$data =array();
			
			}
	   
	   
	
		$filename = "SPL_Monthly_Outlet_Summary _". $current_date . ".xls";
		$report_name='Monthly Outlet Summary Report';
		$worksheet_name='Monthly Outlet Summary';

	'.local("break;
	

        
	case").' 'outlets_itemwise_sales' :
	
        $product_category=$_GET['product_category'];
		
		
		//Get POS list - Attached to the user//
		if($is_centralise=='1'){
			$device_list_query="SELECT group_id AS f FROM `admin_manage_centralized_device_group` g 
			WHERE g.`group_id`IN (".$MULTI_Terminals.")";			
			}else{
			$device_list_query="SELECT key_id  AS f FROM `admin_users_subscription` s 
		WHERE s.`key_id`IN (".$MULTI_Terminals.")";
				
				}
		
		
		
		
		//Get POS list - Attached to the user//
		$customer_list=mysql_query($device_list_query);
		
		$pos_array=array();
		$i=0;
		$all_keys='';
		while($rowk=mysql_fetch_array($customer_list)){//w1
			$pos_key_id=strtoupper($rowk[f]);
			$pos_array[$i]=$pos_key_id;
			
			$all_keys .="'".$pos_key_id."',";
			$i++;
			
			}//w1
		
		
		$all_keys =rtrim($all_keys,",");
		
			
		 /********************************** Headers ***************************************************/	
		 
		 //get all category list//	
		if($is_centralise=='1'){
			$column_value=" IN (".$MULTI_Terminals.")";		
			
			}else{

			$column_value=" IN (".$all_keys.")";		
				
				}	 
		 
		//get all category list//
		$getCategories=mysql_query("SELECT DISTINCT `product_code`,product_name  FROM `".$product_table."` c 
		WHERE c.`".$product_master_key_column."` ".$column_value." AND product_category ='$product_category' ORDER BY product_name ASC");
		
		$report_header_array[0]="Outlet Name";
		$k=1;
		$price_alignment_right_columns=array();
		$total_value_shows=array();
		$product_code_array=array();
		$product_names_array=array();
		
		while($rowC=mysql_fetch_array($getCategories)){//w2
		
		$product_code=$rowC['product_code'];
		$report_header_array[$k]=$product_code;
		$product_code_array[$k-1]=$product_code;
		
		$product_names_array[$k]=$rowC['product_name'];
		
		$total_value_shows[]=$k;
			   
		$k++;		
		}//w2
		

		/*********************************************************************************************************/
		
		
		$data=array();
		//create multidimesional array ->  all outlet with codes & its default value 0//
		foreach($pos_array as $key=>$pos_key){//f1
		
		$pos_device_name=deviceName($pos_key,$user_name,$is_centralise);
		
		$code_array=array("Outlet Name"=>$pos_device_name);
		
		foreach($product_names_array as $key_product_code=>$value_product_name){//f2
		
		$code_array[$key_product_code]=0;
		}//f2
		
		
		$data[$pos_key]=$code_array;
		
		}//f1
		
		//".$main_terminal_table.$centralise_group_table."
		//find Data  -> update array//
	    $itemwise_sales_query="SELECT s.`key`,s.invoice_itemcode,SUM(s.invoice_qty) AS inv_qty
FROM device_backup_invoice s".$main_terminal_table.$centralise_group_table."
WHERE  s.`".$inv_master_key_column."` IN (".$MULTI_Terminals.")  
AND invoice_itemtype_number = 1
AND invoice_delete_flag='0'
 ".$where."
AND ".$main_terminal_condition_.$centralise_condition_."
DATE(invoice_date) BETWEEN '$from_date' AND '$to_date'
GROUP BY i.".$group_by.",s.`".$inv_master_key_column."`,invoice_itemcode";

		$itemwise_sales_data=mysql_query($itemwise_sales_query);
		$data_count_sales=mysql_num_rows($itemwise_sales_data);
		
		if($data_count_sales>0){//i
		
		
		
			while($rowR=mysql_fetch_array($itemwise_sales_data)){//w
				
					$outlet_key=strtoupper($rowR['key']);
					$invoice_itemcode=$rowR['invoice_itemcode'];
					$total_inv_qty=$rowR['inv_qty'];
					
					//update array//
					$data[$outlet_key][$invoice_itemcode]=$total_inv_qty;
				
				
				}//w
				
				
				
				
			//reduce credit note and cash refund item qty////
				$itemwise_credit_note_query="SELECT   c.`key`,i.`credit_note_item_code`,IFNULL(SUM(credit_note_qty),0) AS credit_note_returns
FROM `device_backup_credit_note` i,`device_backup_invoice` c ".$main_terminal_table.$centralise_group_table."
WHERE i.`".$inv_master_key_column."` IN (".$MULTI_Terminals.") AND c.`".$inv_master_key_column."` IN (".$MULTI_Terminals.")
AND i.`credit_note_invoice_number`=c.`invoice_main_number` 
AND i.`credit_note_item_code`=c.`invoice_itemcode` AND i.`credit_note_flag_delete`='0' 
".$where."
         AND ".$main_terminal_condition.$centralise_condition."
DATE(c.`invoice_date`) BETWEEN '$from_date' AND '$to_date'
GROUP BY i.".$group_by.",i.`".$inv_master_key_column."`,i.`credit_note_item_code`";

		$itemwise_credit_note_data=mysql_query($itemwise_credit_note_query);
		$data_count_credit_note=mysql_num_rows($itemwise_credit_note_data);
		
				if($data_count_credit_note>0){//c
					
					while($rowR=mysql_fetch_array($itemwise_credit_note_data)){//w
						
						$outlet_key=strtoupper($rowR['key']);
					    $credit_note_item_code=$rowR['credit_note_item_code'];
					    $credit_note_returns=$rowR['credit_note_returns'];
						
						//Array exisiting value//
						$array_item_value=$data[$outlet_key][$credit_note_item_code];
					
						
						//update array//
					    $data[$outlet_key][$credit_note_item_code]=$array_item_value - $credit_note_returns;
						
						
						
						}//w
					
					
					
					}//c
		
				
				
				
	
            ?>
         <table id="example_16" class="table table-striped table-bordered table-hover">
            <thead>
             
                <?php
                    //print_r($product_names_array);
            //foreach($report_header_array as $key=>$dataArray){//f2
                    $outlet_name='Outlet Name';

                    '.local("echo").'   '<tr>';
                ?>
                    <th> <?php echo $outlet_name; ?> </th>
             
                <?php  
        
                    $array1 = $report_header_array;
                    $array2 = array( $outlet_name);
                    $result = array_diff($array1, $array2);

                   foreach ( $product_names_array as $key => $value )
                        {?>
                            <th> <?php echo $value; ?> </th>
                        <?php       
                        }
                    echo '</tr>';
                    //if($key == 0){break;}

            //}//f2?>
            </thead>
            <tbody>
               <?php
                foreach($data as $key=>$dataArray){//f2

                        echo   '<tr>';
                    $outlet_name=$dataArray['Outlet Name'];
                    $array3 = $dataArray;
                    $result2 = array_diff($array3, $array2);
                    ?>
                    
                        <td> <?php echo $outlet_name; ?> </td>
                    <?php       foreach ( $result as $key => $value )
                            {?>
                                <td> <?php  if($result2[$value]==''){ echo 0;}else{ echo $result2[$value];} ?> </td>
                            <?php       
                            }
                        echo '</tr>';
                        //if($key == 0){break;}

                }//f2?>
            </tbody>
        </table>
       
    <?php 
					//echo $arr;
			}else{//i
		
	
			//empty array//
			unset($data);
			//$data =array();
			
			}//i
	
	
		
		$filename = "SPL_Outlets_Productwise_Sales_Summary _". $current_date . ".xls";
		$report_name='Outlets Productwise Sales Report';
	    $worksheet_name='Outlets Productwise Sales';
		$device_name='ALL OUTLETS';
		
	
	
	'.local("break;
        
        
        
        case").' 'stock_balance' :
		
			
			$key_query = "SELECT TM.`device_name` AS terminal_name,product_category, product_sub_category, A.`product_code`, `product_name`, SUM(`grn_qty`) AS grn_qty, SUM(`sold_qty`) AS sold_qty, SUM(`return_qty`) AS return_qty, SUM(`transfer_in`) AS transfer_in, SUM(`transfer_out`) AS transfer_out, openingbalance FROM `device_backup_stock_balance` A, 
            (SELECT product_code, `opening_balance` AS openingbalance FROM `device_backup_stock_balance` WHERE `key` IN (".$MULTI_Terminals.") AND DATE(transaction_date) = '$from_date_only') B,
            (SELECT product_code, `closing_balance` AS closing_balance FROM `device_backup_stock_balance` WHERE `key` IN (".$MULTI_Terminals.") AND DATE(transaction_date) = '$to_date_only') C,
            `admin_users_subscription` TM
            WHERE A.`key`=TM.`key_id` AND A.`key` IN (".$MULTI_Terminals.") AND DATE(transaction_date) BETWEEN '$from_date'
            AND '$to_date' AND A.product_code=B.product_code AND A.product_code=C.product_code GROUP BY A.product_code,openingbalance ORDER BY product_category,product_sub_category ASC";
		                $query_results=mysql_query($key_query);			
?>
<table id="example_18" class="table table-striped table-bordered table-hover">
                <thead>
                                <tr><?php  if($MULTI_TERMINALS_AVAILABLE =='1' ||  $is_centralise !='1'){ ?>
                                    <th> Terminal Name </th>
                                    <?php } ?>
                                    <th> Product Category </th>
                                    <th> Product Sub Category </th>
                                    <th> Product Code </th>
                                    <th> Product Name </th>
                                    <th> GRN  </th>
                                    <th> Sold </th>
                                    <th> Return</th>
                                    <th> Transfer In</th> 
                                    <th> Transfer Out</th>
                                    <th> Open Stock</th>
                                    <th> Closing Stock</th>
				                </tr>
                            </thead>
                            <tbody>
                               
                                <?php	
                                        while($row=mysql_fetch_array($query_results)){
                                                    
                                        $terminal_name = $row[terminal_name];
                                        $product_category = $row[product_category];
                                        $product_sub_category = $row[product_sub_category];
                                        $product_code = $row[product_code];
                                        $product_name = $row[product_name];
                                        $grn_qty = $row[grn_qty];
                                        $sold_qty = $row[sold_qty];
                                        $return_qty = $row[return_qty];
                                        $transfer_in = $row[transfer_in];
                                        $transfer_out = $row[transfer_out];
                                        $opening_balance = $row[opening_balance];
                                        $device_backup_stock_balance = $row[device_backup_stock_balance];
                                        

                                ?>      <tr>
                                        <?php  if($MULTI_TERMINALS_AVAILABLE =='1' ||  $is_centralise !='1'){ ?>
                                        <td><?php echo $terminal_name; ?></td> 
                                        <?php } ?>
                                        <td><?php echo $product_category; ?></td>    
                                        <td><?php echo $product_sub_category; ?></td>                
                                        <td><?php echo $product_code; ?></td>   
                                        <td><?php echo $product_name; ?></td>    
                                        <td><?php echo $grn_qty; ?></td>    
                                        <td><?php echo $sold_qty; ?></td>    
                                        <td><?php echo $return_qty; ?></td>    
                                        <td><?php echo $transfer_in; ?></td>    
                                        <td><?php echo $transfer_out; ?></td>    
                                        <td><?php echo $opening_balance; ?></td>    
                                        <td><?php echo $device_backup_stock_balance; ?></td>    
                                        </tr>  
                                <?php        
                                    }
                                 ?>
                                </tbody>
                </table>


            
<?php
			break;	
	case 'sales_commssion' :
	
        if(!empty($_GET['commision_value'])){   
		  $commision_value = $_GET['commision_value'];
        }else{
          $commision_value = 0;    
        }
		
		 $key_query = "SELECT ".$centralise_group_name.$main_colum_name."e.`emp_name`,e.emp_id,t.`emp_type_name`,e.`emp_mobile`,IFNULL(SUM(`invoice_price`),0.00) AS total_service_charges,'$commision_value' AS commison_rate,(IFNULL(SUM(`invoice_price`),0.00) *$commision_value)/100 AS commission 
		 FROM device_backup_invoice i,`".$employees_table."` e,`".$emp_type_table."` t".$main_terminal_table.$centralise_group_table ." WHERE 
         ".$main_terminal_condition.$centralise_condition." i.`".$inv_master_key_column."` IN (".$MULTI_Terminals.")  
                ".$where1." 
         
		 AND e.`".$product_master_key_column."` IN ('$product_master_key_value')
AND t.`".$product_master_key_column."` IN ('$product_master_key_value') AND i.`emp_id`=e.`emp_id` AND e.`emp_type_id`=t.`emp_type_id` AND i.`invoice_itemtype_number`=2 AND LOWER(i.`invoice_itemcode`) = 'service charge'
AND DATE(i.invoice_date) BETWEEN '$from_date' AND '$to_date'
GROUP BY i.".$group_by.",i.`emp_id`";

        
$report_header_array=array("Employee Name","Employee ID","Employee Type","Employee Mobile","Total Service Charges(".$profile_currency.")","Commission Rate(%)","Commission(".$profile_currency.")");
//set price column cell alignment right-> header array keys//
$price_alignment_right_columns=array(4,6);
$total_value_shows=array(4,6);

		$query_results=mysql_query($key_query);
		?>

    <table id="example_20" class="table table-striped table-bordered table-hover">
                <thead>
                    <tr>            <?php if($is_centralise=='1'){ ?>
                                    <th> Group Name </th>
                                    <?php }  if($MULTI_TERMINALS_AVAILABLE =='1' ||  $is_centralise !='1'){?>
                                    <th> Terminal Name </th>
                                    <?php } ?>
                                    <th> Employee Name </th>
                                    <th> Employee ID </th>
                                    <th> Employee Type </th>
                                    <th> Employee Mobile </th>
                                    <th> Total Service Charges(<?php echo $profile_currency;?>)</th>
                                    <th> Commission Rate(%) </th>
                                    <th> Commission(<?php echo $profile_currency;?>)</th>
				                </tr>
                            </thead>
                            <tbody>
                               
                                <?php	
                                        while($row=mysql_fetch_array($query_results)){
                                        $terminal_name = $row[terminal_name];
                                        $product_category = $row[product_category];            
                                        $emp_name = $row[emp_name];
                                        $emp_id = $row[emp_id];
                                        $emp_type_name = $row[emp_type_name];
                                        $emp_mobile = $row[emp_mobile];
                                        $total_service_charges = $row[total_service_charges];
                                        $commison_rate = $row[commison_rate];
                                        $commission = $row[commission];
			
                                        

                                ?>      <tr><?php if($is_centralise=='1'){ ?>
                                        <td><?php echo $group_name; ?></td>
                                            <?php }  if($MULTI_TERMINALS_AVAILABLE =='1' ||  $is_centralise !='1'){?>
                                        <td><?php echo $terminal_name; ?></td> 
                                        <?php } ?>
                                        <td><?php echo $emp_name; ?></td>    
                                        <td><?php echo $emp_id; ?></td>                
                                        <td><?php echo $emp_type_name; ?></td>   
                                        <td><?php echo $emp_mobile; ?></td>    
                                        <td><?php echo $total_service_charges; ?></td>    
                                        <td><?php echo $commison_rate; ?></td>      
                                        <td><?php echo $commission; ?></td>      
                                        </tr>      
                                <?php        
                                    }
                                 ?>
                                </tbody>
                </table>
        <?php

		break;
			
		
	case 'emp_wise_sales' :
	
			if($is_centralise=='1'){
                $col = "terminal_name, group_name,";
				$query_condition_1='';
				}else{
					$query_condition_1='i.`key`=e.`key` AND';
					$col = "terminal_name,";
					}

		
		 $key_query = "SELECT ".$col."q.emp_id,q.emp_name,q.emp_type_name,q.emp_mobile,IFNULL(SUM(q.invoice_total),0.00) AS total_sales FROM (
         
         SELECT
         
         ".$centralise_group_name.$main_colum_name."i.`emp_id`,e.`emp_name`,t.`emp_type_name`,e.`emp_mobile`,i.`invoice_main_number`,(i.`invoice_total`) 
FROM device_backup_invoice i,`device_backup_employees` e,`device_backup_employee_types` t".$main_terminal_table.$centralise_group_table ."
 WHERE ".$main_terminal_condition.$centralise_condition.$query_condition_1." i.`emp_id`=e.`emp_id` AND  e.`emp_type_id`=t.`emp_type_id`
AND i.`invoice_itemtype_number`=1 AND i.`".$inv_master_key_column."` IN (".$MULTI_Terminals.")  AND e.`".$product_master_key_column."` IN (".$MULTI_Terminals.") AND t.`".$product_master_key_column."` IN (".$MULTI_Terminals.")
".$where1." 
AND DATE(i.`invoice_date`) BETWEEN '$from_date' AND '$to_date'
GROUP BY i.".$group_by.",i.`emp_id`,i.`invoice_main_number`) AS q
GROUP BY q.".$group_by.",q.emp_id";

//Excel Header Array//////////
$report_header_array=array("Employee Name","Employee ID","Employee Type","Employee Mobile","Total Sales(".$profile_currency.")","Total Service Charges(".$profile_currency.")");
//set price column cell alignment right-> header array keys//
$price_alignment_right_columns=array(4,5);
$total_value_shows=array(4,5);

		$query_results=mysql_query($key_query);
			
			?>

                        <table id="example_21" class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr><?php if($is_centralise=='1'){ ?>
                                            <th> Group Name </th>
                                            <?php }  if($MULTI_TERMINALS_AVAILABLE =='1' ||  $is_centralise !='1'){?>
                                            <th> Terminal Name </th>
                                            <?php } ?>
                                    <th> Employee Name </th>
                                    <th> Employee ID </th>
                                    <th> Employee Type </th>
                                    <th> Employee Mobile </th>
                                    <th> Total Sales(<?php echo $profile_currency;?>)</th>
                                    <th> Total Service Charges(<?php echo $profile_currency;?>)</th>
				                </tr>
                            </thead>
                            <tbody>
                               
                                <?php	
                                        while($row=mysql_fetch_array($query_results)){
                                                    
                                        $emp_name = $row[emp_name];
                                        $emp_id = $row[emp_id];
                                        $emp_type_name = $row[emp_type_name];
                                        $emp_mobile = $row[emp_mobile];
                                        $total_sales = $row[total_sales];
			                             $group_name = $row[group_name];
                                        $terminal_name = $row[terminal_name];
                                        $get_full_service_charges=mysql_query("SELECT IFNULL(SUM(`invoice_price`),0.00) AS f
                                        FROM `device_backup_invoice` i WHERE i.`".$inv_master_key_column."` IN ('$def_key') AND i.`emp_id` ='$emp_id'
                                        AND i.`invoice_itemtype_number`=2 AND LOWER(i.`invoice_itemcode`) = 'service charge'
                                        AND DATE(i.invoice_date) BETWEEN '$from_date' AND '$to_date'");
                                                    $row1=mysql_fetch_array($get_full_service_charges);

                                ?>      <tr>
                                        <?php if($is_centralise=='1'){ ?>
                                        <td><?php echo $group_name; ?></td>
                                            <?php }  if($MULTI_TERMINALS_AVAILABLE =='1' ||  $is_centralise !='1'){?>
                                        <td><?php echo $terminal_name; ?></td> 
                                        <?php } ?>
                                        <td><?php echo $emp_name; ?></td>    
                                        <td><?php echo $emp_id; ?></td>                
                                        <td><?php echo $emp_type_name; ?></td>   
                                        <td><?php echo $emp_mobile; ?></td>       
                                        <td><?php echo $total_sales; ?></td>      
                                        <td><?php echo $row1['f']; ?></td>
                                </tr>
                                <?php        
                                    }
                                 ?>
                            </tbody>
                        </table>
        <?php

		break;
		
		
		case 'staff_cost' :
		
			
						$key_query = "SELECT ".$centralise_group_name.$main_colum_name." c.`customer_name`,p.product_name,p.product_code,p.product_category,
SUM(s.`invoice_item_cost` * s.`invoice_qty`) AS total_cost, s.invoice_price AS selling_price
FROM device_backup_invoice s, ".$product_table." p,".$customer_table." c".$main_terminal_table.$centralise_group_table."
WHERE  s.`".$inv_master_key_column."` IN (".$MULTI_Terminals.") AND p.`".$product_master_key_column."` IN (".$MULTI_Terminals.") AND c.`".$product_master_key_column."` IN (".$MULTI_Terminals.")
AND
s.invoice_itemcode = p.product_code
AND s.`invoice_customer_id`=c.`customer_id`
AND invoice_itemtype_number = 1
AND invoice_delete_flag='0'
 ".$where."
AND ".$main_terminal_condition_.$centralise_condition_."
s.`invoice_payment_method`='Staff'
AND DATE(invoice_date) BETWEEN '$from_date' AND '$to_date'
GROUP BY s.".$group_by.",s.`invoice_customer_id`,p.product_code
ORDER BY s.".$group_by.",c.`customer_name` ASC";
        
$query_results=mysql_query($key_query);
?>
                            <table id="example_19" class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr><?php if($is_centralise=='1'){ ?>
                                            <th> Group Name </th>
                                            <?php }  if($MULTI_TERMINALS_AVAILABLE =='1' ||  $is_centralise !='1'){?>
                                            <th> Terminal Name </th>
                                            <?php } ?>
                                    <th> Staff Member Name </th>
                                    <th> Product Category </th>
                                    <th> Product Name </th>
                                    <th> Product Code </th>
                                    <th> Product Price (<?php echo $profile_currency;?>)</th>
                                    <th> Sold </th>
                                    <th> Total Cost(<?php echo $profile_currency;?>)</th>
				                </tr>
                            </thead>
                            <tbody>
                               
                                <?php	
                                        while($row=mysql_fetch_array($query_results)){
                                                    
                                        $group_name = $row[group_name];
                                        $terminal_name = $row[terminal_name]; 
                                        $customer_name = $row[customer_name];
                                        $product_category = $row[product_category];
                                        $product_code = $row[product_code];
                                        $product_name = $row[product_name];
                                        $qty = $row[qty];
                                        $total_cost = number_format($row[total_cost],2,'.',',');
                                        $selling_price = number_format($row[selling_price],2,'.',',');
                                        

                                ?>        <tr><?php if($is_centralise=='1'){ ?>
                                        <td><?php echo $group_name; ?></td>
                                            <?php }  if($MULTI_TERMINALS_AVAILABLE =='1' ||  $is_centralise !='1'){?>
                                        <td><?php echo $terminal_name; ?></td>
                                            <?php } ?>
                                        <td><?php echo $customer_name; ?></td>    
                                        <td><?php echo $product_category; ?></td>                
                                        <td><?php echo $product_code; ?></td>   
                                        <td><?php echo $product_name; ?></td>    
                                        <td><?php echo $selling_price; ?></td>    
                                        <td><?php echo $qty; ?></td>    
                                        <td><?php echo $total_cost; ?></td>      
                                        </tr>
                                <?php        
                                    }
                                 ?>
                                </tbody>
                            </table>



<?php		
			break;	
		case 'kot_wise' :
		
			
        $key_query = "SELECT ".$centralise_group_name.$main_colum_name."`kot_number`,`item_code`,p.`product_name`,`item_note`,`kot_qty`,
`table_code`,`kot_description`, 
DATE_FORMAT(`kot_start_date_time`, '%Y-%m-%d %h:%i %p') AS kot_start_date_time,
DATE_FORMAT(`kot_end_date_time`, '%Y-%m-%d %h:%i %p') AS kot_end_date_time,`cashier`,
`invoice_main_number` 
FROM `device_backup_invoice_kot` i, `device_backup_product` p ".$main_terminal_table.$centralise_group_table."
WHERE i.`item_code`=p.`product_code` AND ".$main_terminal_condition.$centralise_condition."
i.`key`=p.key AND  i.`".$inv_master_key_column."` IN (".$MULTI_Terminals.")
AND DATE(i.`kot_start_date_time`) BETWEEN  '$from_date' AND '$to_date'";
        
 
        $query_results=mysql_query($key_query);	
        
?>
	<table id="example_23" class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr><?php if($is_centralise=='1'){ ?>
                                            <th> Group Name </th>
                                            <?php }  if($MULTI_TERMINALS_AVAILABLE =='1' ||  $is_centralise !='1'){?>
                                            <th> Terminal Name </th>
                                            <?php } ?>
                                    <th> KOT Number </th>
                                    <th> Product Code </th>
                                    <th> Product Name </th>
                                    <th> KOT Qty </th>
                                    <th> KOT Note </th>
                                    <th> Table </th>
                                    <th> KOT Description </th>
                                    <th> Start Time </th>
                                    <th> End Time </th>
                                    <th> Invoice Number </th>
                                    <th> Cashier</th>
				                </tr>
                            </thead>
                            <tbody>
                               
                                <?php	
                                        while($row=mysql_fetch_array($query_results)){
                                            
                                        $kot_number = $row[kot_number];
                                        $product_code = $row[item_code];
                                        $product_name = $row[product_name];
                                        $item_note = $row[item_note];
                                        $kot_qty = $row[kot_qty];
                                        $table_code = $row[table_code];
                                        $kot_description = $row[kot_description];
                                        $kot_start_date_time = $row[kot_start_date_time];
                                        $kot_end_date_time = $row[kot_end_date_time];
                                        $cashier = $row[cashier];
                                        $invoice_main_number = $row[invoice_main_number];
                                        $group_name = $row[group_name];
                                        $terminal_name = $row[terminal_name];

                                ?>        <tr><?php if($is_centralise=='1'){ ?>
                                        <td><?php echo $group_name; ?></td>
                                            <?php }  if($MULTI_TERMINALS_AVAILABLE =='1' ||  $is_centralise !='1'){?>
                                        <td><?php echo $terminal_name; ?></td>
                                            <?php } ?>
                                        <td><?php echo $kot_number; ?></td>    
                                        <td><?php echo $product_code; ?></td>   
                                        <td><?php echo $product_name; ?></td>    
                                        <td><?php echo $kot_qty; ?></td>                
                                        <td><?php echo $item_note; ?></td>                
                                        <td><?php echo $table_code; ?></td>    
                                        <td><?php echo $kot_description; ?></td>    
                                        <td><?php echo $kot_start_date_time; ?></td>      
                                        <td><?php echo $kot_end_date_time; ?></td>      
                                        <td><?php echo $invoice_main_number; ?></td>      
                                        <td><?php echo $cashier; ?></td>      
                                        </tr>
                                <?php        
                                    }
                                 ?>
                                </tbody>
                            </table>



<?php		
			break;			
		
		
}

exit();

?>

