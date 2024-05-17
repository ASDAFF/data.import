<?
IncludeModuleLangFile(__FILE__);
$APPLICATION->SetTitle(GetMessage("acrit.uninst_fb_PAGE_TITLE"));

if ($_REQUEST['agree'] == 1) {
    if (SITE_CHARSET != 'UTF-8') {
        $charset = 'windows-1251';
    }
    else {
        $charset = 'utf-8';
    }
    $email_def = COption::GetOptionString('main','email_from');
    $from = $email_def ? $email_def : GetMessage("acrit.uninst_fb_MAIL_SUPPORT_EMAIL");
    $to = GetMessage("acrit.uninst_fb_MAIL_SUPPORT_EMAIL");
    $subject = GetMessage("acrit.uninst_fb_MAIL_SUBJECT");
    $mail_msg = GetMessage("acrit.uninst_fb_MAIL_MODULE") . "<br><br />\r\n";
    $mail_msg .= GetMessage("acrit.uninst_fb_MAIL_REASON") . GetMessage("acrit.uninst_fb_MAIL_REASON_" . $_REQUEST['reason'] . "") . "<br>\r\n";
    if ($_REQUEST['reason_other']) {
        $mail_msg .= GetMessage("acrit.uninst_fb_MAIL_REASON_TEXT") . nl2br($_REQUEST['reason_other']) . "<br>\r\n";
    }
    $mail_msg .= GetMessage("acrit.uninst_fb_MAIL_SUPPORT") . GetMessage("acrit.uninst_fb_MAIL_SUPPORT_" . $_REQUEST['support'] . "") . "<br>\r\n";
    $mail_msg .= GetMessage("acrit.uninst_fb_MAIL_EMAIL_TIT") . $email_def . "<br>\r\n";
    $mail_msg .= GetMessage("acrit.uninst_fb_MAIL_SITE_TIT") . $_SERVER['SERVER_NAME'] . "<br>\r\n";
    $mail_msg .= "<br>" . GetMessage("acrit.uninst_fb_MAIL_CALLBACK") . GetMessage("acrit.uninst_fb_MAIL_CALLBACK_" . $_REQUEST['callback'] . "") . "<br>\r\n";
    $headers = "MIME-Version: 1.0\n";
    $headers .= "From: <$from>\n";
    $headers .= "Content-type: text/html; charset=$charset\n";
    $headers .= "Content-Transfer-Encoding: base64\n";
    $res = mail("=?$charset?B?".base64_encode($to)."?= <$to>", "=?$charset?B?".base64_encode($subject)."?=", chunk_split(base64_encode($mail_msg)), $headers, "-f$from");
}

CAdminMessage::ShowNote(GetMessage('acrit.uninst_fb_MAIL_MOD_UNINST_OK'));
