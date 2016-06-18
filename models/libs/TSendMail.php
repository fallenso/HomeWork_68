<?php
/**
 * 郵件認證 套件
 * @author Fallen
 * @version 1.0
 */

class TSendMail
{

	/**
	 * 使用自建mail server 轉發信件
	 * @param $tomail :: 對象mail
	 * @param $userName :: 對象稱呼
	 * @param $subject
	 * @param $ibody
	 */
	public static function SendMail($tomail, $userName, $subject, $ibody)
	{

		//
		$_mail = new \PHPMailer();
		$_mail->isSMTP();
		$_mail->CharSet = 'UTF-8';

		$_mail->SMTPDebug = 4;
		$_mail->SMTPDebug = 0;
		$_mail->SMTPAuth = true;
		$_mail->AuthType = true; // 開啟驗證功能
		$_mail->Host = 'ssl://smtp.gmail.com'; #'mas.hinet.com';
		$_mail->Port = 465; #25;
		$_mail->SMTPSecure = 'ssl';

		$_mail->Username = SERVICE_USER;
		$_mail->Password = SERVICE_PASSWORD;
		
		$_mail->From = SERVICE_MAIL;
		$_mail->FromName = SERVICE_FROM_NAME;
		$_mail->addAddress($tomail, $userName);     // Add a recipient
		
		$_mail->isHTML(true);                       // Set email format to HTML

		$_mail->Subject = $subject;
		$_mail->Body    = $ibody;
		
		if (!$_mail->send()){

			TLogs::log('TSendMail', $_mail->ErrorInfo);
			return false;
			#echo '<br/><br/>失敗 ='.$_mail->ErrorInfo.'<br/><br/>';
		}#else echo '<br/><br/>成功<br/><br/>';
		return true;
	}
}
