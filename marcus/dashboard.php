<!DOCTYPE HTML>
<?php include "session.php" ; ?>
<html>
<head>
	<title>Autonomous Occupants Monitoring System</title>
	<meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
	<meta name="description" content="Marcus Demo by TME">
	<meta name="author" content="Dennis Chen @ TME, 2017-08-20">	
<style>
body {
	margin: 1px;
	padding: 0px;
}
header{
    padding: 5px;
    color: white;
    background-color: brown;
    clear: left;
    text-align: center;
}
footer {
    padding: 5px;
    color: white;
    background-color: brown;
    clear: left;
    text-align: center;
}
.divTable{
	display: table;
	width: 100%;
}
.divTableRow {
	display: table-row;
}
.divTableCell {
	display: table-cell;
}
.mdu_block {
	border: 1px solid #d9d9d9;
	padding: 3px 10px;
	width: 25% ;
}

.mdu_status_left{
	background-color : rgba(177,0,195,0.4);
    width: 70%;
    height: 100%;
	padding: 0 0 0.5em 0;
}
.mdu_status_right{
	background-color : rgba(231,179,255,0.4);
    width: 30%;
    height: 100%;
}
.mdu_status_bottom{
	text-align: center;
	padding: 5px;
}

fieldset
{
    border: 1px solid #ddd;
    padding: 0 1.0em 1.0em 1.0em;
    border-radius: 8px;
    height: 100%;
}

.setupbutton
{
	float: left ;
}	

.menublock
{
	display:none;
    position: fixed;
	top: 20px ;
	left: 65px ;
    background: white ;
	z-index:5 ;
}

</style>
	<link rel="stylesheet" href="jq/jquery-ui.css">
	<script src="js/jsaes.js"></script>	
	<script src="jq/jquery.js"></script>
	<link rel="stylesheet" href="jq/ui.jqgrid.css">
	<script src="jq/jquery-ui.js"></script>
	<script src="jq/jquery.jqGrid.min.js"></script>
	<script src="https://www.gstatic.com/firebasejs/4.10.1/firebase.js"></script>
	<script src="setfire.js"></script>
	<script src="mdueditor.js"></script>
<script>
	
