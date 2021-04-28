<?php
// Add more events from here
// https://business.facebook.com/business/help/402791146561655?id=1205376682832142
abstract class FacebookEvents{
    // Events used by me, expand this list how you need
    const ViewContent = 'ViewContent';
    const Lead = 'Lead';
    const Contact = 'Contact';
}
class FacebookConversionApiEvent{
    /**
     * Change these values with your own
     */
    private $token = 'YOUR-SECRET-TOKEN';
    private $pixel_id = 'YOUR-PIXEL-ID';
    // Last stable version on April 2021
    private $api_version = 'v10.0';






    protected $event = array();
    /**
     * Create a new conversion event fro Facebook Api
     * FacebookConversionApiEvent constructor.
     * @param FacebookEvents|null $event_type If not specified default value is FacebookEvents::ViewContent
     */
    function __construct(string $event_type = null){

        $host = $_SERVER['HTTP_HOST'];
        $protocol = $_SERVER['PROTOCOL'] = isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) ? 'https' : 'http';

        // This is the structure I used...
        // Create a new one as you need using this builder
        // https://developers.facebook.com/docs/marketing-api/conversions-api/payload-helper
        $this->event = array(
            "event_name" => isset($event_type) ? $event_type : FacebookEvents::ViewContent,
            "event_time" => time(),
            "action_source" => "website",
            "event_source_url" => "{$protocol}://{$host}{$_SERVER['REQUEST_URI']}",
            "user_data" => array(),
            "custom_data" => array(),
        );

        // Get values from current request
        if (isset($_SERVER['REMOTE_ADDR'])){
            $this->event["user_data"]["client_ip_address"] = $_SERVER['REMOTE_ADDR'];
        }
        if (isset($_SERVER['HTTP_USER_AGENT'])){
            $this->event["user_data"]["client_user_agent"] = $_SERVER['HTTP_USER_AGENT'];
        }
        if (isset($_COOKIE['_fbp'])){
            $this->event["user_data"]["fbp"] = $_COOKIE['_fbp'];
        }
    }
    public function setEmail(string $email){
        $this->event["user_data"]["em"] = hash("sha256", $email);
        $this->event["action_source"] = "email";
    }
    public function setPhone(string $phone){
        $this->event["user_data"]["ph"] = hash("sha256", $phone);
        $this->event["action_source"] = "phone_call";
    }
    public function setConversionValue(int $value, string $currency_iso = 'RON'){
        $this->event["custom_data"]["value"] = $value;
        $this->event["custom_data"]["currency"] = $currency_iso;
    }

    /**
     * Returns string facebook_event_id on success, bool FALSE on error
     * @return bool | string
     */
    function send(){

        // Construct URL to be called
        $fb_call_url = "https://graph.facebook.com/{$this->api_version}/{$this->pixel_id}/events?access_token={$this->token}";

        // Add URL
        if ($this->event["action_source"] == "website" && isset($_SERVER['REQUEST_URI'])){

            $host = $_SERVER['HTTP_HOST'];
            $protocol = $_SERVER['PROTOCOL'] = isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) ? 'https' : 'http';

            // This is for API calls to /contact (Contact - event) , or /request-loan (Lead - event)
            if ($this->event['event_name'] != FacebookEvents::ViewContent){
                if (isset($_SERVER['HTTP_REFERER'])){
                    $this->event["event_source_url"] = "{$_SERVER['HTTP_REFERER']}";
                } else {
                    $this->event["event_source_url"] = "{$protocol}://{$host}{$_SERVER['REQUEST_URI']}";
                }
            }
        }

        // 100% JSON format specified on website
        $payload = json_encode(array(
            "data" => array(
                $this->event
            )
        ), JSON_PRETTY_PRINT);
        error_log($payload);


        /**
         * Create a simple php cURL POST request
         */
        $ch = curl_init( $fb_call_url );

        // Setup request to send json via POST.
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $payload );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));

        // Return response instead of printing.
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        $result = curl_exec($ch);
        curl_close($ch);

        // Was succesfully received by FB?
        $json_respone = json_decode($result, true);
        if (isset($json_respone['events_received']) && $json_respone['events_received'] == 1){
            // error_log($json_respone['fbtrace_id']);
            return $json_respone['fbtrace_id'];
        } else {
            // Debug response by uncommenting this line
            //error_log(print_r($result, 1));
        }
        return false;
    }
}

/**
 * USAGE EXAMPLES
 */
// Simple example for page view
$fb_event = new FacebookConversionApiEvent();
$fb_sent_event = $fb_event->send();
if ($fb_event->send()){
    error_log("Success! FB received & created Event ID {$fb_sent_event}");
}


// Example to be used on contact page, in script which handles a message sent by contact page form
// Simple example for page view
// Send this only if the contact form was successfully executed (e.g. email parameter was succesfully validated)
$fb_event = new FacebookConversionApiEvent();
$fb_event->setEmail($_POST['email']);
$fb_event->setConversionValue(9000);
$fb_event->send();
?>