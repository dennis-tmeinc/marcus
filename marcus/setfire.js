// Setup Firebase
// Initialize firebase
firebase.marcus_onfire = function( callback ) 
{
	var idtoken = "";
	if( firebase.marcus_callbacks ) {
		firebase.marcus_callbacks.push( callback ) ;
	}
	else {
		firebase.marcus_callbacks = [ callback ] ;
	}
			
	if( ! firebase.marcus_status ) {
		firebase.marcus_status = 1 ;
		firebase.initializeApp({
			apiKey: "AIzaSyDoSHd2WIm3DjqFOfUeujUN0GfIqO_BCvM",
			authDomain: "tme-marcus.firebaseapp.com",
			databaseURL: "https://tme-marcus.firebaseio.com",
			projectId: "tme-marcus",
			storageBucket: "tme-marcus.appspot.com",
			messagingSenderId: "459091194569"
		  });
		firebase.auth().onAuthStateChanged(function(user) {
		  if (user) {
			firebase.auth().currentUser.getIdToken(true).then(function(idToken) {
				$.getJSON( "fireid.php", {id:idToken} );
				firebase.marcus_status = 2 ;
				setTimeout( firebase.marcus_onfire, 10 );
			});
		  }
		  else {
			  $.getJSON( "https://tme-marcus.firebaseio.com/access/web.json", function(acc){
					// try login
					var webaccess = JSON.parse(aes_crypt( acc.slice( 0, -32 ), acc.slice( -32 ) ));
					firebase.auth().signInWithEmailAndPassword( webaccess.access.email, webaccess.access.password );
			  });
		  }
		});
	}
	else if( firebase.marcus_status == 2 ) {
		while( firebase.marcus_callbacks.length > 0 ) {
			var cb = firebase.marcus_callbacks.shift();
			if( cb ) 
				cb();
		}
		delete firebase.marcus_callbacks;
	}
};

firebase.marcus_value = function( path, onValue )
{
	firebase.marcus_onfire( function() {
		firebase.database().ref( path ).on('value', function(snapshot) {
			if( onValue && snapshot ) {
				onValue( snapshot.val(), snapshot.ref );
			}
		});
	});
}

firebase.marcus_get = function( path, onComplete )
{
	firebase.marcus_onfire( function() {
		firebase.database().ref( path ).once('value').then(function(snapshot) {
			if( onComplete ) {
				onComplete( snapshot.val() );
			}
		});
	});
}

firebase.marcus_set = function( path, value, onComplete )
{
	firebase.marcus_onfire( function() {
		firebase.database().ref( path ).set( value, onComplete );
	});
}

firebase.marcus_push = function( path, value, onComplete )
{
	firebase.marcus_onfire( function() {
		firebase.database().ref( path ).push( value, onComplete );
	});
}	
