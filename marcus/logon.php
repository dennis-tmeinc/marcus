<!DOCTYPE html>
<?php
$noredir = 1 ;
require_once 'session.php' ;
?><html>
<head>
	<title>Autonomous Occupants Monitoring System</title>
	<meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
	<meta name="description" content="Marcus Demo by TME V0.1">

	<meta name="author" content="Dennis Chen @ TME, 2017-08-25">	
	<link rel="shortcut icon" href="/favicon.ico" />
	<link href="tdclayout.css" rel="stylesheet" type="text/css" />
	
	<script src="jq/jquery.js"></script>
	<script src="jq/jquery-ui.js"></script>

	<script src="md5min.js"></script>
	<style> body { display:none; } </style>
	<script>
// start up 

$(document).ready(function(){

$("button").button();

<?php if( !empty($_COOKIE['ui'])) { ?>
// update cookie
var d = new Date();
d.setTime(d.getTime()+180*24*60*60*1000);
document.cookie =  "ui=" + escape( "<?php echo $_COOKIE['ui'] ?>" )+"; expires="+d.toGMTString();
<?php } ?>
	
function gencnonce(bits)
{
  var hexch = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ " ;
  var output = "" ;
  for( var i=0; i<bits; i++ ) {
	  output += hexch.charAt(Math.random()*62);
  }
  return output ;
}

function wait( w )
{
    if( w ) {
		$("body").append('<div class="wait"></div>');
	}
	else {
		$("div.wait").remove();
	}
}

function pwdEncode(pwd)
{
	if( pwd.length==0 ) return "";
	var acode = "a".charCodeAt(0);
	var bcode = ""+pwd.length+pwd;
	var blen = bcode.length ;
	var ecd="";
	var offset=[3, 1, 4, 1, 5, 9, 2, 6, 5, 3, 5, 8, 9, 7, 9, 3, 2, 3, 8, 4];
	for( var i=0; i<20; i++) {
		ecd += String.fromCharCode(acode+(bcode.charCodeAt(i%blen)+offset[i])%26);
	}
	return ecd;
}

$("form").submit(function(e){
	e.preventDefault();
	var userid=$("#userid").val();
	var clientid=$("#clientid").val();

	if (typeof(Storage) !== "undefined") {
		localStorage.setItem("tme_marcus_clientid", clientid);
		localStorage.setItem("tme_marcus_userid", userid);
	} 
	
	wait(1);
	var nonce = gencnonce(10);
	
	$.post("signuser.php", { user: userid, c: clientid, n: nonce }, function(data) {
		wait(0);
		if( data.res == 1 && data.user.toLowerCase() == userid.toLowerCase() ) {
		  var vcnonce=gencnonce(64);
		  var ha1 ;
		  if( data.keytype==0 ){
		  ha1 = hex_md5(data.user+":"+data.slt+":"+pwdEncode($("#password").val())) ;
		  }
		  else if(data.keytype==1){
		  ha1 = hex_md5(data.user+":"+data.slt+":"+$("#password").val()) ;
		  }
		  else {
			alert("Wrong keys!");
		  }
		  var ha2 = hex_md5(vcnonce+":"+data.user+":"+data.nonce);
		  var vresult = hex_md5(ha1+":"+ha2+":"+data.nonce+":"+vcnonce);
		  wait(1);
		  $.post("signkey.php", { user: data.user, cnonce: vcnonce, result: vresult }, function(kdata){
			wait(0);
			if( kdata.res == 1 && kdata.user == data.user && kdata.page ) {
				window.location.replace(kdata.page);
			}
			else {
			  alert("Password error!") ;
			}
		  }, "json" );
		}
		else {
			if( data.errormsg ){
				alert(data.errormsg);
			}
			else {
				alert("Username error!") ;
			}
		}
	}, "json" );
});

$(window).resize(function(){
   var workarea = $("#workarea") ;
   var nh = window.innerHeight - workarea.offset().top -$("#footer").outerHeight() - 32 ;
   if( nh != workarea.height() ) {	// height changed
	  workarea.height( nh );
   }
});

setTimeout( '$(window).resize();' , 200);

$("body").show();

if (typeof(Storage) !== "undefined") {
	
	var clientid = localStorage.getItem("tme_marcus_clientid" );
	if( clientid ) 
		$("input#clientid").val(clientid);
	var userid = localStorage.getItem("tme_marcus_userid" );
	if( userid ) 
		$("input#userid").val(userid);
} 
});
 
</script>  
</head>
<body>
<div id="rcontainer">
<div id="workarea" style="width:752px;margin:auto;min-height:400px;text-align:center"> 

<img alt="Autonomous Occupants Monitoring System" src="res/main-logo-top.jpg" />
<form style="padding:30px;">
<fieldset><legend> Sign in to <?php echo $product_name ; ?></legend>
<div style="padding-left:30px;text-align:left">

<div id="dclientid">
<p>Client ID<br />
<input id="clientid" name="clientid" /></p>
</div>

<p>User ID<br />
<input id="userid" name="userid" type="text" /></p>

<p>Password<br />
<input id="password" name="password" type="password" /></p>

<table border="0" cellpadding="0" cellspacing="0" style="width:95%">
	<tbody>
		<tr>
			<td><button id="usersignin" type="submit">Sign In</button></td>
			<td style="text-align: right;"><?php 
				if( !empty( $_SESSION['clientid'] ) ) echo $_SESSION['clientid'] ;
			?></td>
		</tr>
	</tbody>
</table>

</div>
</fieldset>
</form>
</div>
<!-- workarea --></div>
<!-- mcontainer -->

<div id="footer">
<hr />
<div id="footerline" style="padding-left:24px;padding-right:24px">
<div style="float:left"></div>

<p style="text-align: right;"><span style="font-size:11px;"><a href="http://www.247securityinc.com/" style="text-decoration:none;">247 Security Inc.</a></span></p>
</div>
</div>
</body>
</html>

