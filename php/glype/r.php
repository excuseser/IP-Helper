<?php

require 'includes/init.php';
define('ADMIN_GLYPE_SETTINGS', 'includes/settings.php');

if ($CONFIG['options']['showForm']['default'] == true) {

    if (is_writable(ADMIN_GLYPE_SETTINGS)) {
        $file = file_get_contents(ADMIN_GLYPE_SETTINGS);

		//some wrong in sublime

        $tmp1 = "our homepage.',
	'default' => true,";

        $tmp2 = "our homepage.',
	'default' => false,";

        $file = str_replace($tmp1, $tmp2, $file);

        if ( file_put_contents(ADMIN_GLYPE_SETTINGS, $file) ){
            echo "setting";
        }
    }
}


echo injectionJS();
?>

<script type="text/javascript" src="includes/main.js"></script>
<script type="text/javascript">

	var Request = new Object();
	Request = getUrlRequest();

	url = Request['url'];

    ginf.b = 1;

	// Ensure the user entered the http://
	if ( url.indexOf('http') !== 0 ) {
		url = 'http://' + url;
	}

	// Update location
	window.top.location = myParseURL(url, 'norefer');


	function getUrlRequest(){
                var url = location.search;
                var theRequest = new Object();
                if (url.indexOf("?") != -1) {
                    var str = url.substr(1);
                    if (str.indexOf("&") != -1) {
                        strs = str.split("&");
                        for (var i = 0; i < strs.length; i++) {
                            theRequest[strs[i].split("=")[0]] = unescape(strs[i].split("=")[1]);
                        }
                    } else {
                        var key = str.substring(0,str.indexOf("="));
                        var value = str.substr(str.indexOf("=")+1);
                        theRequest[key] = decodeURI(value);
                    }
                }
                return theRequest;
        }

</script>

