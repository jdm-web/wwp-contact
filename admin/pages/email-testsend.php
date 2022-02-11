<?php
/** @var \WonderWp\Plugin\Contact\Email\AbstractContactEmail $email */
/** @var \WonderWp\Component\Mailing\Result\EmailResult $result $subject */
$subject = er_EmailcheckValue($email->provideSubject());
$body = er_EmailcheckValue($email->provideBody());
$to       = $email->provideTo();
$toName   = er_EmailcheckValue($to['name']);
$toMail   = er_EmailcheckValue($to['mail']);
$from     = $email->provideFrom();
$fromName = er_EmailcheckValue($from['name']);
$fromMail = er_EmailcheckValue($from['mail']);
?>
<section style="margin: 40px 0;">
    <h4>Email : <?php echo $email::Name; ?></h4>

    <h5>Result</h5>
    <table class="widefat striped">
        <tr>
            <th>Code</th>
            <td><?php echo $result->getCode(); ?></td>
        </tr>
        <tr>
            <th>MsgKey</th>
            <td><?php echo $result->getMsgKey(); ?></td>
        </tr>
        <tr>
            <th>Response</th>
            <td><?php dump($result->getResponse()); ?></td>
        </tr>
        <tr>
            <th>Error</th>
            <td><?php dump($result->getError()); ?></td>
        </tr>
        <tr>
            <th>Data</th>
            <td><?php dump($result->getData()); ?></td>
        </tr>
        <tr>
            <th>Full Raw Result</th>
            <td><?php dump($result); ?></td>
        </tr>
    </table>

    <br /><br /><br />
    <hr/>
    <br /><br /><br />

    <h5>Email Request</h5>
    <table class="widefat striped">
        <tr>
            <th>Subject</th>
            <td><?php echo $subject; ?></td>
        </tr>
        <tr>
            <th>Body</th>
            <td><?php echo $body; ?></td>
        </tr>
        <tr>
            <th>To</th>
            <td><?php echo $toName . ' < ' . $toMail . ' >'; ?>'</td>
        </tr>
        <tr>
            <th>From</th>
            <td><?php echo $fromName . ' < ' . $fromMail . ' >'; ?></td>
        </tr>
        <tr>
            <th>Mailer</th>
            <td><?php echo get_class($email->getMailer()); ?></td>
        </tr>
        <tr>
            <th>Options</th>
            <td><?php echo print_r($email->getOptions(), true); ?></td>
        </tr>
    </table>
</section>
<?php
function er_EmailcheckValue($value)
{
    return empty($value) ? er_EmailError('empty value') : $value;
}

function er_EmailError($error)
{
    return '<span class="error" style="color: firebrick">error: ' . $error . '</span>';
}

?>
