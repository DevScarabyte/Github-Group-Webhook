<?php
function curl_get_file_contents($URL)
{
    $c = curl_init();
    curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($c, CURLOPT_URL, $URL);
    $contents = curl_exec($c);
    $err  = curl_getinfo($c,CURLINFO_HTTP_CODE);
    curl_close($c);
    if ($contents)
    {
        return $contents;
    }
    else
    {
        return FALSE;
    }
}
function configclean($info)
{
    $file = "SET ME";
    $cleaned = str_replace("&expires=", "\n", $info);
    $cleaned = str_replace("access_token=", "", $cleaned);
    $fopen = fopen($file, 'w');
    $data = $cleaned;
    fwrite($fopen, $data);
    fclose($fopen);
}
function writeToFacebook($x)
{
    $facebook = new Facebook();
    if($x == 0)
    {
        $commit = "SET ME";
        $link = NULL;
        $picure = "";
        $description = "";
    }
    elseif($x == 1)
    {
        $commit = file_get_contents("SET ME");
        $link = "SET ME";
        $picure = "SET ME";
        $description = "SET ME";
    }
    echo $facebook->message(array( 
                                'message'     => $commit,
                                'link'        => $link,
                                'picture'  => $link,
                                'description' => $description ) );
}

class Facebook
{
    /**
     * @var App Data we are going to use
     */
    private $app_id = 'SET ME';
    private $app_secret = 'SET ME';
    /**
     * @var The group id to post to
     */
    public $id = 'SET ME';

    /**
     * @var the page access token given to the application above
     */
    public $app_access_token = '';
    /**
     * @var The back-end service for group's wall
     */
    private $post_url = '';
    private $renew_url = '';
    /**
     * Constructor, sets the url's
     */
    public function Facebook()
    {
        $lines = file('./config/oauth.php');
        $this->app_access_token = $lines[0];
        $this->post_url = 'https://graph.facebook.com/' . $this->id .'/feed';
        $this->renew_url = 'https://graph.facebook.com/oauth/access_token?grant_type=fb_exchange_token&client_id='.$this->app_id.'&client_secret='.$this->app_secret.'&fb_exchange_token='.$this->app_access_token;
    }
    /**
     * Manages the POST message to post an update on a page wall
     *
     * @param array $data
     * @return string the back-end response
     * @private
     */
    public function message($data)
    {
        // need token
        $data['access_token'] = $this->app_access_token;
        // init
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->post_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // execute and close
        $return = curl_exec($ch);
        curl_close($ch);
        // end

        return $return;
    }
    public function renewToken()
    {
        $c = curl_init();
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($c, CURLOPT_URL, $this->renew_url);
        $contents = curl_exec($c);
        $err  = curl_getinfo($c,CURLINFO_HTTP_CODE);
        curl_close($c);
        if ($contents)
        {
            return $contents;
        }
        else
        {
            return FALSE;
        }
    }
}
$facebook = new Facebook();
//Attempt to query group
$graph_url = "https://graph.facebook.com/".$facebook->id."?access_token=".$facebook->app_access_token;
$response = curl_get_file_contents($graph_url);
$decoded_response = json_decode($response);

//var_dump($decoded_response);
//Check for errors 
if(isset($decoded_response->error))
{
    if($decoded_response->error)
    {
        // check to see if this is an oAuth error:
        if ($decoded_response->error->type== "OAuthException")
        {
            //echo "BAD TOKEN";
            $run = $facebook->renewToken();
            configclean($run);
            writeToFacebook($argv[1]);
        }
    } 
}
else
{
//echo "<pre>";
    writeToFacebook($argv[1]);
//echo "</pre>";
}
?>