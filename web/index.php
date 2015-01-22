<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Silex\Application as App;
use Postmark\PostmarkClient;

$app = new App();

$app['debug'] = true;

$app->post('/api/{email}', function (App $app, Request $request, $email) {

    if ($email === urldecode('support@oktiptop.com')) {
        $email = urldecode($email);
    } else {
        return new Response('OK', 200);
    }

    $client = new PostmarkClient('933e451c-c708-4379-ae9a-6db649b219d4');

    $input = $request->request->all();
    if (isset($input['email'])) {
        $name = $input['name'];
        $sender_mail = $input['email'];
        $message = $input['support-msg'];
        $subject = $input['subject'];
    }

    if (strlen($subject) < 1) {
        $bodyContent = build_email_body($name, $sender_mail, $message);
        try {
            $sendResult = $client->sendEmail(
                $email,
                $email,
                "Work Journal Support",
                $bodyContent);
        } catch (Exception $e) {
            return new Response($e->getMessage(), 503);
        }
    }
    return new Response('OK', 200);
});

$app->get('/', function(){
    return new Response();
});

$app->run();

function build_email_body($n, $e, $m)
{
    $body = '<html><body>';
    $body .= "<p>$n $e</p>";
    $body .= "<p>$m</p>";
    $body .= "</body></html>";
    return $body;
}