$( function() {

	function sel_options( list ) 
	{
		var html = "";
		for( var i=0; i<list.length; i++ ) {
			html += "<option value=\"" + list[i].value + "\">" + list[i].name + "</option>" ;
		}
		return html ;
	}

	$( ".tabs" ).tabs();
	
	var userprivs = {} ;
	var usertype = 'user' ;
	$.getJSON("userpriv.php", function( resp ){
		if( resp.res && resp.privs) {
			userprivs = resp.privs ;
			usertype = resp.usertype ;
			if( !userprivs.ConfigureUnit ) {
				$("button#bt_unitconfig").hide();
			}
		}
	});

	// default room algs ???
	var roomAlgs = {};
	
	firebase.marcus_get( '/units/options/roomalgs', function(val){
		roomAlgs = val ;
	} );
 	
	function updateAlgs( algs ) {
		var i ;
		for (i in roomAlgs ) {
			roomAlgs[i] = false ;
		}
		if( algs )
		for (i in algs ) {
			roomAlgs[i] = algs[i];
		}
		return roomAlgs ;
	}

	
    function addRoomTab( conf, idx ) {
		var room = conf.rooms[idx] ;
		var id = "tabs_room_" + idx ;
		
		var template = $("div#tabs_room_template") ;
		var newtab = '<div id="' + id + '" class="roomtabs">' + template.html() + '</div>' ;
		var roomtabs = $("div#room_tabs");
		roomtabs.append( newtab );

		var li = '<li class=\"roomtabs\"><a id="n_' + id + '" href="#' + id + '">' + room.name + '</a></li>' ;
		roomtabs.find("ul").append( li );
		
		// set values
		newtab = $('div#' + id);
		newtab.find("input[name='room_name']").val(room.name);
		newtab.find("input[name='room_name']").on("change", function(){
			$("a#n_"+id).text( $(this).val() );
		});
		
		var html='';
		for( var i in conf.roomTypes ) {
			html += '<option>' + conf.roomTypes[i] + '</option>' ;
		}
		newtab.find("select[name='room_type']").html( html );
		newtab.find("select[name='room_type']").val( room.type );
		
		// update roomalgs
		updateAlgs( room.alg ) ;
		html = '' ;
		for (var name in roomAlgs ) {
			var cbid = name + '-' + idx ;
			html += '<label for="' + cbid + '"><input type="checkbox" class="roomalgs" name="' + name + '" id="' + cbid + '">' + name + '</label>';
		}
		var roomalg = newtab.find("div#room_alg");
		roomalg.html(html);
		for (var name in room.alg ) {
			roomalg.find("input.roomalgs[name=\"" + name + "\"]").prop('checked', room.alg[name]);
		}
		roomalg.find("input.roomalgs").checkboxradio({
			icon: false
		});
		$( ".tabs" ).tabs("refresh");
    }
	
    function addCameraTab( conf , idx ) {
		var camera = conf.cameras[idx] ;
		var id = "tabs_cam_" + idx ;
		
		var template = $("div#tabs_cam_template") ;
		var newtab = '<div id="' + id + '" class="camtabs">' + template.html() + '</div>' ;
		var camtabs = $("div#camera_tabs");
		camtabs.append( newtab );

		var li = '<li class=\"camtabs\"><a id="n_' + id + '" href="#' + id + '">' + camera.name + '</a></li>' ;
		camtabs.find("ul").append( li );
		
		// set values
		$('div#' + id).find("input[name='camera_enable']").prop('checked', camera.enable);
		$('div#' + id).find("input[name='camera_name']").val(camera.name);
		$('div#' + id).find("input[name='camera_name']").on("change", function(){
			$("a#n_"+id).text( $(this).val() );
		});
		var html='';
		for( var i=0; i<conf.fps_ops.length; i++ ) {
			html += '<option>' + conf.fps_ops[i] + '</option>' ;
		}
		$('div#' + id).find("select[name='camera_framerate']").html( html );
		$('div#' + id).find("select[name='camera_framerate']").val( camera.fps );
		
		html='';
		for( var i=0; i<conf.br_ops.length; i++ ) {
			html += '<option>' + conf.br_ops[i] + '</option>' ;
		}
		$('div#' + id).find("select[name='camera_bitrate']").html( html );
		$('div#' + id).find("select[name='camera_bitrate']").val( camera.bitrate );
		
		html='';
		for( var i=0; i<conf.res_ops.length; i++ ) {
			html += '<option>' + conf.res_ops[i] + '</option>' ;
		}
		$('div#' + id).find("select[name='camera_resolution']").html( html );
		$('div#' + id).find("select[name='camera_resolution']").val( camera.resolution );

		html='';
		for( var i=0; i<conf.rooms.length; i++ ) {
			html += '<option value="'+ i + '">' + conf.rooms[i].name + '</option>' ;
		}
		$('div#' + id).find("select[name='camera_room']").html( html );
		if( camera.roomIndex >= conf.rooms.length ) camera.roomIndex = 0 ;
		$('div#' + id).find("select[name='camera_room']").val( camera.roomIndex );
		
		$( ".tabs" ).tabs("refresh");
    }
		
	var mdu_box = null ;		// current mdu box
	var mdu_dialog = null ;		// current waiting dialog

	$( "div#dialog_unit_config" ).dialog({
		autoOpen: false,
		resizable: false,
		height: "auto",
		width: "auto",
		modal: true,
		open: function( event, ui ) {

			var _this = this ;
			this.mdu = {
				conf:null,
				confref:null,
			} ;
			if( !mdu_box.mdu_unitid ) {
				$( this ).dialog( "close" );
				return ;
			}
			mdu_dialog = null ;
			
			this.mdu_loadconf=function() {
				if( !this.mdu || !this.mdu.conf ) {
					return ;
				}
				// timezone
				var html='';
				for( var i in this.mdu.conf.timezones ) {
					html += '<option>' + this.mdu.conf.timezones[i] + '</option>' ;
				}
				var tzsel = $( this ).find( "select#unit_timezone");
				tzsel.html( html );
				tzsel.val( this.mdu.conf.timezone );
				
				$(this).find("input[name='alertL1']").val( this.mdu.conf.alertL1 );
				$(this).find("input[name='alertN1']").val( this.mdu.conf.alertN1 );
				$(this).find("input[name='alertN2']").val( this.mdu.conf.alertN2 );
				
				if( !this.mdu.conf.rooms ) {
					this.mdu.conf.rooms=[];
				}
				$(".roomtabs").remove();
				for( var i in this.mdu.conf.rooms ) {
					var room = this.mdu.conf.rooms[i] ;
					if( this.mdu.conf.rooms[i] ) {
						addRoomTab( this.mdu.conf, i );
					}
				}

				if( !this.mdu.conf.cameras ) {
					this.mdu.conf.cameras=[];
				}
				$(".camtabs").remove();
				for( var i in this.mdu.conf.cameras ) {
					addCameraTab(this.mdu.conf, i) ;
				}
				
				$( ".tabs" ).tabs("option", "active", 0 );

			};
			
			this.mdu_saveconf=function() {
				
				this.mdu.conf.timezone = $( this ).find( "select#unit_timezone").val();
				
				this.mdu.conf.alertL1 =	parseInt( $(this).find("input[name='alertL1']").val() );
				this.mdu.conf.alertN1 = parseInt( $(this).find("input[name='alertN1']").val() );
				this.mdu.conf.alertN2 = parseInt( $(this).find("input[name='alertN2']").val() );

				var roomtabs = $("div.roomtabs");
				var rooms = [] ;
				for( var i=0; i<roomtabs.length; i++ ) {
					var rname = $(roomtabs[i]).find("input[name='room_name']").val();
					var rtype = $(roomtabs[i]).find("select[name='room_type']").val();
					var ralg = {} ;
					var algck = $(roomtabs[i]).find("input.roomalgs");
					for( var c=0; c<algck.length; c++ ) {
						var input = algck[c] ;
						ralg[input.name] = $(input).prop("checked");
					}
					rooms.push({
						name: rname,
						type: rtype,
						alg: ralg 
					});
				}
				this.mdu.conf.rooms = rooms ;
				
				for( var i=0; i < this.mdu.conf.cameras.length; i++ ) {
					var camera = this.mdu.conf.cameras[i];
					var camtab = $("div#tabs_cam_" + i) ;
					if( camtab.length>0 ) {
						camera.enable = camtab.find("input[name='camera_enable']").prop('checked' );
						camera.name =   camtab.find("input[name='camera_name']").val();
						camera.resolution =camtab.find("select[name='camera_resolution']").val();

						camera.fps =    parseInt(camtab.find("select[name='camera_framerate']").val());
						if( ! Number.isInteger(camera.fps) ) 
							camera.fps = this.mdu.conf.fps_ops[0] ;	
						camera.bitrate =parseInt(camtab.find("select[name='camera_bitrate']").val());
						if( ! Number.isInteger(camera.bitrate) ) 
							camera.bitrate = this.mdu.conf.br_ops[0] ;	
						camera.roomIndex =parseInt( camtab.find("select[name='camera_room']").val());
						if( ! Number.isInteger(camera.roomIndex) ) 
							camera.roomIndex = 0 ;	
					}
				}

			};
			
			$(".roomtabs").remove();
			$( "#tabs" ).tabs( "refresh" );
			
			firebase.marcus_onfire( function() {
				$.getJSON("confload.php", {'unitid': mdu_box.mdu_unitid}, function(list){
					if( list.res && list.fireid && _this.mdu ) {
						_this.mdu.confref = firebase.database().ref( '/units/' + list.fireid + '/conf' ) ;
						_this.mdu.confref.on('value', function(snapshot) {
							_this.mdu.conf = snapshot.val();
							mdu_box.mdu_conf = _this.mdu.conf ;

							if( _this.mdu.conf ) {
								_this.mdu_loadconf();
							}
						});
					}
				});
			});
		},
		close: function() {
			if( this.mdu.confref ) {
				this.mdu.confref.off('value');
			}
			delete this.mdu ;
			delete mdu_box.mdu_conf ;
			$("div#dialog_unit_config #sel_camera").html("");;			
			$(".roomtabs").remove();
			$(".camtabs").remove();			
			$( "#tabs" ).tabs( "refresh" );			
		},
		buttons: {
			'Save': function() {
				this.mdu_saveconf();
				this.mdu.confref.set(this.mdu.conf, function() {
					$.post("confsave.php", {subid:mdu_box.mdu_subid});					
				});
			},
			'Close': function() {
				$( this ).dialog( "close" );
			}
		}
	});
	
	var roomCounter = 1 ;
	$("button#addRoom").click(function(){
		var dialogconfig = $( "div#dialog_unit_config" ) ;
		dialogconfig[0].mdu_saveconf();
		var conf = dialogconfig[0].mdu.conf ;
		conf.rooms.push({
		  "alg" : updateAlgs() ,
		  "name" : "New Room "+roomCounter ,
		  "type" : "Bedroom"
		});
		dialogconfig[0].mdu_loadconf();
		roomCounter++;
	});

	$("button#delRoom").click(function(){
		var room = $("div#room_tabs").tabs("option", "active" );
		var dialogconfig = $( "div#dialog_unit_config" ) ;
		dialogconfig[0].mdu_saveconf();
		var conf = dialogconfig[0].mdu.conf ;
		conf.rooms.splice(room, 1); 
		dialogconfig[0].mdu_loadconf();
	});
	
	$( "div#dialog_unit_editroc" ).dialog({
		autoOpen: false,
		resizable: false,
		height: "auto",
		width: "auto",
		modal: true,
		open: function( event, ui ) {
			this.mdu_editor = new mdueditor( $(this).find("canvas")[0] );
			this.mdu_editor.showROC = true ;
			this.mdu_cam = $("div#camera_tabs").tabs("option", "active" );
			this.mdu_editor.load( mdu_box.mdu_subid, mdu_box.mdu_fireid, this.mdu_cam );
		},
		close: function() {
			this.mdu_editor.release();
			delete this.mdu_editor ;
			$("div#dialog_unit_config #sel_camera").html("");;			
		},
		buttons: {
			'Save': function() {
				var cam = this.mdu_cam ;
				this.mdu_editor.saveConf(function(){
					$("div#camera_tabs").tabs("option", "active" , cam );
					$.post("confsave.php", {'subid':mdu_box.mdu_subid,'saveroc':true,'camera':cam});
					
				});		
			},
			'Close': function() {
				$( this ).dialog( "close" );
			}
		}
	});
	
	$("button#camera_roc").click( function(){
		$( "div#dialog_unit_editroc" ).dialog("open");
	});
	
	$( "div#dialog_unit_editaoi" ).dialog({
		autoOpen: false,
		resizable: false,
		height: "auto",
		width: "auto",
		modal: true,
		open: function( event, ui ) {
			this.mdu_editor = new mdueditor( $(this).find("canvas")[0] );
			this.mdu_editor.showAOI = true ;
			var camera = $("div#camera_tabs").tabs("option", "active" );
			this.mdu_editor.load( mdu_box.mdu_subid, mdu_box.mdu_fireid, camera );
		},
		close: function() {
			this.mdu_editor.release();
			delete this.mdu_editor ;
			$("div#dialog_unit_config #sel_camera").html("");;			
		},
		buttons: {
			'New AOI': function() {
				this.mdu_editor.newAOI();				
			},
			'Delete AOI': function() {
				this.mdu_editor.deleteAOI();					
			},
			'Clear All': function() {
				this.mdu_editor.clearAOI();				
			},			
			'Save': function() {
				this.mdu_editor.saveConf(function(){
					//$.post("confsave.php", {subid:mdu_box.mdu_subid});
				});		
			},
			'Close': function() {
				$( this ).dialog( "close" );
			}
		}
	});
	
	$("button#camera_aoi").click( function(){
		$( "div#dialog_unit_editaoi" ).dialog("open");
	});
	
	$( "div#dialog_metaview" ).dialog({
		autoOpen: false,
		resizable: false,
		height: "auto",
		width: "auto",
		modal: true,
		open: function( event, ui ) {
			var _this = this ;
			if( !mdu_box.mdu_subid ) {
				$( this ).dialog( "close" );
				return ;
			}
			mdu_dialog = null ;

			this.mdu_editor = new mdueditor( $( "div#dialog_metaview canvas" )[0] );
			this.mdu_editor.showPose = true ;
			
			var sel_cam = $(this).find("#sel_camera");
			sel_cam.on("change", function(){
				var cam = sel_cam.val();
				_this.mdu_editor.load( mdu_box.mdu_subid, mdu_box.mdu_fireid, cam );		
				$.getJSON("metaview.php", {'subid': mdu_box.mdu_subid, 'camera': cam, 'on':true });
			});

			if( mdu_box.mdu_unitid ){
				$.getJSON("cameralist.php", {'unitid': mdu_box.mdu_unitid}, function(list){
					if( list.res && list.cameras ) {
						sel_cam.html( sel_options(list.cameras) );
						sel_cam.trigger("change");
					}
				});
			}
		},
		close: function() {
			this.mdu_editor.release();
			delete this.mdu_editor ;

			var sel_cam = $(this).find("#sel_camera");
			sel_cam.off("change");
			sel_cam.html("");
			// to stop metaview
			$.getJSON("metaview.php", {'subid': mdu_box.mdu_subid} );
		}
	});
	
	$( "#dialog_aoi_name" ).dialog({
		autoOpen: false,
		resizable: false,
		height: "auto",
		width: "auto",
		modal: true,
		create: function() {
			load_list( "conf/aoi_names", function( list ) {
				var selects = "" ;
				for( var s=0; s<list.length; s++ ) {
					selects += "<option>" + list[s] + "</option>" ;
				}
				$("#id_aoi_names").html(selects);
			});
		},
		open: function( event, ui ) {
			if( mdu_box.mdu_conf ) {
				var list = mdu_box.mdu_conf.aoiNames ;
				var selects = "" ;
				for( var s=0; s<list.length; s++ ) {
					selects += "<option>" + list[s] + "</option>" ;
				}
				$("#id_aoi_names").html(selects);
			}
		},		
		close: function() {
			if( this.onComplete ) {
				this.onComplete(null);
				delete this.onComplete ;
			}
		},
		buttons: {
			"Save": function() {
				if( this.onComplete ) {
					this.onComplete($("#id_aoi_names").val());
					delete this.onComplete ;
				}
				$( this ).dialog( "close" );
			},
			"Cancel": function() {
				$( this ).dialog( "close" );
			}
		}
	});

	var location_list = [] ;
	
	$( "div#dialog_select_unit" ).dialog({
		autoOpen: false,
		resizable: false,
		height: "auto",
		width: "auto",
		modal: true,
		open: function() {
			var _this = this ;
			this.mdu_unitlist = [] ;
			this.mdu_unitid = '' ;
			this.locationid = '' ;
			$("#sel_location").change(function(evt){
				_this.locationid = $("#sel_location").val();
				$.getJSON("unitlist.php", {'locationid': _this.locationid }, function(list){
					if( list.res ) {
						var html = "";
						_this.mdu_unitlist = list.unitlist ;
						for( var i=0; i<list.unitlist.length; i++ ) {
							html += "<option value=\"" + i + "\">" + list.unitlist[i].name + "</option>" ;
						}
						$( "select#sel_unit" ).html( html );
					}
				});
			});
			if( !location_list || location_list.length == 0 ) {
				$.getJSON("locationlist.php", function(list){
					if( list.res ) {
						location_list = list.locationlist ;
						var html = "";
						for( var i=0; i<location_list.length; i++ ) {
							html += "<option value=\"" + location_list[i].id + "\">" + location_list[i].name + "</option>" ;
						}
						$( "select#sel_location" ).html( html );
						$( "select#sel_location" ).trigger("change");
					}
				});
			}
			else {
				$( "select#sel_location" ).trigger("change");
			}
		},
		close: function() {
			delete this.mdu_unitlist ;
			$("#sel_location").off();
		},
		buttons: {
			"Select": function() {
				var i = $( "select#sel_unit" ).val() ;
				if( i>=0 && i< this.mdu_unitlist.length )
				mdu_box.mdu_setunit( this.mdu_unitlist[i].id, function(){
					if( mdu_dialog ) {
						$(mdu_dialog).dialog("open");
						mdu_dialog = null ;
					}					
				});
				$( this ).dialog( "close" );
			},
			"Close Unit": function() {
				mdu_box.mdu_setunit( null, function(){
					mdu_dialog = null ;
				});
				$( this ).dialog( "close" );
			},
			"Cancel": function() {
				$( this ).dialog( "close" );
			}
		}   
	});

	var subjectStatus = [] ;
	firebase.marcus_get( "/units/options/subjectStatus", function( val ){
		subjectStatus = val ;
	});

	
	// duplicate status boxes
	$(".mdu_block").each(function() {
		$(this).html($("div.mdu_status_template").html());
		$(this).find("table.daygrid").jqGrid({        
			scroll: true,
			datatype: "local",
			height: 180,
			colNames:['Event', 'Today', 'Yesterday', '2 Days Ago', 'Avg/30 days'],
			colModel:[
				{name:'event',index:'event', width: 120, sortable:false},
				{name:'today',index:'today', width: 70, sortable: true},
				{name:'yesterday',index:'yesterday', width: 70, sortable: true},
				{name:'day2',index:'day2', width: 80, sortable:true},
				{name:'day30',index:'day30', width: 80, sortable:true}
			]
		});

		this.mdu_unitid = null ;
		this.mdu_subid = null ;
		this.mdu_fireid = null ;
			
		this.mdu_setunit = function( unitid, onComplete ) {
			var box = this ;
			if( box.mdu_unitid && unitid == box.mdu_unitid ) {
				// return ;
			}
			if( box.mdu_subid ) {
				// unregister old unit
				$.getJSON("subsessionkill.php", {'subid':box.mdu_subid});
			}
			box.mdu_unitid = null ;
			box.mdu_subid = null ;
			box.mdu_fireid = null ;
			if( box.mdu_statusref  ) {
				box.mdu_statusref.off('value');
				box.mdu_statusref = null ;
			}			
			if( box.mdu_dayeventsref ) {
				box.mdu_dayeventsref.off('value');
				box.mdu_dayeventsref = null ;
			}

			$(box).find("#mdu_location").text( "" );
			$(box).find("#mdu_name").text( "" );

			$(box).find("#mdu_subject_status").html( "" );
			
			var table = $(box).find("table.daygrid") ;
			var ids = table.jqGrid( 'getDataIDs' );
			if( ids.length > 0 ) {
				for( var i =0 ; i< ids.length; i++ ) {
					$(table).jqGrid("delRowData", ids[i]);
				}
			}

			if( unitid ) {
				firebase.marcus_onfire(function(){
					$.getJSON("subsessionstart.php", {'unitid': unitid }, function(list){
						if( list.res ) {
							if( list.location_id ) {
								$(box).find("#mdu_location").text( list.location_id );
							}
							if( list.name ) {
								$(box).find("#mdu_name").text( list.name );
							}
							box.mdu_unitid = list.unitid ;
							box.mdu_fireid = list.fireid ;
							box.mdu_subid = list.subid ;
							box.mdu_statusref = firebase.database().ref( '/units/' + list.fireid + '/subjectstatus' ) ;
							box.mdu_statusref.on('value', function(snapshot) {
								// mdu box is ready
								var mdu_status = snapshot.val();
								$(box).find("#mdu_subject_status").html(mdu_status);
							});
							box.mdu_dayeventsref = firebase.database().ref( '/units/' + list.fireid + '/dayevents' ) ;
							box.mdu_dayeventsref.on('value', function(snapshot) {
								// mdu box is ready
								var table = $(box).find("table.daygrid") ;
								var ids = table.jqGrid( 'getDataIDs' );
								if( ids.length > 0 ) {
									for( var i =0 ; i< ids.length; i++ ) {
										$(table).jqGrid("delRowData", ids[i]);
									}
								}								
								var dayevents = snapshot.val() ;
								table.jqGrid('addRowData',1,dayevents);
							});
							// to load day report data
							$.getJSON("dayreport.php", {'unitid': box.mdu_unitid, 'fireid': box.mdu_fireid}, function(resp){
							});
							
							if( onComplete ) 
								onComplete();
						}
						else {
							if( list.errmsg ) {
								alert( list.errmsg );
							}
						}
					});
				});
			}
			else {
				if( onComplete ) 
					onComplete();
			}
		}
	});

	$("button#bt_selectunit").click(function(evt) {
		evt.preventDefault();
		$(this).parents(".mdu_block").first().each(function(){
			mdu_box = this;
			mdu_dialog = null ;
			$( "div#dialog_select_unit" ).dialog("open");
		});
	});
	
	$("button#bt_metaview").click(function(evt) {
		evt.preventDefault();
		$(this).parents(".mdu_block").first().each(function(){
			mdu_box = this;
			if( !mdu_box.mdu_unitid ) {
				// try open unit selection first
				mdu_dialog = $( "div#dialog_metaview" )[0] ;
				$( "div#dialog_select_unit" ).dialog("open");			
			}
			else {
				$( "div#dialog_metaview" ).dialog("open");
			}			
		});
	});
	
	$("button#bt_unitconfig").click(function(evt) {
		evt.preventDefault();
		$(this).parents(".mdu_block").first().each(function(){
			mdu_box = this;
			if( !mdu_box.mdu_unitid ) {
				// try open unit selection first
				mdu_dialog = $( "div#dialog_unit_config" )[0] ;
				$( "div#dialog_select_unit" ).dialog("open");
			}
			else {
				$( "div#dialog_unit_config" ).dialog("open");
			}
		});
	});

	var user_edit_id = null;
	// User editor dialog
	$( "div#dialog_user_edit" ).dialog({
		autoOpen: false,
		resizable: false,
		height: "auto",
		width: "auto",
		modal: true,
		open: function() {
			if( user_edit_id != null ) {
				$( this ).dialog( "option", "title", "Edit User" );		
				var rowData = $("table#grid_users").getRowData(user_edit_id);
				for( var f in rowData ) {
					$("form#form_user_edit [name=\"" + f + "\"]").val( rowData[f] );
				}
				$("form#form_user_edit [name=\"user_type\"]").attr("disabled", "disabled");
				$("form#form_user_edit [name=\"user_name\"]").attr("readonly", true);
			}
			else {
				$( this ).dialog( "option", "title", "New User" );				
				$("form#form_user_edit input").val("");
				$("form#form_user_edit [name=\"user_name\"]").val("newuser");
				$("form#form_user_edit [name=\"user_type\"]").val("user");
				$("form#form_user_edit [name=\"user_type\"]").attr("disabled", false);
				$("form#form_user_edit [name=\"user_name\"]").attr("readonly", false);
			}
			$("form#form_user_edit input[name=\"user_password\"]").val("********");
		},
		close: function() {

		},
		buttons: {
			"Save": function() {
				var param = $("form#form_user_edit").serializeArray();
				if( user_edit_id == null ) {
					param.push( {name:'adduser',value: true } );
				}
				$.getJSON( "usersave.php", param, function( resp ){
					if( resp.res == 1 ) {
						$("table#grid_users").trigger('reloadGrid');
					}
					else {
						if( resp.errormsg ) {
							alert( "Error: " + resp.errormsg );
						}
						else {
							alert( "Error: modify user failed");
						}
					}
				});
				
				$(this).dialog("close");
			},
			"Cancel": function() {
				$( this ).dialog( "close" );
			}

		}   
	});
	
	
	// User Management
	$( "div#dialog_user" ).dialog({
		autoOpen: false,
		resizable: false,
		height: "auto",
		width: "auto",
		modal: true,
		open: function() {
			if( ! this.user_grid ) {
				this.user_grid = true ;
				$("table#grid_users").jqGrid({
					url:'usergrid.php',
					datatype: 'json',
					colNames:['User Name', 'User Type', 'Contact Name'],
					colModel :[ 
					  {name:'user_name', index:'user_id', width:120	}, 
					  {name:'user_type', index:'user_type', width:120 }, 
					  {name:'real_name', index:'real_name', width:300} 
					],
					rowNum:10,
					rowList:[10,20,30],
					sortname: 'user_name',
					sortorder: 'desc',
					viewrecords: true
			  });
			}
			else {
				$("table#grid_users").trigger('reloadGrid');
			}
		},
		close: function() {
			user_edit_id = null;
		},
		buttons: {
			<?php if( get_privilege( "EditUser" ) ) { ?>
			"Add User": function() {
				user_edit_id = null ;
				$( "div#dialog_user_edit" ).dialog("open");
			},
			<?php } ?>
			"Edit User": function() {
				user_edit_id = $("table#grid_users").jqGrid ('getGridParam', 'selrow') ;
				if( user_edit_id != null ) {
					$( "div#dialog_user_edit" ).dialog("open");
				}
			},
			<?php if( get_privilege( "EditUser" ) ) { ?>
			"Delete User": function() {
				var user_id = $("table#grid_users").jqGrid ('getGridParam', 'selrow') ;
				if( user_id != null ) {
					var rowData = $("table#grid_users").getRowData(user_id);
					if( rowData.user_name ) {
						var param = { 'user_name' : rowData.user_name } ;
						$.getJSON( "userdel.php", param, function(resp){
							if( resp.res == 0 ) {
								if( resp.errormsg ) {
									alert("Error: "+resp.errormsg );
								}
								else {
									alert("Error: delete user failed!");
								}
							}
							else {
								$("table#grid_users").trigger('reloadGrid');
							}
						} );
					}
				}
			},
			<?php } ?>
			"Close": function() {
				$( this ).dialog( "close" );
			}

		}   
	});

	// mdu group editor
	var group_edit_id = null;
	$( "div#dialog_mdu_group_edit" ).dialog({
		autoOpen: false,
		resizable: false,
		height: "auto",
		width: "500px",
		modal: true,
		
		open: function() {
			if( group_edit_id != null ) {
				$( this ).dialog( "option", "title", "Edit MDU Group" );		
				var rowData = $("table#grid_mdu_group").getRowData(group_edit_id);
				var xsel = rowData.mdu_id_list.split(";") ;
				$("form#form_mdu_group [name=\"id\"]").val(rowData.id);
				$("form#form_mdu_group select").val(xsel) ;
				$("form#form_mdu_group [name=\"id\"]").attr("readonly", true);
			}
			else {
				$( this ).dialog( "option", "title", "New MDU Group" );		
				$("form#form_mdu_group [name=\"id\"]").val("newgroup");
				$("form#form_mdu_group select").val([]) ;
				$("form#form_mdu_group [name=\"id\"]").attr("readonly", false);
			}
		},
		close: function() {

		},
		buttons: {
			"Save": function() {
				var param = $("form#form_mdu_group").serializeArray();
				if( group_edit_id == null ) {
					param.push( {name:'addgroup',value: true } );
				}
				$.getJSON( "mdugroupsave.php", param, function( resp ){
					if( resp.res == 1 ) {
						$("table#grid_mdu_group").trigger('reloadGrid');
					}
					else {
						if( resp.errormsg ) {
							alert( "Error: " + resp.errormsg );
						}
						else {
							alert( "Error: modify group failed");
						}
					}
				});
				
				$(this).dialog("close");
			},
			"Cancel": function() {
				$( this ).dialog( "close" );
			}
		}   
	});
	
	// Group Management
	$( "div#dialog_mdu_group" ).dialog({
		autoOpen: false,
		resizable: false,
		height: "auto",
		width: "auto",
		modal: true,
		open: function() {
			if( ! this.mdugroup_grid ) {
				this.mdugroup_grid = true ;
				$("table#grid_mdu_group").jqGrid({
					url:'mdugroupgrid.php',
					datatype: 'json',
					colNames:['Group ID', 'MDU List'],
					colModel :[
					  {name:'id', index:'id', width:120	},
					  {name:'mdu_id_list', index:'mdu_id_list', width:500 }
					],
					rowNum:100,
					rowList:[100,200,300],
					sortname: 'id',
					sortorder: 'desc',
					viewrecords: true
				});
				  
				  // also retrive full mdu list
				$.getJSON("mdulist.php", function( resp ) {
					if( resp.res && resp.mdulist ) {
						var shtml = '' ;
						var i;
						for( i = 0; i < resp.mdulist.length; i++) { 
							shtml += "<option value=\"" + resp.mdulist[i].id + "\">" + resp.mdulist[i].location_id + " - " + resp.mdulist[i].unit_id + "</option>" ;
						}
						$("form#form_mdu_group select").html( shtml );
						$("#form_mdu_group select[multiple] option").off();
						$("#form_mdu_group select[multiple] option").mousedown(function(){
							$("form#form_mdu_group select").focus();
							var $self = $(this);
							$self.prop("selected", !$self.prop("selected"));
							return false;
						});			
					}
				});			  
			  
			}
			else {
				$("table#grid_mdu_group").trigger('reloadGrid');
			}
		},
		close: function() {
			group_edit_id = null;
		},
		buttons: {
			"Add Group": function() {
				group_edit_id = null ;
				$( "div#dialog_mdu_group_edit" ).dialog("open");
			},
			"Edit Group": function() {
				group_edit_id = $("table#grid_mdu_group").jqGrid ('getGridParam', 'selrow') ;
				if( group_edit_id != null ) {
					$( "div#dialog_mdu_group_edit" ).dialog("open");
				}
			},
			"Delete Group": function() {
				var group_id = $("table#grid_mdu_group").jqGrid ('getGridParam', 'selrow') ;
				if( group_id != null ) {
					var rowData = $("table#grid_mdu_group").getRowData(group_id);
					if( rowData.id ) {
						var param = { 'id' : rowData.id } ;
						$.getJSON( "mdugroupdel.php", param, function(resp){
							if( resp.res == 0 ) {
								if( resp.errormsg ) {
									alert("Error: "+resp.errormsg );
								}
								else {
									alert("Error: delete mdu group failed!");
								}
							}
							else {
								$("table#grid_mdu_group").trigger('reloadGrid');
							}
						});
					}
				}
			},
			"Close": function() {
				$( this ).dialog( "close" );
			}

		}   
	});
	
	// Email alert editor
	var alert_edit_id = null;
	$( "div#dialog_alert_edit" ).dialog({
		autoOpen: false,
		resizable: false,
		height: "auto",
		width: "500px",
		modal: true,
		
		open: function() {
			if( alert_edit_id != null ) {
				$( this ).dialog( "option", "title", "Edit Alert" );		
				var rowData = $("table#grid_alert").getRowData(alert_edit_id);
				
				$("form#form_alert [name=\"group_id\"]").val(rowData.group_id);
				$("form#form_alert [name=\"type\"]").val(rowData.type);
				$("form#form_alert [name=\"to_email_addr\"]").val(rowData.to_email_addr);
			}
			else {
				$( this ).dialog( "option", "title", "New Alert" );		

				$("form#form_alert [name=\"type\"]").val(1);
				$("form#form_alert [name=\"to_email_addr\"]").val("");
			}
		},
		close: function() {

		},
		buttons: {
			"Save": function() {
				var param = $("form#form_alert").serializeArray();
				if( alert_edit_id == null ) {
					param.push( {name:'addalert',value: true } );
				}
				else {
					param.push( {name:'serialNo',value: alert_edit_id } );
				}
				$.getJSON( "alertsave.php", param, function( resp ){
					if( resp.res == 1 ) {
						$("table#grid_alert").trigger('reloadGrid');
					}
					else {
						if( resp.errormsg ) {
							alert( "Error: " + resp.errormsg );
						}
						else {
							alert( "Error: modify group failed");
						}
					}
				});
				
				$(this).dialog("close");
			},
			"Cancel": function() {
				$( this ).dialog( "close" );
			}
		}   
	});
	
	// Alert Management
	$( "div#dialog_alert" ).dialog({
		autoOpen: false,
		resizable: false,
		height: "auto",
		width: "auto",
		modal: true,
		open: function() {
			if( ! this.alert_grid ) {
				this.alert_grid = true ;
				$("table#grid_alert").jqGrid({
					url:'alertgrid.php',
					datatype: 'json',
					colNames:['Group ID',  'type', 'Alert Type', 'Email List'],
					colModel :[
					  {name:'group_id', index:'group_id', width:100	},
					  {name:'type', index:'type', hidden: true	},
					  {name:'typeName', index:'type', width:150	},
					  {name:'to_email_addr', index:'to_email_addr', width:300 }
					],
					rowNum:100,
					rowList:[100,200,300],
					sortname: 'group_id',
					sortorder: 'desc',
					viewrecords: true
				});
				
				// setup selects on editor
				$.getJSON("conf/event_types.json", function(resp){
					var options = '';
					for( var i=1 ;i<resp.length; i++ ) {
						options += "<option value=\"" + i + "\">" + resp[i]+"</option>" ;
					}
					$("form#form_alert [name=\"type\"]").html(options);
				});
			}
			else {
				$("table#grid_alert").trigger('reloadGrid');
			}
			$.getJSON("mdugrouplist.php", function(resp){
				var options = '';
				if( resp.res && resp.grouplist ) {
					for( var i=0 ;i<resp.grouplist.length; i++ ) {
						options += "<option>" + resp.grouplist[i] +"</option>" ;
					}
				}
				$("form#form_alert [name=\"group_id\"]").html(options);
			});			
		},
		close: function() {
			alert_edit_id = null;
		},
		buttons: {
			"Add Alert": function() {
				alert_edit_id = null ;
				$( "div#dialog_alert_edit" ).dialog("open");
			},
			"Edit Alert": function() {
				alert_edit_id = $("table#grid_alert").jqGrid ('getGridParam', 'selrow') ;
				if( alert_edit_id != null ) {
					$( "div#dialog_alert_edit" ).dialog("open");
				}
			},
			"Delete Alert": function() {
				var alert_id = $("table#grid_alert").jqGrid ('getGridParam', 'selrow') ;
				if( alert_id != null ) {
					var param = { 'serialNo' : alert_id } ;
					$.getJSON( "alertdel.php", param, function(resp){
						if( resp.res == 0 ) {
							if( resp.errormsg ) {
								alert("Error: "+resp.errormsg );
							}
							else {
								alert("Error: delete alert failed!");
							}
						}
						else {
							$("table#grid_alert").trigger('reloadGrid');
						}
					});

				}
			},
			"Close": function() {
				$( this ).dialog( "close" );
			}
		}   
	});

	
	$(".menublock").hide();
	$( "#menu" ).menu({
		select: function( evt, ui ) {
			evt.preventDefault();
			var val = ui.item.attr("value") ;
			if( val ) {
				if( val == "users" ) {
					$( "div#dialog_user" ).dialog("open");
				}
				else if( val == "mgroup" ) {
					$( "div#dialog_mdu_group" ).dialog("open");
				}
				else if( val == "email" ) {
				}
				else if( val == "alerts" ) {
					$( "div#dialog_alert" ).dialog("open");
				}
				else if( val == "logout" ) {
					location.assign("logout.php");
				}
				$(".menublock").hide("dropdown");
			}
		}
	});
	$("input.setupbutton").click(function(evt){
		evt.preventDefault();
		// $( "#menu" ).show();
		if( $(".menublock").css("display") == "none" ) {
			$(".menublock").show("dropdown");
			$( "#menu" ).menu( "refresh" );
		}
		else {
			$(".menublock").hide("dropdown");
		}
		
	});
	
	$("button").button();
	$("body").show("fast");
	
	$(window).on("unload", function(evt) {
		var ids = [] ;
		$(".mdu_block").each(function() {
			if( this.mdu_subid ) {
				ids.push( this.mdu_subid );
				if( this.mdu_fireid ) {
					firebase.marcus_set( '/units/' + this.mdu_fireid + '/ts', firebase.database.ServerValue.TIMESTAMP ) ;
				}
			}
		});
		$.ajax("subsessionkill.php", {data: {"subid": ids }, async:false, timeout: 300});
	});
	
	// check session timeout
	function checkTimeOut() {
		$.getJSON( "getsession.php", function(resp) {
			if( !resp.res || resp.timeout<=0 ) {
				location.assign("logout.php");
			}
			else {
				setTimeout( checkTimeOut, resp.timeout * 1000 );
			}
		});
	}
	setTimeout( checkTimeOut, 30000 );
});

