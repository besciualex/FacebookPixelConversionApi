<?php
require_once "SimpleFacebookConversionAPI.php";


// Example for landing page: (ViewContent)
// Be aware that viewContent should be used on important pages, and PageView (client side event) must be used on basic pages
$fb_event = new FacebookConversionApiEvent(FacebookEvents::ViewContent);
$fb_sent_event = $fb_event->send();
if ($fb_event->send()){
    error_log("Success! FB received & created Event ID {$fb_sent_event}");
}



// Example to be used on /request-loan landing page (LEAD)
// Send this only if the contact form was successfully executed (e.g. email parameter was succesfully validated)
$fb_event = new FacebookConversionApiEvent(FacebookEvents::Lead);
$fb_event->setEmail($_POST['email']);
$fb_event->setConversionValue($_POST['load_value']);
$fb_event->send();



// Example to be used on /contact page (CONTACT)
// Send this only if the contact form was successfully executed (e.g. email parameter was succesfully validated)
$fb_event = new FacebookConversionApiEvent(FacebookEvents::Contact);
$fb_event->setEmail($_POST['email']);
$fb_event->send();
?>
