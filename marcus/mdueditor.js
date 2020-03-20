
function load_list( listname, callback )
{
	$.get( listname, function( data ){
		var arr = data.split("\n");
		var lst = [] ;
		for( var s=0; s<arr.length; s++ ) {
			var a = arr[s].trim();
			if( a && a.length>0 ) {
				lst.push( a );
			}
		}			
		callback( lst );
	}, "text" );
}

function mdueditor( canvas ) {
	var _this = this ;
	var xscale = 1 ;
	var yscale = 1 ;
	var polys = [ {
		name:"ROC",
		points: [] }];
	var poly_sel = -1 ;
	var aoi_mode = 0 ;
	var mousePos = {x:-1,y:-1} ;
	var bgImg = null ;
	
	this.showPose = false ;
	this.showROC = false ;
	this.showAOI = false ;
	
	var ctx = canvas.getContext('2d');	// canvas context
	ctx.lineJoin="round" ;
	ctx.lineCap ="round" ;
	
	if ( ! mdueditor.poly_colors ) {
		mdueditor.poly_colors = ["red","yellow","green"] ;
		load_list( "conf/color_vals", function( list ) {
			mdueditor.poly_colors = list ;
		});
    }

	function paint_bkground() {
		if( bgImg && bgImg.complete && bgImg.width > 50 )
			ctx.drawImage(bgImg , 0, 0, canvas.width, canvas.height );			
		else 
			ctx.clearRect(0, 0, canvas.width, canvas.height);
	}
	
	function paint_roc() {
		if( polys.length > 0 && polys[0].points ) {
			var points = polys[0].points ;
			if( points.length > 2 ) {
				var w = canvas.width ;
				var h = canvas.height ;		
				ctx.beginPath();
				ctx.lineJoin="round";
				ctx.strokeStyle= mdueditor.poly_colors[ 0 ] ; 		
				ctx.lineWidth=2;
				ctx.rect(points[0].x * w, points[0].y * h,
					(points[2].x-points[0].x) * w, (points[2].y-points[0].y) * h );
				ctx.stroke();
				ctx.fillStyle="rgba(32, 8, 16, 0.1)";
				ctx.fill();
			}
		}
	}
	
	function paint_aoi() {
		var w = canvas.width ;
		var h = canvas.height ;
		for(var p=1; p<polys.length; p++ ) {
			var poly = polys[p] ;
			var points = poly.points ;
			if( points.length > 0 ) {
				ctx.beginPath();
				ctx.lineJoin="round";
				ctx.strokeStyle= mdueditor.poly_colors[ p%mdueditor.poly_colors.length ] ; 		
				if( p == poly_sel ) {
					ctx.lineWidth=5;
				}
				else {
					ctx.lineWidth=2;
				}
				ctx.moveTo( points[0].x * w, points[0].y * h );
				for( var i = 1; i< points.length; i++ ) {
					ctx.lineTo(points[i].x * w, points[i].y * h );
				}
				if( aoi_mode == 2 && p==polys.length-1 ) {
					if(( points.length>2 &&  Math.pow( mousePos.x-points[0].x, 2 ) +  Math.pow(mousePos.y-points[0].y , 2 ) < 0.001 ) || points.length>=1000 ){
						ctx.lineWidth=5;
						ctx.closePath();
						poly.closing = true ;
					}
					else {
						ctx.lineTo(mousePos.x * w, mousePos.y * h) ;
						poly.closing = false ;
					}
				}
				else {
					ctx.closePath();					
				}
				ctx.stroke();
				if( poly_sel == p ) {
					ctx.fillStyle="rgba( 0, 0, 0, 0.1)";
					ctx.fill();
					ctx.font = "28px Arial";
					ctx.fillStyle = "Gold";
					ctx.fillText(polys[poly_sel].name, polys[poly_sel].points[0].x * w +5, polys[poly_sel].points[0].y * h +26 );
				}	
			}
		}
	}
		
	var pose ;		// single people pose array
	
	function paint_people() {
		// draw POSE lines
		if( valPose && valPose.people ) {
			var p ;
			for( p=0; p<valPose.people.length; p++ ) {
				pose = valPose.people[p] ;

				function pose_line( f, t ) {
					if( pose[f].x > 0 && pose[t].x > 0 ) {
						ctx.moveTo( pose[f].x * xscale, pose[f].y * yscale );
						ctx.lineTo( pose[t].x * xscale, pose[t].y * yscale );
					}
				}
				
				function pose_dot( d ) {
					if( pose[d].x > 0 ) {
						var rad = 4 ;
						ctx.beginPath();
						ctx.moveTo( pose[d].x * xscale + rad, pose[d].y * yscale );
						ctx.arc( pose[d].x * xscale, pose[d].y * yscale, rad, 0, 2 * Math.PI);
						ctx.fill();
					}
				}
				
				// get neck ;
				if( pose[1].x <= 0 && pose[1].y <= 0 ) {
					if( pose[2].x>0 && pose[5].x > 0 ) {
						pose[1].x = (pose[2].x+pose[5].x)/2 ;
						pose[1].y = (pose[2].y+pose[5].y)/2 ;
					}
					else if( pose[1].x > 0 ) {
						pose[2] = pose[1] ;
					}
				}

				// draw all lines
				var lines = [
					[14,15],
					[16,14],
					[15,17],
					[14,0],
					[15,0],
					[0, 1],
					[1,2],
					[1,5],
					[2,3],
					[3,4],
					[5,6],
					[6,7],
					[5,11],
					[5,8],
					[2,8],
					[2,11],
					[11,8],
					[11,12],
					[12,13],
					[8,9],
					[9,10]
				];
				
				ctx.strokeStyle = mdueditor.poly_colors[ p%mdueditor.poly_colors.length ] ; 		
				ctx.fillStyle = ctx.strokeStyle ;
				ctx.lineWidth=3;

				ctx.beginPath();
				var i ;
				for( i=0; i<lines.length; i++ ) {
					pose_line( lines[i][0], lines[i][1] );
				}
				ctx.stroke();
				for( i = 0; i<pose.length; i++ ) {
					pose_dot( i );
				}
			}
		}
	};

	// pose index
	const p_nose = 0 ;
	const p_neck = 1 ;
	
	const p_l_eye = 15 ;
	const p_l_ear = 17 ;
	const p_l_shoulder = 5 ;
	const p_l_elbow = 6 ;
	const p_l_wrist = 7 ;
	const p_l_hip = 11 ;
	const p_l_knee = 12 ;
	const p_l_ankle = 13 ;

	const p_r_eye = 14 ;
	const p_r_ear = 16 ;
	const p_r_shoulder = 2 ;
	const p_r_elbow = 3 ;
	const p_r_wrist = 4 ;
	const p_r_hip = 8 ;
	const p_r_knee = 9 ;
	const p_r_ankle = 10 ;
	
	var trunksize = 100 ;
	
	function trunkheight() {
		var top =0;
		var bottom = 50 ;
		if( pose[p_neck].x > 0 ) {
			top = pose[p_neck].y ;
		}
		else if( pose[p_l_shoulder].x > 0 ) {
			top = pose[p_l_shoulder].y ;
		}
		else if( pose[p_r_shoulder].x > 0 ) {
			top = pose[p_r_shoulder].y ;
		}
		if( pose[p_l_hip].x > 0 ) {
			bottom = pose[p_l_hip].y ;
		}
		else if( pose[p_r_hip].x > 0 ) {
			bottom = pose[p_r_hip].y ;
		}
		return Math.abs( bottom-top);
	}

	function pose_line( f, t ) {
		if( pose[f].x > 1 && pose[t].x > 1 ) {
			ctx.beginPath();
			ctx.moveTo( pose[f].x , pose[f].y );
			ctx.lineTo( pose[t].x , pose[t].y );
			ctx.stroke();
		}
	}

	function pose_dot( d, r ) {
		if( pose[d].x > 1 ) {
			if( r < 1 )
				r=1 ;
			ctx.beginPath();
			ctx.moveTo( pose[d].x + r, pose[d].y  );
			ctx.arc( pose[d].x , pose[d].y , r, 0, 2 * Math.PI);
			ctx.fill();
		}
	}	
	
	function draw_skel() {
		// draw all lines
		var lines = [
			[14,15],
			[16,14],
			[15,17],
			[14,0],
			[15,0],
			[0, 1],
			[1,2],
			[1,5],
			[2,3],
			[3,4],
			[5,6],
			[6,7],
			[5,11],
			[5,8],
			[2,8],
			[2,11],
			[11,8],
			[11,12],
			[12,13],
			[8,9],
			[9,10]
		];
		
		ctx.lineWidth=3;
		var i ;
		for( i=0; i<lines.length; i++ ) {
			pose_line( lines[i][0], lines[i][1] );
		}
		for( i = 0; i<pose.length; i++ ) {
			pose_dot( i, trunksize/20 );
		}
	}

	function distance( a, b )
	{
		return Math.sqrt( Math.pow( a.x - b.x, 2) +  Math.pow( a.y - b.y , 2));
	}
	 
	 function draw_head()
	 {
	    var ss ;
		var head = {"x":0,"y":0, "r": 0};
		if( pose[p_l_ear].x>1 && pose[p_r_ear].x>1 ) {
			head.x = ( pose[p_l_ear].x + pose[p_r_ear].x )/2 ;
			head.y = ( pose[p_l_ear].y + pose[p_r_ear].y )/2 ;
			head.r = distance(  pose[p_l_ear],  pose[p_r_ear] )/2;
			// draw head
			ctx.beginPath();
			ctx.moveTo( head.x + head.r, head.y );
			ctx.arc( head.x , head.y , head.r, 0, 2 * Math.PI);
			ctx.fill();			

		}
		else if( pose[p_nose].x>1 ) {
			head.x = pose[p_nose].x ;
			head.y = pose[p_nose].y ;
			head.r = trunksize * 0.2 ;
		}
		else {
			return ;
		}

		// neck
		if( head.x > 1 && pose[ p_neck].x>1) {
			ss = ctx.lineWidth ;
			if( head.r>1) 
				ctx.lineWidth = head.r / 1.2  ;
			else 
				ctx.lineWidth = 3 ;
			ctx.beginPath();
			ctx.moveTo( head.x , head.y );
			ctx.lineTo( pose[p_neck].x, pose[p_neck].y);
			ctx.stroke();
			ctx.lineWidth = ss ;
		}
		
		// ears
		if( pose[ p_l_ear ].x> 0 ) {
			pose_dot( p_l_ear, head.r/4 );
		}
		if( pose[ p_r_ear ].x> 0 ) {
			pose_dot( p_r_ear, head.r/4 );
		}
		
		// left eye
		if( pose[ p_l_eye ].x> 0 ) {
			ss = ctx.fillStyle ;
			ctx.fillStyle = "black" ; 	
			pose_dot( p_l_eye, head.r/8 );
			ctx.fillStyle = ss ; ;
		}
		// right eye
		if( pose[ p_r_eye ].x> 0 ) {
			ss = ctx.fillStyle ;
			ctx.fillStyle = "black" ; 	
			pose_dot( p_r_eye, head.r/8 );
			ctx.fillStyle = ss ; ;
		}

		// nose
		if( pose[p_nose].x > 1 ) {
			ss = ctx.fillStyle ;
			ctx.fillStyle = "gray" ; 	
			pose_dot( p_nose, head.r/10 );
			ctx.fillStyle = ss ; ;

			// draw mouth
			if( pose[ p_l_eye ].x > 1 && pose[ p_r_eye ].x> 1 ) {
				var cx = (pose[ p_l_eye ].x + pose[ p_r_eye ].x ) /2 ;
				var cy = (pose[ p_l_eye ].y + pose[ p_r_eye ].y ) /2 ;
				var mouth={};
				mouth.x = pose[ p_nose ].x + (pose[ p_nose ].x - cx) / 2 ;
				mouth.y = pose[ p_nose ].y + (pose[ p_nose ].y - cy) / 2 ;

				ss = ctx.strokeStyle ;
				ctx.strokeStyle = "gray" ; 	
				ctx.lineWidth=1;
				ctx.beginPath();
				var r = head.r/5 ;
				ctx.moveTo( mouth.x + r, mouth.y );
				ctx.arc( mouth.x , mouth.y, r, 0, Math.PI, false);
				ctx.stroke();
				ctx.strokeStyle = ss ;
			}
		}
	 }
	 
	function draw_trunk() {
		if( pose[p_l_shoulder].x>0 
			&& pose[p_r_shoulder].x>0 
			&& pose[p_l_hip].x>0 
			&& pose[p_r_hip].x>0  ) {
				
			trunksize = trunkheight() ;

			var c1 = {};
			var c2 = {};

			ctx.beginPath();
			ctx.moveTo( pose[p_l_shoulder].x, pose[p_l_shoulder].y);
			if( pose[p_nose].x>0 && pose[p_neck].x>0 ) {
				c1.x = (pose[p_nose].x + pose[p_neck].x)/2 ;
				c1.y = (pose[p_nose].y + pose[p_neck].y)/2 ;
				ctx.quadraticCurveTo(c1.x, c1.y, pose[p_r_shoulder].x, pose[p_r_shoulder].y);
			}
			else {
				ctx.lineTo( pose[p_r_shoulder].x, pose[p_r_shoulder].y);
			}

			// right side
			var dx = pose[p_l_shoulder].x - pose[p_r_hip].x ;
			var dy = pose[p_l_shoulder].y - pose[p_r_hip].y ;
			
			c1.x = pose[p_r_shoulder].x - dx / 10 ;
			c1.y = pose[p_r_shoulder].y - dy / 10 ;
			c2.x = pose[p_r_hip].x - dx / 10 ;
			c2.y = pose[p_r_hip].y - dy / 10 ;
			ctx.bezierCurveTo(c1.x, c1.y, c2.x, c2.y, pose[p_r_hip].x, pose[p_r_hip].y);
			
			// hip
			dx = pose[p_r_hip].x - pose[p_l_hip].x ;
			c1.x = pose[p_r_hip].x - dx / 5 ;
			c1.y = pose[p_r_hip].y - dy / 5 ;
			c2.x = pose[p_l_hip].x + dx / 5 ;
			c2.y = pose[p_l_hip].y - dy / 5 ;
			ctx.bezierCurveTo(c1.x, c1.y, c2.x, c2.y, pose[p_l_hip].x, pose[p_l_hip].y);
			
			// left side
			dx = pose[p_r_shoulder].x - pose[p_l_hip].x ;
			dy = pose[p_r_shoulder].y - pose[p_l_hip].y ;
			
			c1.x = pose[p_l_shoulder].x - dx / 10 ;
			c1.y = pose[p_l_shoulder].y - dy / 10 ;
			c2.x = pose[p_l_hip].x - dx / 10 ;
			c2.y = pose[p_l_hip].y + dy / 10 ;
			ctx.bezierCurveTo(c1.x, c1.y, c2.x, c2.y, pose[p_l_shoulder].x, pose[p_l_shoulder].y);
			
			ctx.closePath();
			
			ctx.fill();
			
		}
	}		

	function arm( d1, d2 )
	{
		if( d1.x<=1 || d2.x<=1 )
			return;
		
		 var angle = Math.atan( (d2.y-d1.y)/(d2.x-d1.x) );
		 var width = trunksize / 10 ;

		 var rang = angle - Math.PI/2 ;
		 var d3 = {"x":0,"y":0};
		 var d4 = {"x":0,"y":0};
		 
		 ctx.beginPath();
		 
		 d3.x = d1.x ;
		 d3.y = d1.y ;
		 d3.y += width * Math.sin( angle - Math.PI/2 );
		 d3.x += width * Math.cos( angle - Math.PI/2 );
		 d4.x = d2.x ;
		 d4.y = d2.y ;
		 d4.y += width * Math.sin( angle - Math.PI/2 ) / 1.5;
		 d4.x += width * Math.cos( angle - Math.PI/2 ) / 1.5;
		 
		// This is where the curve begins (P0)
		ctx.moveTo(d1.x, d1.y);
		
		ctx.bezierCurveTo(d3.x, d3.y, d4.x, d4.y, d2.x, d2.y );

		 d3.x = d2.x ;
		 d3.y = d2.y ;
		 d3.y += width * Math.sin( angle + Math.PI/2 ) / 1.5;
		 d3.x += width * Math.cos( angle + Math.PI/2 ) / 1.5;
		 d4.x = d1.x ;
		 d4.y = d1.y ;
		 d4.y += width * Math.sin( angle + Math.PI/2 );
		 d4.x += width * Math.cos( angle + Math.PI/2 );
		 
		// This is where the curve begins (P0)
		ctx.bezierCurveTo(d3.x, d3.y, d4.x, d4.y, d1.x, d1.y );

		ctx.fill();
		
	}

	// d1: hip, d2: knee, d3 rhip
	function leg( d1, d2, d3) {
		
		if( d3==null || d3.x<=1 ) {
			return arm( d1, d2 )
		}
		
		if( d1.x<=1 || d2.x <= 1 )
			return ;
		
		var dx = d3.x - d1.x ;
		var dy = d3.y - d1.y ;

		ctx.beginPath();
		
		var c1 = {};
		var d12 = {};
		
		c1.x = d1.x + dx/1.5 ;
		c1.y = d1.y + dy/1.5 ;
		d12.x = d2.x + dx/5 ;
		d12.y = d2.y + dy/5 ;
		
		ctx.moveTo(d1.x, d1.y);
		
		ctx.bezierCurveTo(c1.x, c1.y, d12.x, d12.y, d2.x, d2.y );

		c1.x = d2.x - dx/3 ;
		c1.y = d2.y - dy/3 ;
		d12.x = d1.x - dx/3 ;
		d12.y = d1.y - dy/3 ;
		
		ctx.bezierCurveTo(c1.x, c1.y, d12.x, d12.y, d1.x, d1.y );
		ctx.fill();
	}
		
	function draw_people(){
	 
		draw_trunk();
		draw_head();
		
		arm( pose[p_l_shoulder], pose[p_l_elbow] );
		arm( pose[p_r_shoulder], pose[p_r_elbow] );
		arm( pose[p_l_elbow], pose[p_l_wrist] );
		arm( pose[p_r_elbow], pose[p_r_wrist] );

		leg( pose[p_l_hip], pose[p_l_knee], pose[p_r_hip] );
		leg( pose[p_r_hip], pose[p_r_knee], pose[p_l_hip] );
		leg( pose[p_l_knee], pose[p_l_ankle], null );
		leg( pose[p_r_knee], pose[p_r_ankle], null );

   }
	
	function paint_people_n() {

		if( valPose && valPose.people ) {
			var p ;
			for( p=0; p<valPose.people.length; p++ ) {
				pose = valPose.people[p] ;
				
				// set ctx color
				ctx.strokeStyle = mdueditor.poly_colors[ p%mdueditor.poly_colors.length ] ; 		
				ctx.fillStyle = ctx.strokeStyle ;
				ctx.lineWidth=3;
				
				// scale all pose pointer
				var i ;
				for( i=0; i<pose.length; i++ ) {
					if( pose[i].x > 1 && pose[i].y > 1 ) {
						pose[i].x = pose[i].x*xscale ;
						pose[i].y = pose[i].y*yscale ;
					}
					else {
						pose[i].x = 0 ;
						pose[i].y = 0 ;						
					}
				}
				
				// fix missing pose 
				if( pose[p_l_shoulder].x< 1 && pose[p_r_shoulder].x > 0 ) {
					pose[p_l_shoulder].x =  pose[p_r_shoulder].x ;
					pose[p_l_shoulder].y =  pose[p_r_shoulder].y ;
				}
				if( pose[p_neck].x < 1 ) {
					pose[p_neck].x = (pose[p_r_shoulder].x + pose[p_r_shoulder].x)/2 ;
					pose[p_neck].y = (pose[p_r_shoulder].y + pose[p_r_shoulder].y)/2 ;
				}
				if( pose[p_r_shoulder].x<=0 && pose[p_l_shoulder].x > 0 ) {
					pose[p_r_shoulder].x =  pose[p_l_shoulder].x ;
					pose[p_r_shoulder].y =  pose[p_l_shoulder].y ;
				}			
				if( pose[p_l_hip].x<=0 && pose[p_r_hip].x > 0 ) {
					pose[p_l_hip].x =  pose[p_r_hip].x ;
					pose[p_l_hip].y =  pose[p_r_hip].y ;
				}
				if( pose[p_r_hip].x<=0 && pose[p_l_hip].x > 0 ) {
					pose[p_r_hip].x =  pose[p_l_hip].x ;
					pose[p_r_hip].y =  pose[p_l_hip].y ;
				}
				
				// draw_skel() ;
				draw_people();
				
			}
		}
	}
		
	// update display
	var paintId	= null ;
	function paint() 
	{
		paint_bkground();
		if( _this.showPose )
			paint_people_n();
		if( _this.showROC )
			paint_roc();
		if( _this.showAOI )
			paint_aoi();
		paintId	= null ;
	}
		
	function redraw() {
		if( paintId == null ) {
			paintId = window.requestAnimationFrame(paint);
		}
	}
	
	// clear all old event handler
	$(canvas).off();
	
	function getMousePos(evt) {
		var offset = $(canvas).offset();
		return {
			x: (evt.pageX - offset.left) / canvas.width ,
			y: (evt.pageY - offset.top) / canvas.height 
		};
	}
	
	$(canvas).mouseleave( function( evt ) {
		$(canvas).css("cursor", "auto" );
	});

	$(canvas).mousemove( function( evt ) {
		mousePos = getMousePos(evt);
		if( aoi_mode == 1 ) {		// new ROC
			var poly = polys[0] ;
			if( poly.points.length > 0 ) {
				var p1 = poly.points[0] ;
				poly.points = [
					p1,
					{x:mousePos.x, y:p1.y},
					mousePos,
					{x:p1.x, y:mousePos.y},
					p1
					];
				var cursorname = 
					(( mousePos.y >= p1.y )?"s":"n" ) +
					(( mousePos.x >= p1.x )?"e":"w" ) +
					"-resize" ;
				$(canvas).css("cursor", cursorname );
				redraw();			
			}
		}		
		else if( aoi_mode == 2 ) {	// new AOI
			redraw();
		}
		else if( aoi_mode == 3 ) {		// delete Aoi
			var context = canvas.getContext('2d');
			var sel ;
			for( sel=polys.length-1; sel>0; sel-- ) {
				var poly = polys[sel] ;
				var points = poly.points ;
				if( points.length > 2 ) {
					context.beginPath();
					context.moveTo( points[0].x, points[0].y  ) ;
					for( var i = 1; i< points.length; i++ ) {
						context.lineTo(points[i].x, points[i].y  ) ;
					}
					context.closePath();
					if( context.isPointInPath(mousePos.x, mousePos.y) ) {
						break ;
					}
				}
			}
			if( sel != poly_sel ) {
				poly_sel = sel ;
				redraw();
			}
		}
	});
	
	$(canvas).on("click", function( evt ) {
		mousePos = getMousePos(evt);

		// enter ROC mode automatically
		if( _this.showROC && aoi_mode == 0 ) {
			_this.newROC();
		}
		
		if( aoi_mode == 1 ) {		// new ROC
			var poly = polys[0] ;
			if(poly.points.length>2) {
				aoi_mode = 0 ;	// complete ROC
			}
			else {
				poly.points = [	mousePos ] ;
			}
			$(canvas).css("cursor", "auto" );
			redraw();
		}
		else if( aoi_mode == 2 ) {	// new AOI
			var l = polys.length ;
			if( l>1 ) {
				var poly = polys[l-1] ;
				if( poly.closing ) {
					delete poly.closing ;
					$( "#dialog_aoi_name" )[0].onComplete = function( aoiname ) {
						if( aoiname ) 
							_this.saveAOI( aoiname );
						else 
							_this.cancelAOI();
					}
					$( "#dialog_aoi_name" ).dialog("open");
				}
				else {
					var points = poly.points ;
					if( points.length==0 || mousePos.x != points[ points.length -1 ].x || mousePos.y != points[ points.length -1 ].y ) {
						poly.points.push(mousePos);
						paint();
						if( poly.closing ) {
							$(canvas).click();
						}
					}
				}					
				redraw();
			}
		}
		else if( aoi_mode == 3 ) {	// deleting AOI
			if( poly_sel>0 && poly_sel<polys.length ) {
				polys[poly_sel].points.length=0 ;
				poly_sel = -1 ;
				redraw();
			}
		}
	});
	
	this.newAOI = function() {
		polys.push( {
			name: "" ,
			points:[]
		});
		aoi_mode = 2 ;
		redraw();
	};
	
	this.cancelAOI = function() {
		if( aoi_mode == 2 ) {
			polys.pop();
			aoi_mode = 0 ;
			redraw();
		}
	};

	this.saveAOI = function( aoiname ) {
		if( aoi_mode == 2 ) {
			var l = polys.length - 1 ;
			polys[l].name = aoiname ;
			aoi_mode = 0 ;
			redraw();
		}
	};
	
	this.deleteAOI = function() {
		this.cancelAOI();
		aoi_mode = 3 ;
	};
	
	this.clearAOI = function() {
		this.cancelAOI();
		polys.length = 1 ;
		redraw();
	};
	
	this.newROC = function() {
		polys[0].points.length = 0 ;
		aoi_mode = 1 ;
		redraw();
	};
	
	this.saveConf = function( onComplete ) {

		function precise( n ) 
		{
			return Number( n.toFixed(5) );
		}

		// ROC
		if( refROC ) {
			var roc = {} ;
			if( polys[0].points.length>2 ) {
				roc.left   = precise(Math.min( polys[0].points[0].x, polys[0].points[2].x ) );
				roc.top    = precise(Math.min( polys[0].points[0].y, polys[0].points[2].y ) );
				roc.right  = precise(Math.max( polys[0].points[0].x, polys[0].points[2].x ) );
				roc.bottom = precise(Math.max( polys[0].points[0].y, polys[0].points[2].y ) );
			}
			refROC.set(roc, onComplete);
		}
		
		// AOI
		if( refAOI ) {
			var aoi = [] ;
			for( var i=1; i<polys.length; i++ ) {
				var poly = polys[i] ;
				var l = poly.points.length ;
				if( l>2) {
					var points = [] ;
					for( var p=0; p<l ; p++ ) {
						points.push( {
							"x": precise(poly.points[p].x),
							"y": precise(poly.points[p].y)
							});
					}
					l = points.length - 1 ;
					if( points[l].x != points[0].x || points[l].y != points[0].y ) {
						points.push( points[0] );
					}
					aoi.push({
						name: poly.name,
						points: points
					});
				}
			}
			refAOI.set(aoi, onComplete);
		}
	};

	var refPose = null ;
	var valPose = null ;
	function loadPose( val, ref ) {
		if( refPose ) {
			valPose = val ;
		}
		else {
			refPose = ref ;
		}
		redraw();				
	}

	var refAOI = null ;
	function loadAOI( val, ref ) {
		refAOI = ref ;

		// AOIs
		polys.length = 1 ;
		if(val) {
			for( var i=0; i<val.length; i++ ) {
				if( val[i] && val[i].points && val[i].points.length>2 ) {
					polys[polys.length] = val[i] ;
				}
			}
		}
		redraw();		
	}

	var refROC = null ;
	function loadROC( val, ref ) {
		refROC = ref ;
		if( val ) {
			polys[0].points = [
				{x: val.left , y: val.top },
				{x: val.right , y: val.top },
				{x: val.right , y: val.bottom },
				{x: val.left , y: val.bottom }
			];
		}
		else {
			polys[0].points = [] ;
		}
		redraw();				
	}
	
	// release resources
	this.release = function() {
		if( refPose ) {
			refPose.off('value');
			refPose = null;
		}
		valPose = null ;
		
		if( refAOI ) {
			refAOI.off('value');
			refAOI = null;
		}

		if( refROC ) {
			refROC.off('value');
			refROC = null;
		}
		
		polys = [ {
			name:"ROC",
			points: [] }];
		poly_sel = -1 ;
		aoi_mode = 0 ;
		mousePos = {x:-1,y:-1} ;
		bgImg = null ;
		paint_bkground() ;
	
	};
	
	this.load = function( subid, fireid, camera ) {
		_this.release();
		
		if( _this.showPose ) {
			firebase.marcus_value('/units/' + fireid + '/livepeople/' + camera, loadPose );
		}
		if( _this.showAOI ) {
			firebase.marcus_value('/units/' + fireid + '/conf/cameras/' + camera + '/aoi', loadAOI );
		}
		if( _this.showROC ) {
			firebase.marcus_value('/units/' + fireid + '/conf/cameras/' + camera + '/roc', loadROC );
		}

		canvas.width = 800 ;
		canvas.height = 600 ;
		xscale = 1.0 ;
		yscale = 1.0 ;
				
		bgImg = document.createElement('img');
		$(bgImg).on( "load", function(evt){
			if( bgImg && bgImg.width && bgImg.width > 10 && bgImg.height > 10 ) {
				xscale = canvas.width / bgImg.width ;
				canvas.height = bgImg.height * xscale ;
				yscale = canvas.height / bgImg.height ;
				redraw();
			}
		});
		var parameter = {
			'subid': subid,
			'camera': camera } ;
		if( _this.showAOI || _this.showPose ) 
			parameter.roc = true ;
		bgImg.src = "backgroundimg.php?" + $.param( parameter ) ;

	};
	
}