</script>

</head>
<body>
<div class="box">
 
<header>
<input class="setupbutton" type="image" src="res/setupgear.png" alt="Setup" width="48" height="48">
<div class="menublock">
<ul id="menu" >
  <li value="users"><div>Users Management</div></li>
  <li value="mgroup"><div>MDU Group</div></li>
  <li value="alerts"><div>Alerts</div></li>
  <li value="logout"><div>Logout</div></li>
</ul>
</div>

<h3>Marcus Dashboard</h3>
</header>

<div class="divTable">
<div class="divTableRow">
<div class="divTableCell mdu_block"></div>
<div class="divTableCell mdu_block"></div>
<div class="divTableCell mdu_block"></div>
<div class="divTableCell mdu_block"></div>
</div>
<div class="divTableRow">
<div class="divTableCell mdu_block"></div>
<div class="divTableCell mdu_block"></div>
<div class="divTableCell mdu_block"></div>
<div class="divTableCell mdu_block"></div>
</div>
</div>
<footer>Copyright &copy; Toronto MicroElectronics Inc</footer>

<div class="mdu_status_template" style="display: none;">
<div class="divTable">
    <div class="divTableRow">
        <div class="divTableCell mdu_status_left">
            <fieldset><legend>Subject Status</legend>
			<p id="mdu_subject_status">No status available!</p>
            </fieldset>
        </div>
        <div class="divTableCell mdu_status_right">
			<div style="min-height:112px">
				<div id="mdu_name"></div>
				<div id="mdu_location"></div>
				<button id="bt_selectunit">Select Unit</button><br/>
				<button id="bt_metaview">Meta View</button><br/>
				<button id="bt_unitconfig">Unit Config</button>
			</div>          
        </div>
    </div>
