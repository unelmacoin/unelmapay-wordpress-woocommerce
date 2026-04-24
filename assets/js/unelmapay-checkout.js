jQuery(function($){
    $("body").block({
        message: upay_checkout_message,
        baseZ: 99999,
        overlayCSS: {
            background: "#fff",
            opacity: 0.6
        },
        css: {
            padding:        "20px",
            zindex:         "9999999",
            textAlign:      "center",
            color:          "#555",
            border:         "3px solid #aaa",
            backgroundColor:"#fff",
            cursor:         "wait",
            lineHeight:     "24px",
        }
    });
    $("#submit_unelmapay_payment_form").click();
    setTimeout(function(){
        window.location.href = upay_redirect_url;
    }, 2000);
});