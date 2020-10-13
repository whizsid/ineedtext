<script>
	$(document).ready(function() {
            var str = "";
            $('#MULTI_Terminals_PW').find('option:selected').each(function() {
                str += $( this ).val() + "','";
            });
            MULTI_TERMINALS_AVAILABLE = str;
            fromDay = $('#fromDay').val();
            toDay = $('#toDay').val();
            fromTime = $('#fromTime').val();
            toTime = $('#toTime').val();



            dataTable1 = $('#loc_manage_table1').DataTable( {
                dom: "<'row'<'col-sm-12 col-md-3'l><'col-sm-12 col-md-2'B><'col-sm-12 col-md-6'f><'col-sm-12 col-md-1 hidden-xs'>>tp",
                "lengthMenu": [[100, 250, 500, 1000, 1000000000], [100, 250, 500, 1000, "All"]],
                "processing": true,
                "serverSide": true,
                buttons: [
                    {extend: 'csv',
                        exportOptions: {
                            columns: ':visible'
                        }, footer: true ,title: 'Product Wise Sales',
                        text:'Export'},
                    {extend: 'pdf',
                        orientation: 'landscape',
                        pageSize: 'LEGAL',
                        footer: true ,title:'Product Wise Sales'},
                ],
                "ajax":{
                    url :"ajax/rcharts-grid-data<?php echo $php_extension;?>?DB_QUERY_PASS_ID_VALUES="+MULTI_TERMINALS_AVAILABLE+"&fromDay="+fromDay+"&toDay="+toDay+"&fromTime="+fromTime+"&toTime="+toTime+"&MULTI_TERMINALS_AVAILABLE=<?php echo $MULTI_TERMINALS_AVAILABLE;?>", // json datasource
                    type: "post",  // method  , by default get
                    error: function(){  // error handling
                        $(".loc_manage_table1-error").html("");
                        //$("#loc_manage_table").append('<tbody class="employee-grid-error"><tr><th colspan="11">No data found in the server</th></tr></tbody>');
                        $("#loc_manage_table1_processing").css("display","none");

                    }
                }

            });

            var count = 1;
            $( "#MULTI_Terminals_PW").change(function() {
                load();
                redloaTable();
                //loadTable();

            });
            $( "#cus").change(function() {
                load();
                redloaTable();
                //loadTable();

            });
        });

        function redloaTable(){

            loadTable();

        }
        function getDate(){


            load();
        }

        function load(){
            $('.linear-activity').show();
            var str = "";
            $('#MULTI_Terminals_PW').find('option:selected').each(function() {
                str += $( this ).val() + "','";
            });

            var data = '';
            visitorData(data);

        }
        getDate();
        setTimeout(function(){ $('.linear-activity').hide(); }, 1000);
        $(document).ready(function() {
            $('#<?php echo $idNo;?>').show();
            $('#hd_<?php echo $idNo;?>').show();


            <?php if($initial_page_loading=='1'){

            if($_GET['id'] == 27 ){ ?>

            var from_val='<?php echo $db_class->getValueAsf("SELECT DATE_SUB('$formated_date',INTERVAL 30 DAY) AS f ");?>';
            <?php }else{ ?>
            var from_val='<?php echo $formated_date;?>';
            <?php }
            ?>
            //console.log('loading');


            var to_val='<?php echo $formated_date;?>';

            <?php }else{ ?>
            //console.log('submit');
            var from_val='<?php echo $from_date_only;?>';
            var to_val='<?php echo $to_date_only;?>';

            //deselect all selected values//
            $("#MULTI_Terminals_<?php echo $idNo;?> option:selected").removeAttr("selected");


            <?php foreach($selected_terminal_list as $selected_terminal_key){ ?>
            $('#MULTI_Terminals_<?php echo $idNo;?> option[value=<?php echo $selected_terminal_key;?>]').attr('selected', true);
            <?php } ?>

            //reload
            $('#MULTI_Terminals_<?php echo $idNo;?>').parent().removeClass("input-prepend input-append");
            $('#MULTI_Terminals_<?php echo $idNo;?>').multipleSelect();
            $("input:checkbox:not(.hide_checkbox)").after("<label style=\'display: inline-block;\'></label>");


            <?php if($idNo=='5'){ ?>

            //deselect all selected values//
            $("#cus option:selected").removeAttr("selected");


            <?php foreach($selected_customer_list as $selected_customer_id){ ?>
            $('#cus option[value=<?php echo $selected_customer_id;?>]').attr('selected', true);
            <?php } ?>

            //reload
            $('#cus').parent().removeClass("input-prepend input-append");
            $('#cus').multipleSelect();
            $("input:checkbox:not(.hide_checkbox)").after("<label style=\'display: inline-block;\'></label>");


            <?php } ?>


            <?php } ?>
            //console.log(from_val)

            //var to_date = formatDate();
            $('#from_<?php echo $_GET['id'];?>').val(from_val);
            $('#to_<?php echo $_GET['id'];?>').val(to_val);


            //$('#submit_<?php //echo $_GET['id'];?>').click();
            /*
  $('#menu').accordion({
      collapsible :true,
      active: 0
  });
$(".ui-accordion-content").css({"height":"550"});
});
$("h3").click(function(){
$(".ui-accordion-content").css({"height":"550"});
*/
        });
    </script>