</div>
<div class="mdu_status_bottom">
<span>
	<table class="daygrid"></table>
</span>
</div>
</div>

<div id="dialog_select_unit" title="Select Unit" style="display: none;" >
<table border="0" cellpadding="0" cellspacing="1" id="event" width="100%">
<tr>
	<td>Select Location: </td>
	<td>
		<select id="sel_location">
		</select>
	</td>
</tr>
<tr>
	<td>Select Unit:</td>
	<td>
		<select id="sel_unit">
		</select>
	</td>
</tr>
</table>
</div>

<div id="dialog_metaview" title="Unit Meta View" style="display:none" >
	<div>
	Select Camera: <select id="sel_camera"></select>
	</div>
	<p/>
    <canvas id="unit_config_canvas" width="800" height="600" style="border:1px solid #000000;"></canvas>
</div>

<div id="dialog_unit_editroc" title="Edit ROC" style="display:none" >
    <canvas id="unit_config_canvas" width="800" height="600" style="border:1px solid #000000;"></canvas>
</div>

<div id="dialog_unit_editaoi" title="Edit AOI" style="display:none" >
    <canvas id="unit_config_canvas" width="800" height="600" style="border:1px solid #000000;"></canvas>
</div>

<div id="dialog_unit_config" title="Unit Config" style="display:none" >
	<div>
	Select Timezone: <select id="unit_timezone"></select>
	</div>
	alertL1: <input type="number" size="4" max="600" min="2" name="alertL1">
	alertN1: <input type="number" size="4" max="600" min="2" name="alertN1">
	alertN2: <input type="number" size="4" max="600" min="2" name="alertN2">
	<fieldset>
    <legend>Rooms:</legend>
	<div class="tabs" id="room_tabs" >
	  <ul>
	  </ul>
	</div>
	<button id="addRoom">Add New Room</button><button id="delRoom">Delete Room</button>
	  <div id="tabs_room_template" style="display:none">
		<table>
		<tr>
			<td>Name:</td><td><input type="text" name="room_name"></input></td>
		</tr>
		<tr>
			<td>Room Type:</td><td><select name="room_type"></select></td>
		</tr>
		</table>
		<fieldset>
		<legend>algorithm:</legend>
		<div id="room_alg">
		</div>
		</fieldset>		
	  </div>
	</fieldset>
	
	<fieldset>
    <legend>Cameras:</legend>
	<div class="tabs" id="camera_tabs">
	  <ul>
	  </ul>
	</div>
	<button id="camera_roc">Set ROC</button>
	<button id="camera_aoi">Set AOI</button>
	  <div id="tabs_cam_template"  style="display:none">
		<table>
		<tr>
			<td>Enable:</td><td><input type="checkbox" name="camera_enable"></input></td>
		</tr>
		<tr>
			<td>Name:</td><td><input type="text" name="camera_name"></input></td>
		</tr>
		<tr>
			<td>Frame Rate:</td><td><select name="camera_framerate"></select></td>
		</tr>
		<tr>
			<td>Bitrate:</td><td><select name="camera_bitrate"></select></td>
		</tr>
		<tr>
			<td>Resolution:</td><td><select name="camera_resolution"></select></td>
		</tr>
		<tr>
			<td>Room:</td><td><select name="camera_room"></select></td>
		</tr>
		</table>
	  </div>
	</fieldset>
