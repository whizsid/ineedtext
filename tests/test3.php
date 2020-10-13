<html>


<script>
	
    function validateEmail(email) {
        var re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
        return re.test(email);
    }

    function selectOnclick() {
        document.getElementById('select_event').style.display = 'none';
    }
    function selectOnclick1() {
        document.getElementById('select_event1').style.display = 'none';
    }
</script>
<!--Datepicker-->
<link href="css/datepicker.css" rel="stylesheet">
<!--DatePicker-->
<script src="js/bootstrap-datepicker.js"></script>
<script type="text/javascript" charset="utf-8">
    $('.inputCustomdt').datepicker({
        format: "yyyy-mm-dd",
        weekStart: 1,
        todayBtn: "linked",
        orientation: "bottom auto",
        keyboardNavigation: false,
        forceParse: false
    });

    $('.inputCustomdt').datepicker()
        .on('changeDate', function(e) {
            $(this).datepicker('hide');
        })

</script>
</script>

<!--Datepicker-->
<link href="css/datepicker.css" rel="stylesheet">
<!--DatePicker-->
<script src="js/bootstrap-datepicker.js"></script>

Hello 2

<script>
	console.log("Hello 3");

</script>
</html>
<!--
