<!DOCTYPE html>
<html>
	<head>
		<?php include_once("head.php"); ?>
		<?php include_once("files/analytics.php"); ?>
	</head>
<body onload="<?php if (isset($_GET['server'])){echo 'OpenInNewTab();';}else{echo '';}?>">

<div class="row" style="margin-top:80px;margin-left:0px;margin-right:0px;">
  <div class="col-xs-1 col-md-4"></div>
  <div class="col-xs-10 col-md-4">
  	<img src="files/logo.png" width="100%"><br><br>
    <div class="input-group">
    	<div class="input-group-btn">
	        <button style="min-width:74px;" type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown"><span data-bind="label" id="server"><?php if (isset($_GET['server'])){echo $_GET['server'];}else{echo 'Server';}?></span></button>
	        <ul class="dropdown-menu dropdown-menu-right" role="menu" style="min-width:0px !important;">
	        <li><a href="#">NA</a></li>
	        <li><a href="#">EUW</a></li>
	        <li class="divider"></li>
	        <li><a href="#">EUNE</a></li>
	        <li><a href="#">RU</a></li>
	        <li><a href="#">TR</a></li>
	        <li><a href="#">LAN</a></li>
	        <li><a href="#">LAS</a></li>
	        <li><a href="#">BR</a></li>
	        <li><a href="#">OCE</a></li>
	        <li><a href="#">KR</a></li>
	        <li><a href="#">JP</a></li>
	        <!-- <li class="divider"></li>
	        <li><a href="#">Separated link</a></li> -->
	        </ul>
	    </div><!-- /btn-group -->
	    <form action="/stats" onsubmit="OpenInNewTab();return false;"><input type="text" class="form-control" id="summoner" placeholder="Summoner name" value="<?php if (isset($_GET['server'])){echo $_GET['summoner'];}else{echo '';}?>"></form>
	    <span class="input-group-btn" style="height:34px !important;">
	        <button style="height:34px !important;" class="btn btn-default" type="button" onclick="OpenInNewTab();" id="submit"><span><i class="fa fa-chevron-right" style="min-height:17px;margin-top:3px;"></i></span></button>
	    </span>
    </div><!-- /input-group -->
    
  </div><!-- /.col-md-4 -->

  <div class="col-xs-1 col-md-4"></div>
</div><!-- /.row -->


<!-- JS Global Compulsory -->	
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>		
<script src="//netdna.bootstrapcdn.com/bootstrap/3.0.0/js/bootstrap.min.js"></script>


<script>
	function htmlEncode(value){
	    if (value) {
	        return jQuery('<div />').text(value).html();
	    } else {
	        return '';
	    }
	}
	 
	function htmlDecode(value) {
	    if (value) {
	        return $('<div />').html(value).text();
	    } else {
	        return '';
	    }
	}
</script>

<script>
	$( document.body ).on( 'click', '.dropdown-menu li', function( event ) {
		 var $target = $( event.currentTarget );
	 
	   $target.closest( '.input-group-btn' )
	      .find( '[data-bind="label"]' ).text( $target.text() )
	         .end()
	      .children( '.dropdown-toggle' ).dropdown( 'toggle' );
	 
	   return false;
	 
	});
</script>



<script>

	function OpenInNewTab() {
		var s = document.getElementById("server").innerHTML.toUpperCase();
		console.log(s);
		if ( s == 'EUW' || s == 'NA' || s == 'LAN' || s == 'LAS' || s == 'EUNE' || s == 'TR' || s == 'RU' || s == 'BR' || s == 'OCE' || s == 'KR' ){
			var summoner = document.getElementById("summoner").value;
			console.log("server: "+s+";summoner: "+summoner);
			var url = '/game?server='+s+'&summoner='+summoner;
			var win = window.open(url, '_self');
		}else{
			console.log("choose a server");
		}
    	
    }

</script>



<span style="color:#ffffff;user-select: none;cursor:default;-webkit-touch-callout: none;-webkit-user-select: none;-khtml-user-select: none;-moz-user-select: none;-ms-user-select: none;" id="tags">
<br><br><br><br><br><br><br><br><br><br><br><br><br><br><br>
text info here
</span>

</body>
</html>