</div>

<div id="dialog_aoi_name" title="Select AOI name" style="display:none" >
	<p>A new AOI created, please select AOI name:</p>
	<select id="id_aoi_names"></select>
</div>

<div id="dialog_user" title="Users Management" style="display:none" >
	<table id="grid_users"></table>
</div>

<div id="dialog_user_edit" title="User" style="display:none" >
<form action="#" id="form_user_edit">

	<br/>
    <label for="i_user_name">User Name</label>
	<br/>
	<input type="text" name="user_name" id="i_user_name"></input>
	<br/>
    <label for="i_user_password">Password</label>
	<br/>
	<input type="password" name="user_password" id="i_user_password"></input>
	<br/>
    <label for="i_user_type">User Type</label>
	<br/>
    <select name="user_type" id="i_user_type">
      <option value="user">user</option>
      <option value="sensor">sensor user</option>
      <option value="admin">admin user</option>
      <option value="power">power user</option>
      <option value="superpower">super power user</option>
    </select>
	<br/>
    <label for="i_real_name">Contact Name</label>
	<br/>
	<input type="text" name="real_name" id="i_real_name"></input>

</form>
</div>

<div id="dialog_mdu_group" title="MDU Group" style="display:none" >
	<table id="grid_mdu_group"></table>
</div>

<div id="dialog_mdu_group_edit" title="MDU Group" style="display:none" >
<form action="#" id="form_mdu_group">
    <label for="i_goup_id">Group ID</label>
	<br/>
	<input type="text" name="id" id="i_goup_id"></input>
	<br/>
	<label for="i_id_list">Select MDU</label>
	<br/>
    <select name="mdu_id_list[]" id="i_id_list" multiple="true" size="15" style="width:100%;"> 
    </select>
</form>
</div>

<div id="dialog_alert" title="Alert Management" style="display:none" >
	<table id="grid_alert"></table>
</div>

<div id="dialog_alert_edit" title="MDU Group" style="display:none" >
<form action="#" id="form_alert">
    <label for="i_goup_id">Select MDU Group</label>
	<br/>
    <select name="group_id" id="i_group_id"></select>
	<br/>
    <label for="i_alert_type">Select Alert Type</label>
	<br/>
    <select name="type" id="i_alert_type"></select>
	<br/>
    <label for="i_alert_email">Enter Emails Address (seperated with semicolon)</label>
	<br/>
	<textarea name="to_email_addr" id="i_alert_email" rows="10" style="width:100%;"></textarea>
</form>
</div>

</div>	
</body>
</html>