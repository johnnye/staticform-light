<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Silex\Application as App;
use Postmark\PostmarkClient;

$app = new App();
Dotenv::load(__DIR__);
$app['debug'] = getenv('DEVELOPMENT');

$app->post('/api/{email}', function (App $app, Request $request, $email) {

    $returnURL = $request->headers->get('Referer');
    if ($email !== urldecode(getenv('TO_ADDRESS'))) {
        return returnValue($returnURL);
    }

    $client = new PostmarkClient(getenv('POSTMARK_API_KEY'));

    $input = $request->request->all();

    if (strlen($input[getenv('HONEYPOT_NAME')]) < 1) {
        $bodyContent = build_email_body($input);

        $message = [
            'From'      => getenv('FROM_ADDRESS'),
            'To'        => getenv('TO_ADDRESS'),
            'ReplyTo'   => '\"'.$input['name'].'\" <'.$input['email'] .'>',
            'Subject'   => getenv('SUBJECT_LINE'),
            'HtmlBody'  => $bodyContent,
            //'TrackOpens' => true
        ];
        try {
            $sendResult = $client->sendEmailBatch([$message]);
            //TODO: do something with the response
        } catch (Exception $e) {
            //TODO: Something with the error
            return returnValue($returnURL);
        }
    }
    return returnValue($returnURL);
});

$app->get('/', function(){
    var_dump($_ENV);
    return new Response();
});

$app->run();

function build_email_body($input)
{
    $body = '<html><body>';

    foreach($input as $field => $value) {
        if($field !== getenv('HONEYPOT_NAME')) {
            $body .= "<p><b>$field: </b>$value</p>";
        }
    }

    $body .= "</body></html>";
    return $body;
}

function returnValue($returnURL)
{
    //Where possible redirect to the original page
    if(!is_null($returnURL)) {
        return new \Symfony\Component\HttpFoundation\RedirectResponse($returnURL);
    }
    //Otherwise, stiffen the upperlip and pretend it's all ok to the spammers
    return new Response('OK', 200);
}