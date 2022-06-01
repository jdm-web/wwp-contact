<?php
/** @var \WonderWp\Plugin\Contact\Email\AbstractContactEmail[] $emails */
/** @var \WonderWp\Plugin\Contact\Entity\ContactEntity $contact */
?>
    <h3>Emails à propos du contact [#<?php echo $contact->getId(); ?>]</h3>
<?php

if (!empty($emails)) {
    foreach ($emails as $email) {
        $subject = er_EmailcheckValue($email->provideSubject());
        $body = er_EmailcheckValue($email->provideBody());
        $to       = $email->provideTo();
        $toName   = er_EmailcheckValue($to['name']);
        $toMail   = er_EmailcheckValue($to['mail']);
        $from     = $email->provideFrom();
        $fromName = er_EmailcheckValue($from['name']);
        $fromMail = er_EmailcheckValue($from['mail']);
        echo '<section style="margin: 40px 0;">
        <h4>Email : ' . $email::Name . '</h4>
        <table class="widefat striped">
            <tr>
                <th>Subject</th>
                <td>' . $subject . '</td>
            </tr>
            <tr>
                <th>Body</th>
                <td>' . $body . '</td>
            </tr>
            <tr>
                <th>To</th>
                <td>' . $toName . ' < ' . $toMail . ' ></td>
            </tr>
            <tr>
                <th>From</th>
                <td>' . $fromName . ' < ' . $fromMail . ' ></td>
            </tr>
            <tr>
                <th>Mailer</th>
                <td>' . get_class($email->getMailer()) . '</td>
            </tr>
            <tr>
                <th>Options</th>
                <td>' . print_r($email->getOptions(), true) . '</td>
            </tr>';
            if($email::isSendable()) {
                echo '
            <tr>
                <th>Test</th>
                <td><a href="' . $sendMailLink . '&email=' . $email::Name . '" class="button">Send Test Email</a></td>
            </tr>';
            } else {
                echo'
            <tr>
                <th>Test</th>
                <td>This email has been marked as non sendable.</td>
            </tr>';
            }

            echo'
        </table>
</section>';
    }
}

function er_EmailcheckValue($value)
{
    return empty($value) ? er_EmailError('empty value') : $value;
}

function er_EmailError($error)
{
    return '<span class="error" style="color: firebrick">error: ' . $error . '</span>';
}
